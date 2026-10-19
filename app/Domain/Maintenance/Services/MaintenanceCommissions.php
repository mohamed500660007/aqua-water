<?php

namespace App\Domain\Maintenance\Services;

use App\Domain\Maintenance\Models\MaintenanceOperation;
use App\Domain\Maintenance\Models\MaintenancePackage;

/**
 * عمولات الصيانة المستحقة لكل موظف في فترة: عمولات العمليات القطاعي (بتاريخ الزيارة)
 * + العمولة الموحّدة لعقود الصيانة الجملة (بتاريخ بدء العقد) — للفني وللموظفة كلٌ بدوره.
 */
class MaintenanceCommissions
{
    /**
     * @param  list<int>  $userIds
     * @return array<int, array{count: int, amount: float}>
     */
    public function forPeriod(array $userIds, string $from, string $to): array
    {
        $sources = [
            [MaintenanceOperation::class, 'serviced_at', 'technician_id', 'technician_commission'],
            [MaintenanceOperation::class, 'serviced_at', 'sales_rep_id', 'sales_rep_commission'],
            [MaintenancePackage::class, 'started_at', 'technician_id', 'technician_commission'],
            [MaintenancePackage::class, 'started_at', 'sales_rep_id', 'sales_rep_commission'],
        ];

        $totals = [];

        foreach ($sources as [$model, $dateColumn, $userColumn, $amountColumn]) {
            $rows = $model::query()
                ->whereIn($userColumn, $userIds)
                ->whereBetween($dateColumn, [$from, $to])
                ->where($amountColumn, '>', 0)
                ->groupBy($userColumn)
                ->selectRaw("{$userColumn} as user_id, COUNT(*) as item_count, SUM({$amountColumn}) as amount_total")
                ->toBase()
                ->get();

            foreach ($rows as $row) {
                $totals[$row->user_id]['count'] = ($totals[$row->user_id]['count'] ?? 0) + (int) $row->item_count;
                $totals[$row->user_id]['amount'] = round(($totals[$row->user_id]['amount'] ?? 0) + (float) $row->amount_total, 2);
            }
        }

        return $totals;
    }
}
