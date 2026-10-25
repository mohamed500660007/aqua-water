<?php

namespace App\Domain\Reports\Services;

use App\Domain\Complaints\Models\Complaint;
use App\Domain\Complaints\Models\ComplaintPart;
use App\Domain\Gifts\Models\Gift;
use App\Domain\Reports\Data\ReportPeriod;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * خدمة ما بعد البيع: الشكاوى (القطع المتغيّرة وسعرها والفني) والهدايا (القطعة والفني وتكلفتها)
 * خلال الفترة، مع أداء كل فني والقطع الأكثر استخدامًا. كل قسم استعلام تجميعي واحد.
 */
class AfterSalesReport
{
    public const MAX_ROWS = 1000;

    /** @return array<string, float|int> */
    public function summary(ReportPeriod $period, ?int $technicianId): array
    {
        $complaints = $this->complaints($period, $technicianId)
            ->toBase()
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(CASE WHEN resolved = 1 THEN 1 ELSE 0 END), 0) as resolved')
            ->selectRaw('COALESCE(SUM(visit_fee), 0) as visit_fees')
            ->first();

        $parts = $this->parts($period, $technicianId)
            ->toBase()
            ->selectRaw('COALESCE(SUM(complaint_parts.quantity), 0) as pieces')
            ->selectRaw('COALESCE(SUM(complaint_parts.quantity * complaint_parts.price), 0) as amount')
            ->first();

        $gifts = $this->gifts($period, $technicianId)
            ->join('products', 'products.id', '=', 'gifts.product_id')
            ->toBase()
            ->selectRaw('COUNT(*) as total, COUNT(DISTINCT gifts.customer_id) as customers')
            ->selectRaw('COALESCE(SUM(gifts.quantity), 0) as pieces')
            ->selectRaw('COALESCE(SUM(gifts.quantity * COALESCE(products.cost, 0)), 0) as cost')
            ->first();

