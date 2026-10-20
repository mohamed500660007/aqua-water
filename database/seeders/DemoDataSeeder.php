<?php

namespace Database\Seeders;

use App\Domain\Complaints\Data\ComplaintData;
use App\Domain\Complaints\Data\ComplaintPartData;
use App\Domain\Complaints\Services\ComplaintService;
use App\Domain\Customers\Data\CustomerData;
use App\Domain\Customers\Enums\CustomerOrigin;
use App\Domain\Customers\Enums\CustomerStatus;
use App\Domain\Customers\Models\Customer;
use App\Domain\Customers\Services\CustomerService;
use App\Domain\Gifts\Data\GiftData;
use App\Domain\Gifts\Services\GiftService;
use App\Domain\Inventory\Data\EmployeeHandoverData;
use App\Domain\Inventory\Data\ProductQuantityData;
use App\Domain\Inventory\Data\PurchaseOrderData;
use App\Domain\Inventory\Data\PurchaseOrderItemData;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Inventory\Services\EmployeeStockService;
use App\Domain\Inventory\Services\PurchaseOrderService;
use App\Domain\Maintenance\Models\MaintenancePlan;
use App\Domain\Payroll\Services\PayrollService;
use App\Domain\Products\Enums\ProductKind;
use App\Domain\Products\Models\Product;
use App\Domain\Sales\Data\CollectInstallmentData;
use App\Domain\Sales\Data\SalesContractData;
use App\Domain\Sales\Data\SalesContractItemData;
use App\Domain\Sales\Enums\ContractKind;
use App\Domain\Sales\Enums\ContractType;
use App\Domain\Sales\Enums\StockSource;
use App\Domain\Sales\Models\SalesContract;
use App\Domain\Sales\Models\SalesContractInstallment;
use App\Domain\Sales\Services\SalesContractService;
use App\Domain\Users\Enums\UserStatus;
use App\Domain\Users\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * بيانات تجريبية متكاملة لعرض النظام والتقارير: عملاء داخليون وخارجيون، طلبيات، عقود بيع وصيانة
 * بأقساط محصّلة ومتأخرة ومحوّلة للقانونية، شكاوى، هدايا، ومرتبات بدفعات صرف.
 *
 * كل شيء يمر عبر خدمات المجال نفسها حتى تبقى أرصدة المخزون والأقساط والمرتبات متسقة.
 * لا تعتمد على Faker لأن بيئة الإنتاج تُثبَّت بدون حزم التطوير.
 *
 * التشغيل مرة واحدة: php artisan db:seed --class=DemoDataSeeder --force
 * (يتوقف تلقائيًا إن وُجدت عقود مسبقًا حتى لا يكرر البيانات).
 */
class DemoDataSeeder extends Seeder
{
    private const FIRST_NAMES = ['محمد', 'أحمد', 'محمود', 'مصطفى', 'إبراهيم', 'علي', 'حسن', 'خالد', 'عمرو', 'ياسر',
        'سامح', 'طارق', 'وليد', 'هاني', 'أيمن', 'شريف', 'منى', 'سارة', 'هبة', 'نورا', 'إيمان', 'رحاب', 'دعاء', 'أسماء', 'مروة', 'ريهام'];

    private const LAST_NAMES = ['عبد الله', 'السيد', 'عبد الرحمن', 'الشناوي', 'فتحي', 'منصور', 'عبد العزيز', 'سليمان',
        'الجمال', 'حسانين', 'البنا', 'زكي', 'رمضان', 'عثمان', 'شحاتة', 'النجار', 'عبد الحميد', 'الدسوقي'];

    private const PLACES = [
        ['الدقهلية', 'المنصورة', 'ميت حدر'], ['الدقهلية', 'طلخا', 'ميت عنتر'], ['الدقهلية', 'ميت غمر', 'دقادوس'],
        ['الغربية', 'طنطا', 'سبرباي'], ['الغربية', 'المحلة الكبرى', 'محلة أبو علي'], ['الشرقية', 'الزقازيق', 'القنايات'],
        ['القليوبية', 'بنها', 'كفر سعد'], ['القاهرة', 'مدينة نصر', 'الحي العاشر'], ['الجيزة', 'الهرم', 'كفر الجبل'],
    ];

    private CarbonImmutable $today;

