<?php

namespace App\Http\Requests\Users;

use App\Domain\Reports\Data\ReportPeriod;
use App\Domain\Users\Enums\Gender;
use App\Domain\Users\Enums\UserStatus;
use App\Support\Lookups;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * التقرير يستقبل نفس فلاتر قائمة الموظفين + النوع، والفترة تحدد «المعيّنين خلال الفترة» فقط.
 */
class UserReportRequest extends FormRequest
{
    public const FILTERS = ['status', 'role', 'department', 'branch', 'gender'];

    public const SORTS = ['name' => 'الاسم', 'department' => 'القسم', 'hire_date' => 'تاريخ التعيين', 'base_salary' => 'المرتب الثابت', 'birth_date' => 'تاريخ الميلاد'];

    /** أجزاء التقرير التي تُطبع: كله، أو الملخص والتوزيعات فقط، أو الكشف فقط */
    public const CONTENTS = ['all' => 'التقرير كاملًا', 'summary' => 'الملخص والتوزيعات فقط', 'list' => 'كشف الموظفين فقط'];

    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(UserStatus::class)],
            'role' => ['nullable', Rule::in(Lookups::roles())],
            'department' => ['nullable', Rule::in(Lookups::departments())],
            'branch' => ['nullable', Rule::in(Lookups::branches())],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'content' => ['nullable', Rule::in(array_keys(self::CONTENTS))],
            'sort' => ['nullable', Rule::in([...array_keys(self::SORTS), 'email', 'status', 'created_at'])],
            'dir' => ['nullable', 'in:asc,desc'],
        ];
    }

    public function period(): ReportPeriod
    {
        return ReportPeriod::fromStrings($this->validated('from'), $this->validated('to'));
    }

    public function content(): string
    {
        return $this->validated('content') ?? 'all';
    }

    /** فلاتر القائمة (status/role/department/branch) + النوع الذي لا تعرفه القائمة */
    public function filters(): array
    {
        return array_filter($this->safe()->only(self::FILTERS));
    }

    /** الفلاتر + البحث + الترتيب — لرابط الرجوع إلى قائمة الموظفين */
    public function listQuery(): array
    {
        return array_filter($this->safe()->only(['status', 'role', 'department', 'branch', 'q']));
    }
}
