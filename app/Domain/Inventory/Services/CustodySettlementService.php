<?php

namespace App\Domain\Inventory\Services;

use App\Domain\Inventory\Data\CustodySettlementData;
use App\Domain\Inventory\Enums\CustodySettlementStatus;
use App\Domain\Inventory\Enums\CustodySettlementType;
use App\Domain\Inventory\Exceptions\SettlementAlreadyPostedException;
use App\Domain\Inventory\Models\CustodySettlement;
use Illuminate\Support\Facades\DB;

/**
 * تسويات العهدة: المسودة لا تمس أي رصيد، والترحيل يُعدّل عهدة الموظف وحدها — لا يُنشئ
 * أي حركة مخزن ولا خصم راتب. المستند المُرحّل نهائي؛ تصحيحه يكون بتسوية عكسية جديدة.
 */
class CustodySettlementService
{
    public function __construct(private readonly EmployeeStockService $employeeStock) {}

    public function create(CustodySettlementData $data, bool $post = false): CustodySettlement
    {
        return DB::transaction(function () use ($data, $post) {
            $settlement = CustodySettlement::create([
                'status' => CustodySettlementStatus::Draft,
                ...$this->attributes($data),
            ]);

            $this->replaceItems($settlement, $data);

            return $post ? $this->post($settlement) : $settlement;
        });
    }

    public function update(CustodySettlement $settlement, CustodySettlementData $data, bool $post = false): CustodySettlement
    {
        return DB::transaction(function () use ($settlement, $data, $post) {
            $settlement = $this->lockDraft($settlement);

            $settlement->update($this->attributes($data));
            $this->replaceItems($settlement, $data);

            return $post ? $this->post($settlement) : $settlement;
        });
    }

    /**
     * يُطبّق التسوية على العهدة داخل معاملة واحدة: إما أن تُرحَّل كل البنود أو لا شيء.
     * رصيد السالب يُتحقق منه لحظة الترحيل لا لحظة إنشاء المسودة.
     */
    public function post(CustodySettlement $settlement): CustodySettlement
    {
        return DB::transaction(function () use ($settlement) {
            $settlement = $this->lockDraft($settlement);
            // ترتيب ثابت للأقفال يمنع التعارض بين ترحيلين متزامنين لنفس الموظف
            $settlement->load(['items' => fn ($items) => $items->orderBy('product_id')]);

            $date = $settlement->settlement_date->toDateString();
            $note = "{$settlement->type->label()} {$settlement->number()} — {$settlement->reason}";

            foreach ($settlement->items as $item) {
                $movement = $settlement->type === CustodySettlementType::Positive
                    ? $this->employeeStock->increase($settlement->user_id, $item->product_id, $item->quantity, $date, $note, $settlement)
                    : $this->employeeStock->decrease($settlement->user_id, $item->product_id, $item->quantity, $date, $note, $settlement);

                $item->update([
                    'balance_before' => $settlement->type === CustodySettlementType::Positive
                        ? $movement->balance_after - $item->quantity
                        : $movement->balance_after + $item->quantity,
                    'balance_after' => $movement->balance_after,
                ]);
            }

            $settlement->update(['status' => CustodySettlementStatus::Posted, 'posted_at' => now()]);

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

    private function replaceItems(CustodySettlement $settlement, CustodySettlementData $data): void
    {
        $settlement->items()->delete();
        $settlement->items()->createMany(array_map(
            fn ($item) => ['product_id' => $item->productId, 'quantity' => $item->quantity],
            $data->items,
        ));
    }

    /** @return array<string, mixed> */
    private function attributes(CustodySettlementData $data): array
    {
        return [
            'user_id' => $data->userId,
            'type' => $data->type,
            'settlement_date' => $data->settlementDate,
            'reason' => $data->reason,
            'note' => $data->note,
        ];
    }
}
