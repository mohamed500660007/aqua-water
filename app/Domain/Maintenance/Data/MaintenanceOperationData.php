<?php

namespace App\Domain\Maintenance\Data;

use App\Domain\Maintenance\Enums\MaintenanceOperationType;
use App\Domain\Sales\Enums\StockSource;

/**
 * مدخلات عملية صيانة: المراحل المُغيّرة وسعر كل شمعة (القطاعي فقط) والعمولات.
 * في عملية الباكيج تُصفَّر الأسعار والعمولات هنا نفسها — المبلغ في عقد الصيانة والعمولة موحّدة على العقد.
 */
final readonly class MaintenanceOperationData
{
    /** @param  array<int, float>  $prices  رقم المرحلة => سعر الشمعة */
    public function __construct(
        public MaintenanceOperationType $type,
        public int $customerId,
        public int $contractId,
        public ?int $packageId,
        public int $technicianId,
        public ?int $salesRepId,
        public StockSource $stockSource,
        public ?int $warehouseId,
        public string $servicedAt,
        public array $prices,
        public float $technicianCommission = 0.0,
        public float $salesRepCommission = 0.0,
        public ?string $note = null,
    ) {}

    /** @param  array<string, mixed>  $validated */
    public static function fromArray(array $validated): self
    {
        $type = MaintenanceOperationType::from($validated['type']);
        $isPackage = $type === MaintenanceOperationType::Package;
        $stockSource = StockSource::from($validated['stock_source']);
        $salesRepId = isset($validated['sales_rep_id']) ? (int) $validated['sales_rep_id'] : null;
        $positions = array_map('intval', $validated['positions']);

        return new self(
            type: $type,
            customerId: (int) $validated['customer_id'],
            contractId: (int) $validated['sales_contract_id'],
            packageId: $isPackage ? (int) $validated['maintenance_package_id'] : null,
            technicianId: (int) $validated['technician_id'],
            salesRepId: $salesRepId,
            stockSource: $stockSource,
            warehouseId: $stockSource === StockSource::Warehouse ? (int) $validated['warehouse_id'] : null,
            servicedAt: $validated['serviced_at'],
            prices: array_combine($positions, array_map(
                fn (int $position) => $isPackage ? 0.0 : (float) ($validated['prices'][$position] ?? 0),
                $positions,
            )),
            technicianCommission: $isPackage ? 0.0 : (float) ($validated['technician_commission'] ?? 0),
            salesRepCommission: $isPackage || ! $salesRepId ? 0.0 : (float) ($validated['sales_rep_commission'] ?? 0),
            note: $validated['note'] ?? null,
        );
    }

    /** @return list<int> */
    public function positions(): array
    {
        return array_keys($this->prices);
    }

    public function isPackage(): bool
    {
        return $this->type === MaintenanceOperationType::Package;
    }
}
