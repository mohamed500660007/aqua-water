<?php

namespace App\Domain\Sales\Data;

use App\Domain\Sales\Enums\ContractKind;
use App\Domain\Sales\Enums\ContractType;
use App\Domain\Sales\Enums\StockSource;

/**
 * مدخلات إنشاء/تعديل العقد (بيع جهاز أو صيانة فقط) مع بنوده وبيانات التقسيط.
 */
final readonly class SalesContractData
{
    /** @param  list<SalesContractItemData>  $items */
    public function __construct(
        public int $customerId,
        public int $warehouseId,
        public string $contractDate,
        public ?string $installationDate,
        public string $maintenanceDate,
        public ContractType $type,
        public StockSource $stockSource,
        public array $items,
        public float $discount = 0.0,
        public float $paid = 0.0,
        public ?string $notes = null,
        public ?int $maintenancePlanId = null,
        public ?int $salesRepId = null,
        public ?int $installerId = null,
        public ?int $installmentsCount = null,
        public ?int $installmentPeriodMonths = null,
        public ?string $firstDueDate = null,
        public ?string $collectionDate = null,
        public ContractKind $kind = ContractKind::Sale,
        public float $price = 0.0,
    ) {}

    public function isMaintenance(): bool
    {
        return $this->kind === ContractKind::Maintenance;
    }

    /** @param  array<string, mixed>  $validated */
    public static function fromArray(array $validated): self
    {
        $type = ContractType::from($validated['type']);
        $kind = ContractKind::from($validated['kind'] ?? ContractKind::Sale->value);
        $isInstallment = $type === ContractType::Installment;
        $isMaintenance = $kind === ContractKind::Maintenance;

        return new self(
            customerId: (int) $validated['customer_id'],
            warehouseId: (int) $validated['warehouse_id'],
            contractDate: $validated['contract_date'],
            installationDate: $validated['installation_date'] ?? null,
            maintenanceDate: $validated['maintenance_date'],
            type: $type,
            // عقد الصيانة لا يصرف بضاعة، فلا بنود له ولا مصدر غير المخزن الافتراضي
            stockSource: $isMaintenance
                ? StockSource::Warehouse
                : StockSource::from($validated['stock_source'] ?? StockSource::Warehouse->value),
            items: $isMaintenance ? [] : array_map(
                fn (array $item) => SalesContractItemData::fromArray($item),
                array_values($validated['items'] ?? []),
            ),
            discount: (float) ($validated['discount'] ?? 0),
            paid: (float) ($validated['paid'] ?? 0),
            notes: $validated['notes'] ?? null,
            maintenancePlanId: isset($validated['maintenance_plan_id']) ? (int) $validated['maintenance_plan_id'] : null,
            salesRepId: isset($validated['sales_rep_id']) ? (int) $validated['sales_rep_id'] : null,
            installerId: isset($validated['installer_id']) ? (int) $validated['installer_id'] : null,
            // حقول التقسيط تُتجاهل تمامًا في العقد النقدي
            installmentsCount: $isInstallment ? (int) $validated['installments_count'] : null,
            installmentPeriodMonths: $isInstallment ? (int) $validated['installment_period_months'] : null,
            firstDueDate: $isInstallment ? $validated['first_due_date'] : null,
            collectionDate: $validated['collection_date'] ?? null,
            kind: $kind,
            price: $isMaintenance ? (float) ($validated['price'] ?? 0) : 0.0,
        );
    }
}
