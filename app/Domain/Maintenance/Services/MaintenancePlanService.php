<?php

namespace App\Domain\Maintenance\Services;

use App\Domain\Maintenance\Data\MaintenancePlanData;
use App\Domain\Maintenance\Models\MaintenancePlan;
use Illuminate\Support\Facades\DB;

class MaintenancePlanService
{
    public function create(MaintenancePlanData $data): MaintenancePlan
    {
        return DB::transaction(function () use ($data) {
            $plan = MaintenancePlan::create($this->attributes($data));

            $this->syncItems($plan, $data);

            return $plan;
        });
    }

    public function update(MaintenancePlan $plan, MaintenancePlanData $data): MaintenancePlan
    {
        return DB::transaction(function () use ($plan, $data) {
            $plan->update($this->attributes($data));

            $this->syncItems($plan, $data);

            return $plan;
        });
    }

    public function delete(MaintenancePlan $plan): void
    {
        $plan->delete();
    }

    /** @return array<string, mixed> */
    private function attributes(MaintenancePlanData $data): array
    {
        return [
            'name' => $data->name,
            'description' => $data->description,
            'duration_months' => $data->durationMonths,
            'status' => $data->status,
        ];
    }

    /** حفظ البنود مع الإبقاء على السجلات القائمة وحذف ما استُبعد من النموذج */
    private function syncItems(MaintenancePlan $plan, MaintenancePlanData $data): void
    {
        $productIds = array_map(fn ($item) => $item->productId, $data->items);

        $plan->items()->whereNotIn('product_id', $productIds)->delete();

        foreach ($data->items as $item) {
            $plan->items()->updateOrCreate(
                ['product_id' => $item->productId],
                ['replacement_date' => $item->replacementDate],
            );
        }

        $plan->unsetRelation('items');
    }
}
