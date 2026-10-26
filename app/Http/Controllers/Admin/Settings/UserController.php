<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Concerns\ExportsCsv;
use App\Http\Concerns\PersistsTableState;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\User\StoreUserRequest;
use App\Http\Requests\Admin\User\UpdateUserRequest;
use App\Mail\UserInvitationMail;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use App\Support\AppLauncher;
use App\Support\BranchContext;
use App\Support\Mail\CompanyMailer;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    use ExportsCsv, PersistsTableState;

    protected const SORTABLE = [
        'name', 'email', 'employee_code', 'phone', 'department',
        'is_active', 'is_super_admin', 'created_at', 'updated_at',
    ];

    public function index(Request $request): View|RedirectResponse
    {
        $this->authorize('viewAny', User::class);

        $state = $this->resolveTableState($request, 'users', [
            'sort' => 'name', 'direction' => 'asc', 'per_page' => 25,
        ]);

        if ($state instanceof RedirectResponse) {
            return $state;
        }

        $sort = in_array($state['sort'] ?? 'name', self::SORTABLE, true) ? $state['sort'] : 'name';
        $direction = ($state['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $perPage = $this->clampPerPage($state['per_page'] ?? 25);

        $users = $this->filteredUsersQuery($state)
            ->with('branches')
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        // Status-tab counts ignore the status filters themselves (so every
        // tab shows what clicking it would return) but respect the rest.
        $statusCounts = $this->filteredUsersQuery(array_diff_key($state, array_flip(['is_active', 'is_super_admin'])))
            ->toBase()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active')
            ->selectRaw('SUM(CASE WHEN is_super_admin = 1 THEN 1 ELSE 0 END) as super_admins')
            ->first();

        // Headline KPIs are company/branch-wide, unaffected by list filters.
        $kpis = $this->filteredUsersQuery([])
            ->toBase()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active')
            ->selectRaw('SUM(CASE WHEN is_super_admin = 1 THEN 1 ELSE 0 END) as super_admins')
            ->selectRaw('SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as joined_this_month', [now()->startOfMonth()])
            ->first();

        $roles = $this->companyRoles();
        $departments = User::query()->where('company_id', Company::current()->id)->whereNotNull('department')->distinct()->orderBy('department')->pluck('department', 'department');

        return view('admin.settings.user-management.users.index', [
            'company' => Company::current(),
            'users' => $users,
            'roles' => $roles,
            'departments' => $departments,
            'state' => $state,
            'kpis' => array_map('intval', (array) $kpis),
            'statusCounts' => [
                'all' => (int) $statusCounts->total,
                'active' => (int) $statusCounts->active,
                'inactive' => (int) $statusCounts->total - (int) $statusCounts->active,
                'super_admins' => (int) $statusCounts->super_admins,
            ],
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', User::class);

        $state = $request->query();
        $requestedSort = $state['sort'] ?? 'name';
        $sort = in_array($requestedSort, self::SORTABLE, true) ? $requestedSort : 'name';
        $direction = ($state['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $users = $this->filteredUsersQuery($state)->orderBy($sort, $direction)->lazy(500);

        return $this->streamCsv(
            'users-'.now()->format('Y-m-d-His').'.csv',
            ['Name', 'Email', 'Employee Code', 'Phone', 'Department', 'Designation', 'Roles', 'Active', 'Super Admin', 'Joined At'],
            (function () use ($users) {
                foreach ($users as $user) {
                    yield [
                        $user->name,
                        $user->email,
                        $user->employee_code,
                        $user->phone,
                        $user->department,
                        $user->designation,
                        $user->roles->pluck('name')->implode(', '),
                        $user->is_active ? 'Yes' : 'No',
                        $user->is_super_admin ? 'Yes' : 'No',
                        optional($user->created_at)->format('Y-m-d H:i'),
                    ];
                }
            })()
        );
    }

    /**
     * @param  array<string, mixed>  $state
     */
    protected function filteredUsersQuery(array $state): Builder
    {
        return User::query()
            ->where('company_id', Company::current()->id)
            ->with('roles')
            ->when(BranchContext::isRestricted(), fn ($q) => BranchContext::scopeUsersToBranch(
                $q, BranchContext::currentBranchId(), Company::current()->id
            ))
            ->when($state['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when(($state['is_active'] ?? '') !== '', fn ($q) => $q->where('is_active', $state['is_active']))
            ->when(($state['is_super_admin'] ?? '') !== '', fn ($q) => $q->where('is_super_admin', $state['is_super_admin']))
            ->when($state['role'] ?? null, fn ($q, $roleId) => $q->whereHas('roles', fn ($q2) => $q2->where('roles.id', $roleId)))
            ->when($state['department'] ?? null, fn ($q, $department) => $q->where('department', $department))
            ->when($state['joined_from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($state['joined_until'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
    }

    public function create(): View|RedirectResponse
    {
        $this->authorize('create', User::class);

        // Checked again on save (User model) - this just saves filling in
        // a whole form only to be told there's no room for it.
        if (Company::current()->userLimitReached()) {
            return redirect()->route('admin.users.index')
                ->withErrors(['user_limit' => 'Your company has reached its limit of '.Company::current()->max_users.' active users. Deactivate an existing user, or ask your OneSuite provider to raise the limit.']);
        }

        return view('admin.settings.user-management.users.create', [
            'user' => new User(),
            'roles' => $this->companyRoles(),
            'branches' => $this->branchOptions(),
            'apps' => AppLauncher::companyApps(),
            'employees' => $this->employeeOptions(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['roles', 'branches', 'avatar', 'app_access', 'default_app', 'employee_id']);
        $data['company_id'] = Company::current()->id;
        $data['is_active'] = $request->boolean('is_active');
        $data['is_super_admin'] = $request->boolean('is_super_admin');
        // No one types this - it's unknown and unusable until they set
        // their own via the invite link below, same as a platform-created
        // client's first admin (see ClientController::store()).
        $data['password'] = Str::random(40);
        $data = array_merge($data, $this->resolveAppAccess($request));

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user = User::create($data);

        if (! $user->is_super_admin) {
            // Submitted as strings; Spatie treats a numeric string as a role NAME, an int as an ID.
            $user->syncRoles(array_map('intval', $request->input('roles', [])));
        }

        $user->branches()->sync($this->resolveBranchIds($request));
        $this->linkEmployee($user, $request->integer('employee_id') ?: null);

        $acceptUrl = URL::temporarySignedRoute('invitation.accept', now()->addDays(7), ['user' => $user->id]);

        CompanyMailer::for(Company::current())->to($user)->send(new UserInvitationMail($user, $acceptUrl));

        return redirect()->route('admin.users.index')
            ->with('status', "User created. An invite was sent to {$user->email}.")
            ->with('invite_url', $acceptUrl)
            ->with('invite_email', $user->email);
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('admin.settings.user-management.users.edit', [
            'user' => $user,
            'roles' => $this->companyRoles(),
            'branches' => $this->branchOptions(),
            'apps' => AppLauncher::companyApps(),
            'employees' => $this->employeeOptions($user),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->safe()->except(['roles', 'branches', 'avatar', 'password', 'app_access', 'default_app', 'employee_id']);
        $data['is_active'] = $request->boolean('is_active');
        $data['is_super_admin'] = $request->boolean('is_super_admin');
        $data = array_merge($data, $this->resolveAppAccess($request));

        if (filled($request->input('password'))) {
            $data['password'] = $request->input('password');
        }

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->fill($data);
        $user->save();

        if (! $user->is_super_admin) {
            // Submitted as strings; Spatie treats a numeric string as a role NAME, an int as an ID.
            $user->syncRoles(array_map('intval', $request->input('roles', [])));
        }

        $user->branches()->sync($this->resolveBranchIds($request));
        $this->linkEmployee($user, $request->integer('employee_id') ?: null);

        return redirect()->route('admin.users.index')->with('status', 'User updated.');
    }

    /**
     * Employees the "Employee" picker on the user form offers: everyone in
     * this company (and branch, for a branch-restricted admin) who has no
     * login yet, plus - always - the one this user is already linked to, so
     * saving the form can never drop an existing link just because that
     * employee wasn't on the list.
     */
    protected function employeeOptions(?User $user = null): Collection
    {
        $available = Employee::query()
            ->where('company_id', Company::current()->id)
            ->whereNull('user_id')
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->with('department:id,name')
            ->orderBy('name')
            ->get();

        $linked = $user?->employee?->load('department:id,name');

        return collect([$linked])->filter()->merge($available)->unique('id')->values();
    }

    /**
     * One login belongs to at most one employee record (employees.user_id).
     * Linking is what turns on that person's My/Team self-service screens
     * and gives a "team" or "own" role someone to measure against.
     */
    protected function linkEmployee(User $user, ?int $employeeId): void
    {
        Employee::query()
            ->where('company_id', $user->company_id)
            ->where('user_id', $user->id)
            ->when($employeeId, fn ($q) => $q->whereKeyNot($employeeId))
            ->update(['user_id' => null]);

        if ($employeeId) {
            Employee::query()->where('company_id', $user->company_id)->whereKey($employeeId)->update(['user_id' => $user->id]);
        }
    }

    /**
     * Roles belong to exactly one client (see config/permission.php's
     * "teams" feature) - never offer another company's roles in this
     * dropdown.
     */
    protected function companyRoles()
    {
        return Role::where('company_id', Company::current()->id)->orderBy('name')->get();
    }

    /**
     * Branches this user is allowed to assign - the same set the sidebar
     * switcher offers them (their own assignments, or every company branch
     * for a super admin, who has none of their own).
     */
    protected function branchOptions()
    {
        return BranchContext::switchableBranches()->pluck('name', 'id');
    }

    /**
     * The submitted branch selection, already validated (by the form
     * request) to be a subset of what this admin is allowed to assign.
     */
    protected function resolveBranchIds(Request $request): array
    {
        return array_map('intval', $request->input('branches', []));
    }

    /**
     * app_access is stored as null when every app this client has is
     * checked - keeps the "no restriction" meaning intact (the client's
     * own app list still limits it in AppLauncher::hasAccess()) instead of
     * freezing today's list into every user's row. default_app only keeps
     * a value that's actually still within the resolved access set.
     */
    protected function resolveAppAccess(Request $request): array
    {
        $selected = $request->input('app_access', []);
        $allKeys = array_keys(AppLauncher::companyApps());
        $isUnrestricted = empty(array_diff($allKeys, $selected));

        $appAccess = $isUnrestricted ? null : array_values($selected);
        $defaultApp = $request->input('default_app');

        if (! $defaultApp || ($appAccess !== null && ! in_array($defaultApp, $appAccess, true))) {
            $defaultApp = AppLauncher::preferredKey($appAccess ?? $allKeys);
        }

        return ['app_access' => $appAccess, 'default_app' => $defaultApp];
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        abort_if($user->is(auth()->user()), 403, "You can't delete your own account.");

        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'User deleted.');
    }
}
