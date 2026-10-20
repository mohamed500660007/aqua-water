<?php

namespace App\Domain\Payroll\Services;

use App\Domain\Payroll\Models\Payroll;
use App\Domain\Payroll\Models\PayrollPayment;
use App\Domain\Sales\Enums\ContractKind;
use App\Domain\Sales\Models\SalesContract;
use App\Domain\Sales\Models\SalesContractInstallment;
use App\Domain\Sales\Models\SalesContractMaintenanceItem;
use App\Domain\Users\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use RuntimeException;

class PayrollService
{
    private const MONEY_KEYS = [
        'base_salary', 'installment_total', 'installment_commission', 'device_sales_total',
        'installation_commission', 'marketing_commission', 'maintenance_commission',
        'regular_deduction', 'attendance_deduction', 'bonus',
    ];

    private const COUNT_KEYS = [
        'installment_count', 'installed_device_count', 'marketed_device_count', 'maintenance_count', 'absence_days',
    ];

    /**
     * كشف الشهر لكل موظف: القيم المحفوظة إن وُجد كشف معتمد، وإلا القيم المحسوبة تلقائيًا من حركة الشهر.
     *
     * @return Collection<int, array{user: User, payroll: Payroll|null, values: array<string, mixed>, automatic: array<string, mixed>, total: float}>
     */
    public function monthlyRows(CarbonImmutable $month, Collection $users): Collection
    {
        $from = $month->startOfMonth()->toDateString();
        $to = $month->endOfMonth()->toDateString();
        $userIds = $users->pluck('id');

        if ($userIds->isEmpty()) {
            return collect();
        }

        $installments = SalesContractInstallment::query()
            ->whereIn('collected_by_id', $userIds)
            ->whereBetween('paid_at', [$from, $to])
            ->selectRaw('collected_by_id as user_id, COUNT(*) as item_count, SUM(paid_amount) as amount_total')
            ->groupBy('collected_by_id')->get()->keyBy('user_id');

        $installedDevices = SalesContract::query()
            ->join('sales_contract_items', 'sales_contract_items.sales_contract_id', '=', 'sales_contracts.id')
            ->whereBetween('sales_contracts.installation_date', [$from, $to])
            ->whereIn('sales_contracts.installer_id', $userIds)
            ->selectRaw('sales_contracts.installer_id as user_id, SUM(sales_contract_items.quantity) as item_count')
            ->groupBy('sales_contracts.installer_id')->get()->keyBy('user_id');

        $soldDevices = SalesContract::query()
            ->join('sales_contract_items', 'sales_contract_items.sales_contract_id', '=', 'sales_contracts.id')
            ->whereBetween('sales_contracts.contract_date', [$from, $to])
            ->whereIn('sales_contracts.sales_rep_id', $userIds)
            ->selectRaw('sales_contracts.sales_rep_id as user_id, SUM(sales_contract_items.quantity) as item_count')
            ->groupBy('sales_contracts.sales_rep_id')->get()->keyBy('user_id');

        // قيمة المبيعات منفصلة عن عدد الأجهزة: الربط بالبنود يكرر إجمالي العقد مع كل بند
        $salesValue = SalesContract::query()
            ->ofKind(ContractKind::Sale)
            ->whereBetween('contract_date', [$from, $to])
            ->whereIn('sales_rep_id', $userIds)
            ->selectRaw('sales_rep_id as user_id, SUM(total) as amount_total')
            ->groupBy('sales_rep_id')->get()->keyBy('user_id');

        // عمولة الصيانة: كل شمعة غيّرها الفني في الشهر بعمولتها المحفوظة وقت الصيانة
        $maintenance = SalesContractMaintenanceItem::query()
            ->whereIn('technician_id', $userIds)
            ->whereBetween('serviced_at', [$from, $to])
            ->selectRaw('technician_id as user_id, COUNT(*) as item_count, SUM(technician_commission) as amount_total')
            ->groupBy('technician_id')->get()->keyBy('user_id');

        $saved = Payroll::query()
            ->with('payments')
            ->whereIn('user_id', $userIds)
            ->whereDate('month', $month->startOfMonth())
            ->get()
            ->keyBy('user_id');

        return $users->map(function (User $user) use (
            $installments, $installedDevices, $soldDevices, $salesValue, $maintenance, $saved
        ) {
            $automatic = [
                'base_salary' => (float) $user->base_salary,
                'installment_count' => (int) ($installments->get($user->id)?->item_count ?? 0),
                'installment_total' => (float) ($installments->get($user->id)?->amount_total ?? 0),
                'installment_commission' => (float) $user->installment_commission,
                'installed_device_count' => (int) ($installedDevices->get($user->id)?->item_count ?? 0),
                'installation_commission' => (float) $user->installation_commission,
                'marketed_device_count' => (int) ($soldDevices->get($user->id)?->item_count ?? 0),
                'device_sales_total' => (float) ($salesValue->get($user->id)?->amount_total ?? 0),
                'marketing_commission' => (float) $user->marketing_commission,
                'maintenance_count' => (int) ($maintenance->get($user->id)?->item_count ?? 0),
                'maintenance_commission' => round((float) ($maintenance->get($user->id)?->amount_total ?? 0), 2),
                'absence_days' => 0,
                'regular_deduction' => 0.0,
                'deduction_reason' => null,
                'attendance_deduction' => 0.0,
                'bonus' => 0.0,
                'note' => null,
            ];

            $payroll = $saved->get($user->id);
            $values = $this->normalize($payroll ? $payroll->only(array_keys($automatic)) : $automatic);

            return [
                'user' => $user,
                'payroll' => $payroll,
                'values' => $values,
                'automatic' => $automatic,
                'total' => $this->total($values),
            ];
        });
    }

