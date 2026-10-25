<?php

namespace App\Domain\Complaints\Data;

/**
 * مدخلات تسجيل شكوى: زيارة قد تشمل قطع غيار تُخصم من المخزون، وقد تقتصر على رسوم زيارة.
 */
final readonly class ComplaintData
{
    /** @param  list<ComplaintPartData>  $parts */
    public function __construct(
        public int $contractId,
        public string $complaintDate,
        public float $visitFee,
        public bool $resolved,
        public array $parts,
        public ?int $technicianId = null,
        public ?int $salesRepId = null,
        public ?string $note = null,
    ) {}

    /** @param  array<string, mixed>  $validated */
    public static function fromArray(array $validated): self
    {
        return new self(
            contractId: (int) $validated['sales_contract_id'],
            complaintDate: $validated['complaint_date'],
            visitFee: (float) ($validated['visit_fee'] ?? 0),
            resolved: (bool) ($validated['resolved'] ?? false),
            parts: array_map(
                fn (array $part) => ComplaintPartData::fromArray($part),
                array_values($validated['parts'] ?? []),
            ),
            technicianId: isset($validated['technician_id']) ? (int) $validated['technician_id'] : null,
            salesRepId: isset($validated['sales_rep_id']) ? (int) $validated['sales_rep_id'] : null,
            note: $validated['note'] ?? null,
        );
    }
}
