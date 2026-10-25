<?php

namespace App\Domain\Sales\Data;

/**
 * مدخلات تسجيل تحصيل قسط — قد يكون التحصيل جزئيًا فيتولّد قسط جديد بالمتبقي.
 */
final readonly class CollectInstallmentData
{
    public function __construct(
        public float $amount,
        public string $paidAt,
        public ?int $collectedById = null,
        public ?int $salesRepId = null,
        public ?string $note = null,
    ) {}

    /** @param  array<string, mixed>  $validated */
    public static function fromArray(array $validated): self
    {
        return new self(
            amount: (float) $validated['amount'],
            paidAt: $validated['paid_at'],
            collectedById: isset($validated['collected_by_id']) ? (int) $validated['collected_by_id'] : null,
            salesRepId: isset($validated['sales_rep_id']) ? (int) $validated['sales_rep_id'] : null,
            note: $validated['note'] ?? null,
        );
    }
}
