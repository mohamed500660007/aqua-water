<?php

namespace App\Http\Controllers\Users;

use App\Domain\Users\Enums\UserStatus;
use App\Domain\Users\Models\User;
use App\Domain\Users\Services\UserService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Users\UserRequest;
use App\Support\Lookups;
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
            ->applyFilters($request->only(['status', 'role', 'department', 'branch']))
            ->applySort($request->input('sort'), $request->input('dir'))
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('pages.users.index', [
            'users' => $users,
            'stats' => $this->stats(),
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

    /** أعداد المستخدمين حسب الحالة في استعلام تجميعي واحد */
    private function stats(): array
    {
        $counts = User::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'total' => (int) $counts->sum(),
            'active' => (int) $counts->get(UserStatus::Active->value, 0),
            'on_leave' => (int) $counts->get(UserStatus::OnLeave->value, 0),
            'suspended' => (int) $counts->get(UserStatus::Suspended->value, 0),
        ];
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
        ];
    }
}