        return [
            'complaints' => (int) $complaints->total,
            'resolved' => (int) $complaints->resolved,
            'open' => (int) $complaints->total - (int) $complaints->resolved,
            'visit_fees' => (float) $complaints->visit_fees,
            'parts_pieces' => (int) $parts->pieces,
            'parts_amount' => (float) $parts->amount,
            'revenue' => round((float) $complaints->visit_fees + (float) $parts->amount, 2),
            'gifts' => (int) $gifts->total,
            'gift_customers' => (int) $gifts->customers,
            'gift_pieces' => (int) $gifts->pieces,
            'gift_cost' => (float) $gifts->cost,
        ];
    }

    /**
     * لكل فني: الشكاوى وإيرادها والهدايا وقطعها.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function byTechnician(ReportPeriod $period, ?int $technicianId): Collection
    {
        $complaints = $this->complaints($period, $technicianId)
            ->toBase()
            ->select('technician_id')
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(CASE WHEN resolved = 1 THEN 1 ELSE 0 END), 0) as resolved, COALESCE(SUM(visit_fee), 0) as fees')
            ->groupBy('technician_id')
            ->get()
            ->keyBy('technician_id');

        $parts = $this->parts($period, $technicianId)
            ->toBase()
            ->select('complaints.technician_id')
            ->selectRaw('COALESCE(SUM(complaint_parts.quantity * complaint_parts.price), 0) as amount')
            ->groupBy('complaints.technician_id')
            ->pluck('amount', 'technician_id');

        $gifts = $this->gifts($period, $technicianId)
            ->toBase()
            ->select('technician_id')
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(quantity), 0) as pieces')
            ->groupBy('technician_id')
            ->get()
            ->keyBy('technician_id');

        $ids = $complaints->keys()->merge($gifts->keys())->unique();
        $names = User::query()->whereKey($ids->filter())->pluck('name', 'id');

        return $ids
            ->map(fn ($id) => [
                'name' => $names[$id] ?? 'بدون فني',
                'complaints' => (int) ($complaints[$id]->total ?? 0),
                'resolved' => (int) ($complaints[$id]->resolved ?? 0),
                'revenue' => round((float) ($complaints[$id]->fees ?? 0) + (float) ($parts[$id] ?? 0), 2),
                'gifts' => (int) ($gifts[$id]->total ?? 0),
                'gift_pieces' => (int) ($gifts[$id]->pieces ?? 0),
            ])
            ->sortByDesc('complaints')
            ->values();
    }

    /**
     * القطع المستخدمة في الشكاوى والهدايا معًا — الأكثر استخدامًا أولًا.
     *
     * @return Collection<int, array{name: string, complaint_pieces: int, complaint_amount: float, gift_pieces: int}>
     */
    public function partsUsed(ReportPeriod $period, ?int $technicianId): Collection
    {
        $complaintParts = $this->parts($period, $technicianId)
            ->join('products', 'products.id', '=', 'complaint_parts.product_id')
            ->toBase()
            ->select('products.name')
            ->selectRaw('SUM(complaint_parts.quantity) as pieces, SUM(complaint_parts.quantity * complaint_parts.price) as amount')
            ->groupBy('products.name')
            ->get()
            ->keyBy('name');

        $giftParts = $this->gifts($period, $technicianId)
            ->join('products', 'products.id', '=', 'gifts.product_id')
            ->toBase()
            ->select('products.name')
            ->selectRaw('SUM(gifts.quantity) as pieces')
            ->groupBy('products.name')
            ->pluck('pieces', 'name');

        return $complaintParts->keys()->merge($giftParts->keys())->unique()
            ->map(fn (string $name) => [
                'name' => $name,
                'complaint_pieces' => (int) ($complaintParts[$name]->pieces ?? 0),
                'complaint_amount' => (float) ($complaintParts[$name]->amount ?? 0),
                'gift_pieces' => (int) ($giftParts[$name] ?? 0),
            ])
            ->sortByDesc(fn (array $row) => $row['complaint_pieces'] + $row['gift_pieces'])
            ->values();
    }

    /**
     * كشف الشكاوى بقطعها. يُجلب صف زائد لمعرفة إن كان مقطوعًا.
     *
     * @return Collection<int, Complaint>
     */
    public function complaintRows(ReportPeriod $period, ?int $technicianId): Collection
    {
        return $this->complaints($period, $technicianId)
            ->with([
                'contract:id,code,customer_id',
                'contract.customer:id,code,name,governorate,center,village',
                'technician:id,name',
                'parts.product:id,name',
            ])
            ->orderBy('complaint_date')
            ->orderBy('id')
            ->limit(self::MAX_ROWS + 1)
            ->get();
    }

    /**
     * كشف الهدايا. يُجلب صف زائد لمعرفة إن كان مقطوعًا.
     *
     * @return Collection<int, Gift>
     */
    public function giftRows(ReportPeriod $period, ?int $technicianId): Collection
    {
        return $this->gifts($period, $technicianId)
            ->with(['customer:id,code,name,governorate,center,village', 'technician:id,name', 'product:id,name,cost'])
            ->orderBy('gift_date')
            ->orderBy('id')
            ->limit(self::MAX_ROWS + 1)
            ->get();
    }

    private function complaints(ReportPeriod $period, ?int $technicianId): Builder
    {
        return Complaint::query()
            ->whereBetween('complaint_date', $period->bounds())
            ->when($technicianId, fn (Builder $query) => $query->where('technician_id', $technicianId));
    }

    private function parts(ReportPeriod $period, ?int $technicianId): Builder
    {
        return ComplaintPart::query()
            ->join('complaints', 'complaints.id', '=', 'complaint_parts.complaint_id')
            ->whereBetween('complaints.complaint_date', $period->bounds())
            ->when($technicianId, fn (Builder $query) => $query->where('complaints.technician_id', $technicianId));
    }

    private function gifts(ReportPeriod $period, ?int $technicianId): Builder
    {
        return Gift::query()
            ->whereBetween('gifts.gift_date', $period->bounds())
            ->when($technicianId, fn (Builder $query) => $query->where('gifts.technician_id', $technicianId));
    }
}
