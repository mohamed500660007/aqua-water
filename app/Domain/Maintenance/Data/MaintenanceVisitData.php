<?php

namespace App\Domain\Maintenance\Data;

use App\Domain\Maintenance\Enums\WarrantyStatus;
use App\Domain\Sales\Enums\StockSource;

/**
 * مدخلات تسجيل زيارة صيانة (تغيير شمعة) وخصمها من مصدرها في المخزون.
 */
final readonly class MaintenanceVisitData
{
    public function __construct(
        public int $contractId,
        public int $productId,
        public StockSource $stockSource,
        public int $quantity,
        public float $price,
        public WarrantyStatus $warrantyStatus,
        public string $performedAt,
        public ?int $warehouseId = null,
        public ?int $technicianId = null,
        public ?int $salesRepId = null,
        public ?string $note = null,
    ) {}

    /** @param  array<string, mixed>  $validated */
    public static function fromArray(array $validated): self
    {
        return new self(
            contractId: (int) $validated['sales_contract_id'],
            productId: (int) $validated['product_id'],
            stockSource: StockSource::from($validated['stock_source']),
            quantity: (int) $validated['quantity'],
            price: (float) $validated['price'],
            warrantyStatus: WarrantyStatus::from($validated['warranty_status']),
            performedAt: $validated['performed_at'],
            warehouseId: isset($validated['warehouse_id']) ? (int) $validated['warehouse_id'] : null,
            technicianId: isset($validated['technician_id']) ? (int) $validated['technician_id'] : null,
            salesRepId: isset($validated['sales_rep_id']) ? (int) $validated['sales_rep_id'] : null,
            note: $validated['note'] ?? null,
        );
    }
}
