<?php

namespace App\Domain\Inventory\Services;

use App\Domain\Inventory\Data\StockMovementData;
use App\Domain\Inventory\Enums\StockMovementType;
use App\Domain\Inventory\Exceptions\InsufficientStockException;
use App\Domain\Inventory\Models\StockBalance;
use App\Domain\Inventory\Models\StockMovement;
use App\Domain\Products\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * كل تغيير في المخزون يمر من هنا: تُسجَّل الحركة ويُحدَّث الرصيد داخل معاملة واحدة.
 */
class StockService
{
    public function record(StockMovementData $data): StockMovement
    {
        return DB::transaction(function () use ($data) {
            $balance = $this->lockBalance($data->productId, $data->warehouseId);

            $quantityAfter = match ($data->type) {
                StockMovementType::In => $balance->quantity + $data->quantity,
                StockMovementType::Out => $balance->quantity - $data->quantity,
                // التسوية تضبط الرصيد على الكمية المجرودة فعليًا
                StockMovementType::Adjustment => $data->quantity,
            };

            if ($quantityAfter < 0) {
                throw InsufficientStockException::for(
                    Product::query()->whereKey($data->productId)->value('name') ?? 'المنتج',
                    $balance->quantity,
                    $data->quantity,
                );
            }

            $balance->forceFill(['quantity' => $quantityAfter])->save();

            $movement = new StockMovement([
                'product_id' => $data->productId,
                'warehouse_id' => $data->warehouseId,
                'type' => $data->type,
                'quantity' => $data->quantity,
                'balance_after' => $quantityAfter,
                'note' => $data->note,
                'moved_at' => $data->movedAt,
            ]);

            if ($data->reference) {
                $movement->reference()->associate($data->reference);
            }

            $movement->save();

            return $movement;
        });
    }

    public function available(int $productId, int $warehouseId): int
    {
        return (int) StockBalance::query()
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->value('quantity');
    }

    /**
     * الأرصدة الحالية لكل منتج في مخزن واحد — تُستخدم في نموذج العقد.
     *
     * @return array<int, int>
     */
    public function availabilityMap(int $warehouseId): array
    {
        return StockBalance::query()
            ->where('warehouse_id', $warehouseId)
            ->pluck('quantity', 'product_id')
            ->map(fn ($quantity) => (int) $quantity)
            ->all();
    }

    private function lockBalance(int $productId, int $warehouseId): StockBalance
    {
        $balance = StockBalance::query()
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->lockForUpdate()
            ->first();

        return $balance ?? StockBalance::create([
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'quantity' => 0,
        ]);
    }
}
