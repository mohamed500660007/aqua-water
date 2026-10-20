<?php

namespace App\Http\Controllers;

use App\Domain\Complaints\Models\Complaint;
use App\Domain\Customers\Models\Customer;
use App\Domain\Sales\Models\SalesContract;
use App\Domain\Sales\Models\SalesContractInstallment;
use App\Domain\Sales\Models\SalesContractMaintenanceItem;
use App\Support\Lookups;
use Illuminate\Contracts\View\View;

/**
 * الصفحة الرئيسية: ترحيب + كل أقسام اللوحة، مع مؤشرات سريعة تُعرض على بطاقات الأقسام نفسها.
 */
class HomeController extends Controller
{
    public function __invoke(): View
    {
        // كل مؤشر = استعلام COUNT واحد، مفهرس بمسار القسم الذي يفتحه
        $stats = [
            'customers.index' => [
                'count' => Customer::query()->count(),
                'label' => 'عميل',
                'tone' => 'blue',
            ],
            'contracts.index' => [
                'count' => SalesContract::query()->count(),
                'label' => 'عقد',
                'tone' => 'purple',
            ],
            'installments.index' => [
                'count' => SalesContractInstallment::query()->whereHas('contract')->overdue()->count(),
                'label' => 'قسط متأخر',
                'tone' => 'red',
                'href' => route('installments.index', ['status' => 'overdue']),
            ],
            'maintenance.file' => [
                'count' => SalesContractMaintenanceItem::query()->whereHas('contract')->pending()
                    ->whereDate('replacement_date', '<=', today())->count(),
                'label' => 'شمعة مستحقة',
                'tone' => 'green',
            ],
            'complaints.index' => [
                'count' => Complaint::query()->where('resolved', false)->count(),
                'label' => 'شكوى مفتوحة',
                'tone' => 'amber',
                'href' => route('complaints.index', ['resolved' => '0']),
            ],
        ];

        return view('pages.home', [
            'navGroups' => Lookups::navGroups(),
            'stats' => $stats,
        ]);
    }
}