    /** صافي المستحق: الثابت + العمولات − الخصومات + المكافأة */
    public function total(array $values): float
    {
        return round(
            (float) $values['base_salary']
            + (int) $values['installment_count'] * (float) $values['installment_commission']
            + (int) $values['installed_device_count'] * (float) $values['installation_commission']
            + (int) $values['marketed_device_count'] * (float) $values['marketing_commission']
            + (float) $values['maintenance_commission']
            + (float) $values['bonus']
            - (float) $values['regular_deduction']
            - (float) $values['attendance_deduction'],
            2,
        );
    }

    /**
     * اعتماد كشف الشهر. مبالغ التحصيل والمبيعات تُؤخذ من حركة الشهر الفعلية لا من النموذج،
     * لأنها تُطبع في قسيمة المرتب كمرجع وليست قابلة للتعديل اليدوي.
     *
     * @param  array<string, mixed>  $data
     */
    public function save(User $user, CarbonImmutable $month, array $data): Payroll
    {
        $automatic = $this->monthlyRows($month, collect([$user]))->first()['automatic'];
        $data['installment_total'] = $automatic['installment_total'];
        $data['device_sales_total'] = $automatic['device_sales_total'];

        $monthDate = $month->startOfMonth()->toDateString();
        $payroll = Payroll::query()
            ->where('user_id', $user->id)
            ->whereDate('month', $monthDate)
            ->first();

        if ($payroll && (float) $payroll->payments()->sum('amount') > $this->total($data) + 0.01) {
            throw new RuntimeException('لا يمكن أن يقل صافي المرتب عن المبلغ المصروف للموظف بالفعل.');
        }

        $payroll ??= new Payroll([
            'user_id' => $user->id,
            'month' => $monthDate,
        ]);

        $payroll->fill($data + ['total' => $this->total($data)])->save();

        return $payroll;
    }

    /** صرف المرتب على دفعة واحدة أو أكثر — لا تتجاوز الدفعات صافي المستحق */
    public function recordPayment(Payroll $payroll, float $amount, string $paidAt, ?string $note = null): PayrollPayment
    {
        if ($amount > $payroll->remainingAmount() + 0.01) {
            throw new RuntimeException('المبلغ أكبر من المتبقي للموظف ('.number_format($payroll->remainingAmount(), 2).' ج.م).');
        }

        return $payroll->payments()->create([
            'amount' => round($amount, 2),
            'paid_at' => $paidAt,
            'note' => $note,
        ]);
    }

    public function deletePayment(PayrollPayment $payment): void
    {
        $payment->delete();
    }

    /** @param  array<string, mixed>  $values */
    private function normalize(array $values): array
    {
        foreach (self::MONEY_KEYS as $key) {
            $values[$key] = (float) ($values[$key] ?? 0);
        }

        foreach (self::COUNT_KEYS as $key) {
            $values[$key] = (int) ($values[$key] ?? 0);
        }

        return $values;
    }
}
