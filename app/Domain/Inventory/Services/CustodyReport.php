<?php

namespace App\Domain\Inventory\Services;

use App\Domain\Complaints\Models\ComplaintPart;
use App\Domain\Gifts\Models\Gift;
use App\Domain\Inventory\Enums\CustodySettlementStatus;
use App\Domain\Inventory\Enums\StockMovementType;
use App\Domain\Inventory\Models\CustodySettlement;
use App\Domain\Inventory\Models\EmployeeStock;
use App\Domain\Inventory\Models\EmployeeStockMovement;
use App\Domain\Products\Models\Product;
use App\Domain\Reports\Data\ReportPeriod;
use App\Domain\Sales\Models\SalesContract;
use App\Domain\Sales\Models\SalesContractMaintenanceItem;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * تقرير عهد الموظفين: ما استلمه كل موظف خلال الفترة وأين ذهب (بيع، صيانة، شكاوى، هدايا، تسويات)،
 * ورصيده الحالي — لكل موظف ولكل صنف معه. يُصنَّف مصدر كل حركة من مرجعها (reference_type)،
 * فالتقرير كله استعلامان تجميعيان مهما كثرت الحركات.
 */
class CustodyReport
{
    public const MAX_ROWS = 1000;

    /** أعمدة التقرير بترتيب العرض: المفتاح => العنوان */
    public const COLUMNS = [
        'received' => 'استلم من المخزن',
        'sales' => 'صرف في عقود',
        'maintenance' => 'صيانة',
        'complaints' => 'شكاوى',
        'gifts' => 'هدايا',
        'shortage' => 'عجز تسوية',
        'surplus' => 'زيادة تسوية',
    ];

    /**
     * صف لكل موظف: حركات الفترة مصنّفة + الرصيد الحالي، الأكثر استلامًا أولًا.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function byEmployee(ReportPeriod $period, ?int $userId): Collection
    {
        $movements = $this->classified($period, $userId, ['user_id']);
        $balances = EmployeeStock::query()
            ->when($userId, fn (Builder $query) => $query->where('user_id', $userId))
            ->toBase()
            ->selectRaw('user_id, SUM(quantity) as quantity')
            ->groupBy('user_id')
            ->pluck('quantity', 'user_id');

        $userIds = $movements->keys()->merge($balances->keys())->unique();
        $users = User::query()->whereKey($userIds)->get(['id', 'name', 'role'])->keyBy('id');

        return $userIds
            ->map(fn ($id) => [
                'user' => $users[$id] ?? null,
                ...$this->columns($movements[$id] ?? collect()),
                'balance' => (int) ($balances[$id] ?? 0),
            ])
            ->filter(fn (array $row) => $row['user'] !== null)
            ->sortByDesc('received')
            ->values();
    }

    /**
     * تفصيل كل موظف بأصنافه: نفس الأعمدة لكل (موظف، صنف). يُجلب صف زائد لمعرفة إن كان مقطوعًا.
     *
     * @return Collection<int, array{user: User, products: Collection<int, array<string, mixed>>}>
     */
    public function byProduct(ReportPeriod $period, ?int $userId): Collection
    {
        $movements = $this->classified($period, $userId, ['user_id', 'product_id']);
        $balances = EmployeeStock::query()
            ->when($userId, fn (Builder $query) => $query->where('user_id', $userId))
            ->where('quantity', '!=', 0)
            ->limit(self::MAX_ROWS)
            ->get(['user_id', 'product_id', 'quantity']);

        $keys = $movements->keys()
            ->merge($balances->map(fn (EmployeeStock $stock) => "{$stock->user_id}:{$stock->product_id}"))
            ->unique()
            ->take(self::MAX_ROWS);
        $balanceOf = $balances->mapWithKeys(fn (EmployeeStock $stock) => ["{$stock->user_id}:{$stock->product_id}" => (int) $stock->quantity]);

        $ids = $keys->map(fn (string $key) => explode(':', $key));
        $users = User::query()->whereKey($ids->pluck(0)->unique())->get(['id', 'name'])->keyBy('id');
        $products = Product::query()->whereKey($ids->pluck(1)->unique())->get(['id', 'name', 'sku'])->keyBy('id');

        return $keys
            ->map(function (string $key) use ($movements, $balanceOf, $products) {
                [$userId, $productId] = explode(':', $key);

                return [
                    'user_id' => (int) $userId,
                    'product' => $products[$productId] ?? null,
                    ...$this->columns($movements[$key] ?? collect()),
                    'balance' => $balanceOf[$key] ?? 0,
                ];
            })
            ->groupBy('user_id')
            ->filter(fn ($rows, $id) => isset($users[$id]))
            ->map(fn (Collection $rows, $id) => ['user' => $users[$id], 'products' => $rows->sortBy(fn ($row) => $row['product']?->name)->values()])
            ->sortBy(fn (array $group) => $group['user']->name)
            ->values();
    }

