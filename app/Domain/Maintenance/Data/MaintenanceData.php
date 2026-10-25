<?php

namespace App\Domain\Maintenance\Data;

use App\Domain\Maintenance\Enums\MaintenanceType;

/**
 * تسجيل صيانة على شمعة (قطاعي) أو على كل شمعات العقد الباقية (جملة).
 * لو لم تتم بعد ($done = false) يُحفظ الفني والموعد فقط، بلا خصم عهدة ولا أسعار.
 */
final readonly class MaintenanceData
{
    /** @param  list<MaintenanceLineData>  $lines */
    public function __construct(
        public MaintenanceType $type,
        public int $technicianId,
        public string $date,
        public bool $done,
        public array $lines,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     * @param  list<int>  $candleIds  الشمعات التي تشملها العملية (تحددها نوع الصيانة لا المستخدم)
     */
    public static function fromArray(array $validated, array $candleIds): self
    {
        $done = (bool) $validated['done'];

        return new self(
            type: MaintenanceType::from($validated['type']),
            technicianId: (int) $validated['technician_id'],
            date: $validated['date'],
            done: $done,
            lines: array_map(
                fn (int $candleId) => MaintenanceLineData::fromArray($candleId, $done ? $validated['lines'][$candleId] : []),
                $candleIds,
            ),
        );
    }

    /** @return list<int> */
    public function candleIds(): array
    {
        return array_map(fn (MaintenanceLineData $line) => $line->candleId, $this->lines);
    }
}
