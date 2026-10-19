<?php

namespace App\Domain\Maintenance\Services;

use App\Domain\Maintenance\Data\MaintenancePackageData;
use App\Domain\Maintenance\Enums\MaintenancePackageStatus;
use App\Domain\Maintenance\Exceptions\MaintenanceException;
use App\Domain\Maintenance\Models\MaintenancePackage;

/**
 * عقد الصيانة الجملة: يُضاف رصيد الشمعات (الأساسية + الهدايا) لحساب العميل مرة واحدة،
 * والعمولة الموحّدة تُحتسب في مرتب شهر بدء العقد. لا خصم من المخزون هنا — الخصم مع كل زيارة.
 */
class MaintenancePackageService
{
    public function create(MaintenancePackageData $data): MaintenancePackage
    {
        return MaintenancePackage::create([
            'customer_id' => $data->customerId,
            'sales_contract_id' => $data->contractId,
            'maintenance_contract_id' => $data->maintenanceContractId,
            'technician_id' => $data->technicianId,
            'sales_rep_id' => $data->salesRepId,
            'candles' => $data->candles,
            'gift_candles' => $data->giftCandles,
            'used_candles' => 0,
            'technician_commission' => $data->technicianCommission,
            'sales_rep_commission' => $data->salesRepCommission,
            'status' => MaintenancePackageStatus::Active,
            'started_at' => $data->startedAt,
            'note' => $data->note,
        ]);
    }

    /** الحذف مسموح فقط قبل أي زيارة — بعدها يبقى سجل الاستهلاك */
    public function delete(MaintenancePackage $package): void
    {
        if ($package->operations()->exists()) {
            throw MaintenanceException::packageInUse($package->number());
        }

        $package->delete();
    }
}
