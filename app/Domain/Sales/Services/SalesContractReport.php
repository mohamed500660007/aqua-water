<?php

namespace App\Domain\Sales\Services;

use App\Domain\Sales\Enums\ContractKind;
use App\Domain\Sales\Enums\ContractType;
use App\Domain\Sales\Models\SalesContract;
use App\Domain\Sales\Models\SalesContractItem;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * تقرير عقود البيع المطبوع. كل الأقسام تُحسب على نفس مجموعة العقود المفلترة (نفس فلاتر
 * قائمة العقود + فترة تاريخ التعاقد)، وكلها استعلامات تجميعية لا تمر على العقود واحدًا واحدًا.
 */
class SalesContractReport
{
    /** حد أقصى لصفوف الكشف المطبوع حتى لا تُحمَّل كل العقود في الذاكرة */
    public const MAX_ROWS = 1000;

    /**
     * الإجماليات + التوزيع على (نوع العقد × طريقة الدفع) في استعلام واحد.
     *
     * @return array{totals: array<string, float|int>, groups: Collection<int, array<string, mixed>>}
     */
    public function summary(Builder $contracts): array
    {
        $groups = (clone $contracts)
            ->toBase()
            ->select(['kind', 'type'])
            ->selectRaw('COUNT(*) as contracts')
            ->selectRaw('COALESCE(SUM(subtotal), 0) as subtotal')
            ->selectRaw('COALESCE(SUM(discount), 0) as discount')
            ->selectRaw('COALESCE(SUM(total), 0) as total')
            ->selectRaw('COALESCE(SUM(paid), 0) as paid')
            ->selectRaw('COALESCE(SUM(remaining), 0) as remaining')
            ->selectRaw('SUM(CASE WHEN legal_referred_at IS NOT NULL THEN 1 ELSE 0 END) as legal')
            ->selectRaw('COALESCE(SUM(CASE WHEN legal_referred_at IS NOT NULL THEN remaining ELSE 0 END), 0) as legal_remaining')
            ->selectRaw('SUM(CASE WHEN remaining <= 0 THEN 1 ELSE 0 END) as settled')
            ->groupBy('kind', 'type')
            ->get()
            ->map(fn ($row) => [
                'kind' => ContractKind::from($row->kind),
                'type' => ContractType::from($row->type),
                'contracts' => (int) $row->contracts,
                'subtotal' => (float) $row->subtotal,
                'discount' => (float) $row->discount,
                'total' => (float) $row->total,
                'paid' => (float) $row->paid,
                'remaining' => (float) $row->remaining,
                'legal' => (int) $row->legal,
                'legal_remaining' => (float) $row->legal_remaining,
                'settled' => (int) $row->settled,
            ])
            ->sortBy(fn ($group) => [$group['kind']->value, $group['type']->value])
            ->values();

        $totals = collect(['contracts', 'subtotal', 'discount', 'total', 'paid', 'remaining', 'legal', 'legal_remaining', 'settled'])
            ->mapWithKeys(fn (string $key) => [$key => $groups->sum($key)])
            ->all();

        return ['totals' => $totals, 'groups' => $groups];
    }

    /** @return Collection<int, array{name: string, contracts: int, total: float, paid: float, remaining: float}> الأعلى مبيعًا أولًا */
    public function bySalesRep(Builder $contracts): Collection
    {
        $rows = (clone $contracts)
            ->toBase()
            ->select('sales_rep_id')
            ->selectRaw('COUNT(*) as contracts')
            ->selectRaw('COALESCE(SUM(total), 0) as total')
            ->selectRaw('COALESCE(SUM(paid), 0) as paid')
            ->selectRaw('COALESCE(SUM(remaining), 0) as remaining')
            ->groupBy('sales_rep_id')
            ->get();

        $names = User::query()->whereIn('id', $rows->pluck('sales_rep_id')->filter())->pluck('name', 'id');

        return $rows
            ->map(fn ($row) => [
                'name' => $names[$row->sales_rep_id] ?? 'بدون مندوب',
                'contracts' => (int) $row->contracts,
                'total' => (float) $row->total,
                'paid' => (float) $row->paid,
                'remaining' => (float) $row->remaining,
            ])
            ->sortByDesc('total')
            ->values();
    }

    /** @return Collection<int, object{product_name: string, quantity: int, total: float}> المنتجات المباعة — الأعلى قيمة أولًا */
    public function products(Builder $contracts): Collection
    {
        return SalesContractItem::query()
            ->whereIn('sales_contract_id', (clone $contracts)->select('sales_contracts.id'))
            ->toBase()
            ->select('product_name')
            ->selectRaw('COALESCE(SUM(quantity), 0) as quantity')
            ->selectRaw('COALESCE(SUM(total), 0) as total')
            ->groupBy('product_name')
            ->orderByDesc('total')
            ->get();
    }

    /**
     * صفوف الكشف مع العميل والمندوب. يُجلب صف زائد عن الحد لمعرفة إن كان الكشف مقطوعًا.
     *
     * @return Collection<int, SalesContract>
     */
    public function rows(Builder $contracts): Collection
    {
        return (clone $contracts)
            ->select([
                'id', 'code', 'kind', 'type', 'customer_id', 'sales_rep_id', 'contract_date',
                'total', 'paid', 'remaining', 'legal_referred_at',
            ])
            ->with(['customer:id,code,name', 'salesRep:id,name'])
            ->limit(self::MAX_ROWS + 1)
            ->get();
    }
}
