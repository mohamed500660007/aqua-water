<?php

namespace App\Domain\Inventory\Services;

use App\Domain\Inventory\Data\CustodySettlementData;
use App\Domain\Inventory\Enums\CustodySettlementStatus;
use App\Domain\Inventory\Enums\CustodySettlementType;
use App\Domain\Inventory\Exceptions\SettlementAlreadyPostedException;
use App\Domain\Inventory\Exceptions\SettlementMatchesCustodyException;
use App\Domain\Inventory\Models\CustodySettlement;
use Illuminate\Support\Facades\DB;

/**
 * تسويات العهدة بالجرد: لكل منتج تُكتب الكمية الفعلية التي رجع بها الموظف، والفرق عن رصيد عهدته
 * هو التسوية — أقل منه عجز (سالب) وأكثر منه زيادة (موجب). المسودة لا تمس أي رصيد وتحفظ معاينة
 * للفروق، والترحيل يعيد حسابها تحت القفل ويضبط العهدة وحدها — لا حركة مخزن ولا خصم راتب.
 * المستند المُرحّل نهائي؛ تصحيحه يكون بتسوية جديدة.
 */
class CustodySettlementService
{
    public function __construct(private readonly EmployeeStockService $employeeStock) {}

    public function create(CustodySettlementData $data, bool $post = false): CustodySettlement
    {
        return DB::transaction(function () use ($data, $post) {
            $lines = $this->previewLines($data);

            $settlement = CustodySettlement::create([
                'status' => CustodySettlementStatus::Draft,
                'type' => $this->typeOf($lines),
                ...$this->attributes($data),
            ]);

            $settlement->items()->createMany($lines);

            return $post ? $this->post($settlement) : $settlement;
        });
    }

    public function update(CustodySettlement $settlement, CustodySettlementData $data, bool $post = false): CustodySettlement
    {
        return DB::transaction(function () use ($settlement, $data, $post) {
            $settlement = $this->lockDraft($settlement);
            $lines = $this->previewLines($data);

            $settlement->update(['type' => $this->typeOf($lines), ...$this->attributes($data)]);
            $settlement->items()->delete();
            $settlement->items()->createMany($lines);

            return $post ? $this->post($settlement) : $settlement;
        });
    }

    /**
     * يضبط العهدة على الكميات الفعلية داخل معاملة واحدة: إما أن تُرحَّل كل البنود أو لا شيء.
     * الفرق يُحسب من رصيد العهدة لحظة الترحيل، لا من معاينة المسودة.
     */
    public function post(CustodySettlement $settlement): CustodySettlement
    {
        return DB::transaction(function () use ($settlement) {
            $settlement = $this->lockDraft($settlement);
            // ترتيب ثابت للأقفال يمنع التعارض بين ترحيلين متزامنين لنفس الموظف
            $settlement->load(['items' => fn ($items) => $items->orderBy('product_id')]);

            $date = $settlement->settlement_date->toDateString();
            $note = "تسوية جرد {$settlement->number()} — {$settlement->reason}";
            $differences = [];

            foreach ($settlement->items as $item) {
                [$before, $after] = $this->employeeStock->settleTo(
                    $settlement->user_id, $item->product_id, $item->counted_quantity, $date, $note, $settlement,
                );

                $item->update($this->line($before, $after));
                $differences[] = $after - $before;
            }

            $type = CustodySettlementType::forDifferences($differences)
                ?? throw SettlementMatchesCustodyException::make();

            $settlement->update(['type' => $type, 'status' => CustodySettlementStatus::Posted, 'posted_at' => now()]);

            return $settlement;
        });
    }

    public function delete(CustodySettlement $settlement): void
    {
        DB::transaction(fn () => $this->lockDraft($settlement)->delete());
    }

    /** يعيد قراءة المستند بقفل حتى لا يُرحَّل أو يُعدَّل من طلبين متزامنين */
    private function lockDraft(CustodySettlement $settlement): CustodySettlement
    {
        $locked = CustodySettlement::query()->lockForUpdate()->findOrFail($settlement->id);

        if (! $locked->isDraft()) {
            throw SettlementAlreadyPostedException::for($locked->number());
        }

        return $locked;
    }

    /**
     * سطور المسودة بفروقها على العهدة الحالية — معاينة فقط تُعاد عند الترحيل.
     *
     * @return list<array<string, int>>
     */
    private function previewLines(CustodySettlementData $data): array
    {
        $balances = $this->employeeStock->availabilityMap($data->userId);

        return array_map(fn ($item) => [
            'product_id' => $item->productId,
            'counted_quantity' => $item->quantity,
            ...$this->line($balances[$item->productId] ?? 0, $item->quantity),
        ], $data->items);
    }

    /** @return array{balance_before: int, balance_after: int, quantity: int} */
    private function line(int $before, int $counted): array
    {
        return ['balance_before' => $before, 'balance_after' => $counted, 'quantity' => abs($counted - $before)];
    }

    /** @param  list<array<string, int>>  $lines */
    private function typeOf(array $lines): CustodySettlementType
    {
        return CustodySettlementType::forDifferences(array_map(fn (array $line) => $line['balance_after'] - $line['balance_before'], $lines))
            ?? throw SettlementMatchesCustodyException::make();
    }

    /** @return array<string, mixed> */
    private function attributes(CustodySettlementData $data): array
    {
        return [
            'user_id' => $data->userId,
            'settlement_date' => $data->settlementDate,
            'reason' => $data->reason,
            'note' => $data->note,
        ];
    }
}
