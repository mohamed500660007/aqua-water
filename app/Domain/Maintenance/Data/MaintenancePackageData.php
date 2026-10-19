<?php

namespace App\Domain\Maintenance\Data;

/**
 * مدخلات عقد الصيانة الجملة: رصيد الشمعات (الأساسية + الهدايا) والعمولة الموحّدة — بلا أي مبالغ للعميل.
 */
final readonly class MaintenancePackageData
{
    public function __construct(
        public int $customerId,
        public int $contractId,
        public ?int $maintenanceContractId,
        public ?int $technicianId,
        public ?int $salesRepId,
        public int $candles,
        public int $giftCandles,
        public float $technicianCommission,
        public float $salesRepCommission,
        public string $startedAt,
        public ?string $note = null,
    ) {}

    /** @param  array<string, mixed>  $validated */
    public static function fromArray(array $validated): self
    {
        $technicianId = isset($validated['technician_id']) ? (int) $validated['technician_id'] : null;
        $salesRepId = isset($validated['sales_rep_id']) ? (int) $validated['sales_rep_id'] : null;

        return new self(
            customerId: (int) $validated['customer_id'],
            contractId: (int) $validated['sales_contract_id'],
            maintenanceContractId: isset($validated['maintenance_contract_id']) ? (int) $validated['maintenance_contract_id'] : null,
            technicianId: $technicianId,
            salesRepId: $salesRepId,
            candles: (int) $validated['candles'],
            giftCandles: (int) ($validated['gift_candles'] ?? 0),
            technicianCommission: $technicianId ? (float) ($validated['technician_commission'] ?? 0) : 0.0,
            salesRepCommission: $salesRepId ? (float) ($validated['sales_rep_commission'] ?? 0) : 0.0,
            startedAt: $validated['started_at'],
            note: $validated['note'] ?? null,
        );
    }
}
