<?php

namespace App\Domain\Inventory\Services;

use App\Domain\Inventory\Data\EmployeeHandoverData;
use App\Domain\Inventory\Data\StockMovementData;
use App\Domain\Inventory\Enums\StockMovementType;
use App\Domain\Inventory\Exceptions\InsufficientStockException;
use App\Domain\Inventory\Models\EmployeeStock;
use App\Domain\Inventory\Models\EmployeeStockMovement;
use App\Domain\Products\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ما يحمله الموظفون من بضاعة أخذوها من المخزن الرئيسي — رصيد مستقل عن رصيد المخزن،
 * يُحدَّث فقط عبر حركة تسليم (handOver) أو بيع/إرجاع (decrease/increase).
 */
class EmployeeStockService
{
    public function __construct(private readonly StockService $stock) {}

    /**
     * يأخذ الموظف عهدة من مخزن رئيسي قد تضم أكثر من منتج — كل بند حركة تسليم مستقلة،
     * لكن العملية بالكامل تُلغى معًا لو فشل أحد بنودها (نفاد رصيد أحد المنتجات مثلًا).
     *
     * @return Collection<int, EmployeeStockMovement>
     */
    public function handOverMany(EmployeeHandoverData $data): Collection
    {
        return DB::transaction(fn () => collect($data->items)->map(
            fn ($item) => $this->handOver(
                userId: $data->userId,
                productId: $item->productId,
                warehouseId: $data->warehouseId,
                quantity: $item->quantity,
                date: $data->movedAt,
                note: $data->note,
            ),
        ));
    }

    /** يأخذ الموظف كمية من مخزن رئيسي إلى ما يحمله شخصيًا */
    public function handOver(
        int $userId,
        int $productId,
        int $warehouseId,
        int $quantity,
        string $date,
        ?string $note = null,
    ): EmployeeStockMovement {
        return DB::transaction(function () use ($userId, $productId, $warehouseId, $quantity, $date, $note) {
            $this->stock->record(new StockMovementData(
                productId: $productId,
                warehouseId: $warehouseId,
                type: StockMovementType::Out,
                quantity: $quantity,
                movedAt: $date,
                note: $note ? "تسليم لموظف — {$note}" : 'تسليم لموظف',
            ));

            return $this->applyDelta($userId, $productId, $quantity, StockMovementType::In, $date, $note ?? 'استلام من المخزن الرئيسي');
        });
    }

    public function decrease(
        int $userId,
        int $productId,
        int $quantity,
        string $date,
        ?string $note = null,
        ?Model $reference = null,
    ): EmployeeStockMovement {
        return $this->applyDelta($userId, $productId, $quantity, StockMovementType::Out, $date, $note, $reference);
    }

    public function increase(
        int $userId,
        int $productId,
        int $quantity,
        string $date,
        ?string $note = null,
        ?Model $reference = null,
    ): EmployeeStockMovement {
        return $this->applyDelta($userId, $productId, $quantity, StockMovementType::In, $date, $note, $reference);
    }

    public function available(int $userId, int $productId): int
    {
        return (int) EmployeeStock::query()
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->value('quantity');
    }

    /**
     * أرصدة موظف واحد لكل منتج — تُستخدم في نموذج العقد لعرض المتاح فورًا.
     *
     * @return array<int, int>
     */
    public function availabilityMap(int $userId): array
    {
        return EmployeeStock::query()
            ->where('user_id', $userId)
            ->pluck('quantity', 'product_id')
            ->map(fn ($quantity) => (int) $quantity)
            ->all();
    }

    /**
     * عهدة كل الموظفين دفعة واحدة: [user_id => [product_id => quantity]].
     *
     * @return array<int, array<int, int>>
     */
    public function availabilityMatrix(): array
    {
        return EmployeeStock::query()
            ->get(['user_id', 'product_id', 'quantity'])
            ->groupBy('user_id')
            ->map(fn ($balances) => $balances->pluck('quantity', 'product_id')->map(fn ($q) => (int) $q)->all())
            ->all();
    }

    private function applyDelta(
        int $userId,
        int $productId,
        int $quantity,
        StockMovementType $type,
        string $date,
        ?string $note,
        ?Model $reference = null,
    ): EmployeeStockMovement {
        return DB::transaction(function () use ($userId, $productId, $quantity, $type, $date, $note, $reference) {
            $balance = $this->lockBalance($userId, $productId);

            $quantityAfter = $type === StockMovementType::In
                ? $balance->quantity + $quantity
                : $balance->quantity - $quantity;

            if ($quantityAfter < 0) {
                throw InsufficientStockException::for(
                    Product::query()->whereKey($productId)->value('name') ?? 'المنتج',
                    $balance->quantity,
                    $quantity,
                );
            }

            $balance->forceFill(['quantity' => $quantityAfter])->save();

            $movement = new EmployeeStockMovement([
                'user_id' => $userId,
                'product_id' => $productId,
                'type' => $type,
                'quantity' => $quantity,
                'balance_after' => $quantityAfter,
                'note' => $note,
                'moved_at' => $date,
            ]);

            if ($reference) {
                $movement->reference()->associate($reference);
            }

            $movement->save();

            return $movement;
        });
    }

    private function lockBalance(int $userId, int $productId): EmployeeStock
    {
        $balance = EmployeeStock::query()
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        return $balance ?? EmployeeStock::create([
            'user_id' => $userId,
            'product_id' => $productId,
            'quantity' => 0,
        ]);
    }
}