    public function __construct(
        private readonly CustomerService $customers,
        private readonly PurchaseOrderService $purchaseOrders,
        private readonly EmployeeStockService $employeeStock,
        private readonly SalesContractService $contracts,
        private readonly ComplaintService $complaints,
        private readonly GiftService $gifts,
        private readonly PayrollService $payroll,
    ) {}

    public function run(): void
    {
        // A dedicated demo account is the marker. Existing real contracts must
        // not prevent adding the demo dataset, while repeat runs remain safe.
        if (User::withTrashed()->where('email', 'demo.staff1@aquawater.eg')->exists()) {
            $this->command?->warn('توجد عقود بالفعل — تم تخطي البيانات التجريبية حتى لا تتكرر.');

            return;
        }

        mt_srand(2026);
        $this->today = CarbonImmutable::today();

        // كل البيانات في معاملة واحدة: أي فشل في المنتصف لا يترك بيانات تجريبية ناقصة
        DB::transaction(fn () => $this->seedAll());

        $this->command?->info('تمت إضافة البيانات التجريبية بنجاح.');
    }

    private function seedAll(): void
    {
        $warehouse = Warehouse::query()->orderBy('id')->firstOrFail();
        $staff = $this->staff();
        $products = Product::query()->get()->groupBy(fn (Product $product) => $product->kind->value);

        $this->purchaseOrders($warehouse, $products);
        $this->handOvers($warehouse, $staff, $products);

        $customers = $this->createCustomers();
        $this->saleContracts($warehouse, $staff, $products, $customers->where('origin', CustomerOrigin::Internal));
        $this->maintenanceContracts($warehouse, $staff, $customers);
        $this->collectInstallments($staff);
        $this->referToLegal();
        $this->complaintsAndGifts($warehouse, $staff, $products, $customers);
        $this->payrolls();
    }

    /** @return array<string, Collection<int, User>> الموظفون حسب الدور */
    private function staff(): array
    {
        $employees = [
            ['name' => 'شيماء عبد الله', 'role' => 'مندوبة', 'department' => 'المبيعات', 'salary' => 4000],
            ['name' => 'رحاب السيد', 'role' => 'مندوبة', 'department' => 'المبيعات', 'salary' => 4000],
            ['name' => 'أحمد فتحي', 'role' => 'مندوب مبيعات', 'department' => 'المبيعات', 'salary' => 4500],
            ['name' => 'محمد رمضان', 'role' => 'فني تركيب', 'department' => 'الفنيين', 'salary' => 5000],
            ['name' => 'حسن عثمان', 'role' => 'فني تركيب', 'department' => 'الفنيين', 'salary' => 5000],
            ['name' => 'إبراهيم النجار', 'role' => 'محصل', 'department' => 'المحصّلين', 'salary' => 3500],
            ['name' => 'سامح زكي', 'role' => 'محصل', 'department' => 'المحصّلين', 'salary' => 3500],
            ['name' => 'دعاء شحاتة', 'role' => 'سكرتارية', 'department' => 'السكرتارية', 'salary' => 3800],
            ['name' => 'أسماء البنا', 'role' => 'خدمة عملاء', 'department' => 'الجودة', 'salary' => 3600],
            ['name' => 'ياسر الدسوقي', 'role' => 'محاسب', 'department' => 'الحسابات', 'salary' => 6000],
            ['name' => 'هاني سليمان', 'role' => 'سكرتارية', 'department' => 'الأرشيف', 'salary' => 3200],
        ];

        foreach ($employees as $index => $employee) {
            User::updateOrCreate(['email' => 'demo.staff'.($index + 1).'@aquawater.eg'], [
                'name' => $employee['name'],
                'phone' => '0100'.str_pad((string) (7000000 + $index), 7, '0', STR_PAD_LEFT),
                'role' => $employee['role'],
                'department' => $employee['department'],
                'branch' => 'الدقهلية',
                'status' => UserStatus::Active,
                'base_salary' => $employee['salary'],
                'installment_commission' => 10,
                'installation_commission' => 150,
                'marketing_commission' => 200,
                'maintenance_commission' => 25,
                'created_at' => $this->today->subYear(),
            ]);
        }

        // الموظفون الموجودون من البذور الأساسية يحصلون على مرتب حتى يظهروا في تقارير المرتبات
        User::query()->where('base_salary', 0)->update(['base_salary' => 4500, 'created_at' => $this->today->subYear()]);

        $byRole = fn (array $roles) => User::query()->where('status', UserStatus::Active)->whereIn('role', $roles)->get();

        return [
            'reps' => $byRole(['مندوب مبيعات', 'مندوبة']),
            'installers' => $byRole(['فني تركيب']),
            'collectors' => $byRole(['محصل']),
        ];
    }