    /**
     * التسويات المُرحّلة في الفترة بسطورها.
     *
     * @return Collection<int, CustodySettlement>
     */
    public function settlements(ReportPeriod $period, ?int $userId): Collection
    {
        return CustodySettlement::query()
            ->where('status', CustodySettlementStatus::Posted->value)
            ->whereBetween('settlement_date', $period->bounds())
            ->when($userId, fn (Builder $query) => $query->where('user_id', $userId))
            ->with(['user:id,name', 'items.product:id,name'])
            ->orderBy('settlement_date')
            ->orderBy('id')
            ->limit(self::MAX_ROWS)
            ->get();
    }

    /**
     * كميات حركات الفترة مجمّعة على $groupBy + نوع المرجع + الاتجاه، ومفهرسة بمفتاح المجموعة.
     *
     * @param  list<string>  $groupBy
     * @return Collection<string, Collection<int, object>>
     */
    private function classified(ReportPeriod $period, ?int $userId, array $groupBy): Collection
    {
        return EmployeeStockMovement::query()
            ->whereBetween('moved_at', $period->bounds())
            ->when($userId, fn (Builder $query) => $query->where('user_id', $userId))
            ->toBase()
            ->select([...$groupBy, 'reference_type', 'type'])
            ->selectRaw('SUM(quantity) as quantity')
            ->groupBy([...$groupBy, 'reference_type', 'type'])
            ->get()
            ->groupBy(fn ($row) => implode(':', array_map(fn (string $column) => $row->{$column}, $groupBy)));
    }

    /**
     * يحوّل حركات مجموعة واحدة إلى أعمدة التقرير. الإرجاع (حركة وارد على عقد أو شكوى أو هدية)
     * يُخصم من صرفها، فيظهر الصافي.
     *
     * @param  Collection<int, object>  $rows
     * @return array<string, int>
     */
    private function columns(Collection $rows): array
    {
        $columns = array_fill_keys(array_keys(self::COLUMNS), 0);
        $settlement = (new CustodySettlement)->getMorphClass();

        foreach ($rows as $row) {
            $quantity = (int) $row->quantity;
            $out = $row->type === StockMovementType::Out->value;

            $column = match ($row->reference_type) {
                null => $out ? null : 'received',
                (new SalesContract)->getMorphClass() => 'sales',
                (new SalesContractMaintenanceItem)->getMorphClass() => 'maintenance',
                (new ComplaintPart)->getMorphClass() => 'complaints',
                (new Gift)->getMorphClass() => 'gifts',
                $settlement => $out ? 'shortage' : 'surplus',
                default => null,
            };

            if ($column === null) {
                continue;
            }

            $signed = in_array($column, ['received', 'shortage', 'surplus'], true) || $out ? $quantity : -$quantity;
            $columns[$column] += $signed;
        }

        return $columns;
    }
}
