<?php

namespace App\Http\Controllers\Users;

use App\Domain\Users\Enums\Gender;
use App\Domain\Users\Enums\MaritalStatus;
use App\Domain\Users\Enums\MilitaryStatus;
use App\Domain\Users\Enums\UserStatus;
use App\Domain\Users\Models\User;
use App\Domain\Users\Services\UserReport;
use App\Domain\Users\Services\UserService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Users\UserReportRequest;
use App\Http\Requests\Users\UserRequest;
use App\Support\Lookups;
use App\Support\NationalId;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    private const PER_PAGE = 12;

    public function __construct(private readonly UserService $users) {}

    public function index(Request $request): View
    {
        $users = User::query()
            ->search($request->input('q'))
            ->applyFilters($request->only(['status', 'department']))
            ->applySort($request->input('sort'), $request->input('dir'))
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('pages.users.index', [
            'users' => $users,
        ] + $this->lookups());
    }

    public function report(UserReportRequest $request, UserReport $report): View
    {
        $content = $request->content();
        $users = User::query()
            ->search($request->validated('q'))
            ->applyFilters($request->filters());

        // الأجزاء غير المطلوبة للطباعة لا تُحسب أصلًا
        $withSummary = $content !== 'list';
        $rows = $content !== 'summary'
            ? $report->rows($users, $request->validated('sort'), $request->validated('dir'))
            : collect();

        return view('pages.users.report', [
            'period' => $request->period(),
            'content' => $content,
            'summary' => $report->summary($users, $request->period()),
            'byDepartment' => $withSummary ? $report->groupedBy($users, 'department') : collect(),
            'byRole' => $withSummary ? $report->groupedBy($users, 'role') : collect(),
            'byBranch' => $withSummary ? $report->groupedBy($users, 'branch') : collect(),
            'alerts' => $withSummary ? $report->alerts($users) : collect(),
            'rows' => $rows->take(UserReport::MAX_ROWS),
            'truncated' => $rows->count() > UserReport::MAX_ROWS,
            'search' => $request->validated('q'),
            'filters' => $request->filters(),
            'listQuery' => $request->listQuery(),
            'contents' => UserReportRequest::CONTENTS,
            'sorts' => UserReportRequest::SORTS,
        ] + $this->lookups());
    }

    public function create(): View
    {
        return view('pages.users.create', $this->lookups());
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $user = $this->users->create($request->toData());

        return redirect()
            ->route('users.show', $user)
            ->with('success', "تمت إضافة المستخدم «{$user->name}» بنجاح.");
    }

    public function show(User $user): View
    {
        return view('pages.users.show', ['user' => $user]);
    }

    public function edit(User $user): View
    {
        return view('pages.users.edit', ['user' => $user] + $this->lookups());
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $this->users->update($user, $request->toData());

        return redirect()
            ->route('users.show', $user)
            ->with('success', 'تم حفظ تعديلات المستخدم بنجاح.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->users->delete($user);

        return redirect()
            ->route('users.index')
            ->with('success', "تم حذف حساب «{$user->name}».");
    }

    /** قوائم الأدوار والأقسام والفروع المستخدمة في الفلترة وفي نموذج المستخدم */
    private function lookups(): array
    {
        return [
            'statuses' => UserStatus::options(),
            'defaultStatus' => UserStatus::Active->value,
            'roles' => Lookups::roles(),
            'departments' => Lookups::departments(),
            'branches' => Lookups::branches(),
            'genders' => Gender::options(),
            'maritalStatuses' => MaritalStatus::options(),
            'militaryStatuses' => MilitaryStatus::options(),
            'religions' => Lookups::religions(),
            'qualifications' => Lookups::qualifications(),
            'relations' => Lookups::relations(),
            'egyptGovernorates' => NationalId::governorates(),
        ];
    }
}