    private function purchaseOrders(Warehouse $warehouse, Collection $products): void
    {
        $suppliers = ['شركة النيل لأنظمة المياه', 'المصرية للفلاتر', 'أكوا تك للاستيراد'];

        // طلبية كل شهرين تقريبًا
        for ($monthsAgo = 9; $monthsAgo >= 1; $monthsAgo -= 2) {
            $date = $this->today->subMonths($monthsAgo)->startOfMonth()->addDays(mt_rand(1, 6));

            if ($date->gt($this->today)) {
                $date = $this->today;
            }

            // سعر الشراء = تكلفة المنتج، أو 60% من سعر البيع إن لم تُسجَّل تكلفة
            $line = fn ($p, int $quantity) => new PurchaseOrderItemData($p->id, $quantity, round((float) ($p->cost ?? $p->price * 0.6), 2));
            $items = [
                ...$products->get(ProductKind::Device->value, collect())->map(fn ($p) => $line($p, mt_rand(16, 22))),
                ...$products->get(ProductKind::Cartridge->value, collect())->map(fn ($p) => $line($p, mt_rand(80, 140))),
                ...$products->get(ProductKind::Other->value, collect())->map(fn ($p) => $line($p, mt_rand(5, 15))),
            ];

            $this->purchaseOrders->create(new PurchaseOrderData(
                warehouseId: $warehouse->id,
                orderDate: $date->toDateString(),
                items: $items,
                supplier: $suppliers[$monthsAgo % count($suppliers)],
                note: 'طلبية دورية',
            ));
        }
    }

    private function handOvers(Warehouse $warehouse, array $staff, Collection $products): void
    {
        $cartridges = $products->get(ProductKind::Cartridge->value, collect());

        foreach ($staff['installers']->concat($staff['reps'])->take(4) as $employee) {
            $this->employeeStock->handOverMany(new EmployeeHandoverData(
                userId: $employee->id,
                warehouseId: $warehouse->id,
                items: $cartridges->map(fn ($p) => new ProductQuantityData($p->id, mt_rand(10, 25)))->values()->all(),
                movedAt: $this->today->subDays(mt_rand(5, 40))->toDateString(),
                note: 'عهدة شمع للزيارات',
            ));
        }
    }

    /** @return Collection<int, Customer> */
    private function createCustomers(): Collection
    {
        $created = collect();

        for ($i = 0; $i < 220; $i++) {
            [$governorate, $center, $village] = self::PLACES[$i % count(self::PLACES)];
            $name = $this->pick(self::FIRST_NAMES).' '.$this->pick(self::FIRST_NAMES).' '.$this->pick(self::LAST_NAMES);

            $created->push($this->customers->create(new CustomerData(
                name: $name,
                status: CustomerStatus::Active,
                registeredAt: $this->today->subDays(mt_rand(30, 290))->toDateString(),
                phones: ['01'.$this->pick(['0', '1', '2', '5']).str_pad((string) mt_rand(10000000, 99999999), 8, '0', STR_PAD_LEFT)],
                // خُمس العملاء خارجيون: أجهزتهم من شركات أخرى ويتعاملون معنا في الصيانة فقط
                origin: $i % 5 === 4 ? CustomerOrigin::External : CustomerOrigin::Internal,
                governorate: $governorate,
                center: $center,
                village: $village,
                detailedAddress: 'شارع '.$this->pick(['الجيش', 'المدارس', 'الجمهورية', 'البحر', 'المحطة']).' — بجوار '.$this->pick(['المسجد', 'الصيدلية', 'المدرسة', 'مكتب البريد']),
            )));
        }

        return $created;
    }

    private function saleContracts(Warehouse $warehouse, array $staff, Collection $products, Collection $customers): void
    {
        $devices = $products->get(ProductKind::Device->value, collect());
        $cartridges = $products->get(ProductKind::Cartridge->value, collect());
        $planIds = MaintenancePlan::query()->pluck('id')->all();

        foreach ($customers->values() as $index => $customer) {
            $contractDate = $this->today->subDays(mt_rand(5, 280));
            $device = $devices->random();
            $isInstallment = mt_rand(1, 100) <= 65;
            $items = [new SalesContractItemData($device->id, 1)];

            if (mt_rand(0, 1) === 1 && $cartridges->isNotEmpty()) {
                $items[] = new SalesContractItemData($cartridges->random()->id, mt_rand(2, 4));
            }

            $price = (float) $device->price;

            $this->contracts->create(new SalesContractData(
                customerId: $customer->id,
                warehouseId: $warehouse->id,
                contractDate: $contractDate->toDateString(),
                installationDate: $contractDate->addDays(mt_rand(0, 3))->min($this->today)->toDateString(),
                maintenanceDate: $contractDate->addMonths(3)->toDateString(),
                type: $isInstallment ? ContractType::Installment : ContractType::Cash,
                stockSource: StockSource::Warehouse,
                items: $items,
                discount: mt_rand(0, 3) * 100,
                paid: $isInstallment ? round($price * mt_rand(20, 35) / 100, -1) : 999999,
                maintenancePlanId: $planIds && mt_rand(0, 1) ? $this->pick($planIds) : null,
                salesRepId: $staff['reps']->random()->id,
                installerId: $staff['installers']->random()->id,
                installmentsCount: $isInstallment ? $this->pick([6, 8, 10, 12]) : null,
                installmentPeriodMonths: $isInstallment ? 1 : null,
                firstDueDate: $isInstallment ? $contractDate->addMonth()->toDateString() : null,
            ));
        }
    }

    private function maintenanceContracts(Warehouse $warehouse, array $staff, Collection $customers): void
    {
        $planIds = MaintenancePlan::query()->pluck('id')->all();
        $targets = $customers->where('origin', CustomerOrigin::External)
            ->concat($customers->where('origin', CustomerOrigin::Internal)->take(10));

        foreach ($targets->values() as $customer) {
            $contractDate = $this->today->subDays(mt_rand(5, 240));
            $isInstallment = mt_rand(0, 1) === 1;
            $price = (float) $this->pick([600, 900, 1200, 1500]);

            $this->contracts->create(new SalesContractData(
                customerId: $customer->id,
                warehouseId: $warehouse->id,
                contractDate: $contractDate->toDateString(),
                installationDate: null,
                maintenanceDate: $contractDate->addMonth()->toDateString(),
                type: $isInstallment ? ContractType::Installment : ContractType::Cash,
                stockSource: StockSource::Warehouse,
                items: [],
                paid: $isInstallment ? 200 : $price,
                maintenancePlanId: $planIds ? $this->pick($planIds) : null,
                salesRepId: $staff['reps']->random()->id,
                installmentsCount: $isInstallment ? 4 : null,
                installmentPeriodMonths: $isInstallment ? 1 : null,
                firstDueDate: $isInstallment ? $contractDate->addMonth()->toDateString() : null,
                kind: ContractKind::Maintenance,
                price: $price,
            ));
        }
    }

    /**
     * معظم الأقساط المستحقة تُحصَّل قرب موعدها، وبعضها جزئيًا، ونحو خُمسها يبقى متأخرًا —
     * حتى تظهر تقارير المتأخرات ومن أنهى السداد ببيانات واقعية.
     */
    private function collectInstallments(array $staff): void
    {
        $lateContracts = SalesContract::query()->where('type', ContractType::Installment->value)
            ->inRandomOrder()->limit(18)->pluck('id')->flip();

        $due = SalesContractInstallment::query()
            ->with('contract')
            ->whereNull('paid_at')
            ->where('due_date', '<', $this->today)
            ->orderBy('due_date')
            ->get();

        foreach ($due as $installment) {
            if ($lateContracts->has($installment->sales_contract_id) && $installment->due_date->gt($this->today->subMonths(4))) {
                continue;
            }

            $installment->refresh();

            if ($installment->paid_at) {
                continue;
            }

            $paidAt = CarbonImmutable::parse($installment->due_date)->addDays(mt_rand(0, 9))->min($this->today);
            $amount = (float) $installment->amount;

            if (mt_rand(1, 100) <= 8) {
                $amount = round($amount / 2, 2);
            }

            $this->contracts->collectInstallment($installment, new CollectInstallmentData(
                amount: $amount,
                paidAt: $paidAt->toDateString(),
                collectedById: $staff['collectors']->random()->id,
            ));
        }
    }

    private function referToLegal(): void
    {
        SalesContract::query()
            ->whereHas('installments', fn ($query) => $query->overdue())
            ->inRandomOrder()
            ->limit(5)
            ->get()
            ->each(fn (SalesContract $contract) => $this->contracts->referToLegal(
                $contract,
                $this->today->subDays(mt_rand(1, 25))->toDateString(),
            ));
    }

    private function complaintsAndGifts(Warehouse $warehouse, array $staff, Collection $products, Collection $customers): void
    {
        $parts = $products->get(ProductKind::Other->value, collect())->concat($products->get(ProductKind::Cartridge->value, collect()));
        $contracts = SalesContract::query()->get(['id', 'contract_date']);
        $notes = ['تسريب مياه من الوصلات', 'ضعف ضغط المياه', 'صوت عالي في المضخة', 'طعم المياه متغير', 'الحنفية لا تغلق جيدًا'];

        for ($i = 0; $i < 30; $i++) {
            $contract = $contracts->random();
            $withPart = mt_rand(0, 1) === 1;
            $part = $parts->random();

            $this->complaints->create(new ComplaintData(
                contractId: $contract->id,
                complaintDate: CarbonImmutable::parse($contract->contract_date)->addDays(mt_rand(10, 150))->min($this->today)->toDateString(),
                visitFee: (float) $this->pick([0, 50, 100]),
                resolved: mt_rand(1, 100) <= 75,
                parts: $withPart ? [new ComplaintPartData($part->id, StockSource::Warehouse, 1, (float) $part->price, $warehouse->id)] : [],
                technicianId: $staff['installers']->random()->id,
                note: $this->pick($notes),
            ));
        }

        $cartridges = $products->get(ProductKind::Cartridge->value, collect());

        foreach ($customers->random(15) as $customer) {
            $this->gifts->create(new GiftData(
                customerId: $customer->id,
                productId: $cartridges->random()->id,
                stockSource: StockSource::Warehouse,
                quantity: 1,
                giftDate: $this->today->subDays(mt_rand(1, 120))->toDateString(),
                technicianId: $staff['installers']->random()->id,
                warehouseId: $warehouse->id,
                note: 'هدية تجديد عقد',
            ));
        }
    }

    /** كشوف الشهور الثلاثة الماضية معتمدة ومصروفة، والشهر السابق مصروف جزئيًا لبعض الموظفين */
    private function payrolls(): void
    {
        $users = User::query()->where('status', '!=', UserStatus::Suspended->value)->orderBy('name')->get();

        foreach ([3, 2, 1] as $monthsAgo) {
            $month = $this->today->subMonths($monthsAgo)->startOfMonth();

            foreach ($this->payroll->monthlyRows($month, $users) as $row) {
                $values = $row['automatic'];
                $absence = mt_rand(1, 100) <= 30 ? mt_rand(1, 3) : 0;
                $values['absence_days'] = $absence;
                $values['attendance_deduction'] = $absence * round($values['base_salary'] / 30);
                $values['bonus'] = mt_rand(1, 100) <= 20 ? (float) $this->pick([200, 300, 500]) : 0.0;

                unset($values['installment_total'], $values['device_sales_total']);

                $payroll = $this->payroll->save($row['user'], $month, $values);
                $payDate = $month->addMonth()->addDays(mt_rand(0, 4))->min($this->today)->toDateString();

                if ($monthsAgo > 1 || mt_rand(1, 100) <= 55) {
                    $this->payroll->recordPayment($payroll, (float) $payroll->total, $payDate);
                } elseif (mt_rand(0, 1) === 1) {
                    $this->payroll->recordPayment($payroll, round((float) $payroll->total / 2), $payDate, 'دفعة أولى');
                }
            }
        }
    }

    private function pick(array $values): mixed
    {
        return $values[array_rand($values)];
    }
}
