<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Concerns\ExportsCsv;
use App\Http\Concerns\PersistsTableState;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Support\AppLauncher;
use App\Support\DataScope;
use App\Support\PermissionGroups;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RoleController extends Controller
{
    use ExportsCsv, PersistsTableState;

    protected const SORTABLE = ['name', 'guard_name', 'created_at', 'updated_at'];

    /** @var array<int>|null */
    protected ?array $allowedIds = null;

    /**
     * Permission names follow <verb>_<resource-key> (e.g.
     * "delete_any_leave_balance"), but the verb itself can be one or two
     * words (force_delete_any, restore_any, view_any, ...). Longer/more
     * specific prefixes must be checked before the shorter prefixes they
     * contain (e.g. "delete_any_" before "delete_"), otherwise a
     * multi-word resource key gets its leading word eaten by the verb.
     */
    protected const VERB_PREFIXES = [
        'force_delete_any_', 'force_delete_', 'restore_any_', 'delete_any_',
        'view_any_', 'replicate_', 'reorder_', 'restore_', 'create_', 'delete_', 'update_', 'view_',
    ];

    public function index(Request $request): View|RedirectResponse
    {
        $this->authorize('viewAny', Role::class);

        $state = $this->resolveTableState($request, 'roles', [
            'sort' => 'name', 'direction' => 'asc', 'per_page' => 25,
        ]);

        if ($state instanceof RedirectResponse) {
            return $state;
        }

        $sort = in_array($state['sort'] ?? 'name', self::SORTABLE, true) ? $state['sort'] : 'name';
        $direction = ($state['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $perPage = $this->clampPerPage($state['per_page'] ?? 25);

        $roles = $this->filteredRolesQuery($state)
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.settings.user-management.roles.index', [
            'roles' => $roles,
            'state' => $state,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Role::class);

        $state = $request->query();
        $requestedSort = $state['sort'] ?? 'name';
        $sort = in_array($requestedSort, self::SORTABLE, true) ? $requestedSort : 'name';
        $direction = ($state['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $roles = $this->filteredRolesQuery($state)->orderBy($sort, $direction)->lazy(500);

        return $this->streamCsv(
            'roles-'.now()->format('Y-m-d-His').'.csv',
            ['Name', 'Data Access', 'Guard', 'Permissions', 'Updated At'],
            (function () use ($roles) {
                foreach ($roles as $role) {
                    yield [
                        Str::headline($role->name),
                        DataScope::OPTIONS[$role->data_scope]['label'] ?? $role->data_scope,
                        $role->guard_name,
                        $role->permissions_count,
                        optional($role->updated_at)->format('Y-m-d H:i'),
                    ];
                }
            })()
        );
    }

    /**
     * @param  array<string, mixed>  $state
     */
    protected function filteredRolesQuery(array $state): Builder
    {
        return Role::query()
            ->where('company_id', Company::current()->id)
            ->withCount(['permissions' => fn ($q) => $q->whereIn('permissions.id', $this->allowedPermissionIds())])
            ->when($state['search'] ?? null, fn ($q, $search) => $q->where('name', 'like', "%{$search}%"));
    }

    public function create(): View
    {
        $this->authorize('create', Role::class);

        return view('admin.settings.user-management.roles.create', [
            'role' => new Role(),
            'permissionMatrix' => $this->permissionMatrix(),
            'matrixColumns' => $this->matrixColumns(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Role::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->where('company_id', Company::current()->id)],
            'data_scope' => ['required', Rule::in(array_keys(DataScope::OPTIONS))],
            'permissions' => ['array'],
            'permissions.*' => ['integer', Rule::in($this->allowedPermissionIds())],
        ], [], ['data_scope' => 'data access']);

        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web', 'data_scope' => $data['data_scope']]);
        $role->syncPermissions($this->permissionNames($data['permissions'] ?? []));

        return redirect()->route('admin.roles.index')->with('status', 'Role created.');
    }

    public function edit(Role $role): View
    {
        $this->authorize('update', $role);

        return view('admin.settings.user-management.roles.edit', [
            'role' => $role,
            'permissionMatrix' => $this->permissionMatrix(),
            'matrixColumns' => $this->matrixColumns(),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $this->authorize('update', $role);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->where('company_id', Company::current()->id)->ignore($role->id)],
            'data_scope' => ['required', Rule::in(array_keys(DataScope::OPTIONS))],
            'permissions' => ['array'],
            'permissions.*' => ['integer', Rule::in($this->allowedPermissionIds())],
        ], [], ['data_scope' => 'data access']);

        $role->update(['name' => $data['name'], 'data_scope' => $data['data_scope']]);

        // Permissions for apps this client hasn't been given aren't shown
        // in the form, so they're never submitted - keep them rather than
        // silently wiping them (they come back into use if the client is
        // given that app later; until then AppLauncher blocks the app).
        $hiddenIds = $role->permissions->pluck('id')->diff($this->allowedPermissionIds())->all();
        $role->syncPermissions($this->permissionNames(array_merge($data['permissions'] ?? [], $hiddenIds)));

        return redirect()->route('admin.roles.index')->with('status', 'Role updated.');
    }

    /**
     * Spatie's syncPermissions() treats a plain array as a list of
     * permission NAMES (whereIn('name', ...)), not IDs, so the raw
     * checkbox values (permission IDs) must be resolved to names first
     * or every sync silently fails to match anything.
     *
     * @param  array<int>  $permissionIds
     * @return array<string>
     */
    protected function permissionNames(array $permissionIds): array
    {
        return Permission::query()->whereIn('id', $permissionIds)->pluck('name')->all();
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('delete', $role);

        $role->delete();

        return redirect()->route('admin.roles.index')->with('status', 'Role deleted.');
    }

    /**
     * @return \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<int, Permission>>
     */
    protected function groupedPermissions()
    {
        return Permission::query()
            ->where('name', 'not like', 'page_%')
            ->orderBy('name')
            ->get()
            ->groupBy(function (Permission $permission) {
                foreach (self::VERB_PREFIXES as $prefix) {
                    if (Str::startsWith($permission->name, $prefix)) {
                        return Str::after($permission->name, $prefix);
                    }
                }

                return $permission->name;
            })
            ->filter(function ($permissions, $group) {
                $app = PermissionGroups::appFor($group);

                return $app === null || array_key_exists($app, AppLauncher::companyApps());
            })
            ->sortKeys();
    }

    /**
     * Columns of the Roles permission matrix, each mapping to one or more
     * permission verbs. A cell ticks every permission it covers at once
     * (e.g. "View" = view_any_X + view_X), so an admin thinks in "can see /
     * add / edit / delete Leave Requests", not in raw permission names.
     * "advanced" columns are only checked by a few legacy Filament
     * policies, so the form hides them unless asked.
     */
    protected const MATRIX_COLUMNS = [
        'view' => ['icon' => 'ti ti-eye', 'label' => 'View', 'verbs' => ['view_any_', 'view_'], 'advanced' => false],
        'create' => ['icon' => 'ti ti-plus', 'label' => 'Create', 'verbs' => ['create_'], 'advanced' => false],
        'update' => ['icon' => 'ti ti-pencil', 'label' => 'Edit', 'verbs' => ['update_'], 'advanced' => false],
        'delete' => ['icon' => 'ti ti-trash', 'label' => 'Delete', 'verbs' => ['delete_'], 'advanced' => false],
        'delete_any' => ['icon' => 'ti ti-trash', 'label' => 'Bulk delete', 'verbs' => ['delete_any_'], 'advanced' => true],
        'restore' => ['icon' => 'ti ti-refresh', 'label' => 'Restore', 'verbs' => ['restore_', 'restore_any_'], 'advanced' => true],
        'force_delete' => ['icon' => 'ti ti-alert-triangle', 'label' => 'Force delete', 'verbs' => ['force_delete_', 'force_delete_any_'], 'advanced' => true],
        'replicate' => ['icon' => 'ti ti-copy', 'label' => 'Replicate', 'verbs' => ['replicate_'], 'advanced' => true],
        'reorder' => ['icon' => 'ti ti-arrows-sort', 'label' => 'Reorder', 'verbs' => ['reorder_'], 'advanced' => true],
    ];

    /**
     * The permission matrix, one tab per app this client has been given
     * (app-launcher order), each split into categories of module rows.
     * An app the client doesn't have simply has no tab. A group this
     * class can't map to an app yet (PermissionGroups::appFor() null)
     * lands in an "Other" tab rather than disappearing.
     *
     * @return array<string, array{label: string, icon: string, total: int, categories: array<string, array<int, array{key: string, label: string, cells: array<string, array<int>>}>>}>
     */
    protected function permissionMatrix(): array
    {
        $apps = [];

        foreach (AppLauncher::companyApps() as $key => $app) {
            $apps[$key] = ['label' => $app['label'], 'icon' => $app['icon'], 'total' => 0, 'rows' => []];
        }

        $groups = $this->groupedPermissions()
            ->sortBy(fn ($permissions, $group) => PermissionGroups::sortPosition($group));

        foreach ($groups as $group => $permissions) {
            $appKey = PermissionGroups::appFor($group) ?? 'other';
            $apps[$appKey] ??= ['label' => 'Other', 'icon' => 'ti ti-dots', 'total' => 0, 'rows' => []];

            $byName = $permissions->pluck('id', 'name');
            $cells = [];

            foreach (self::MATRIX_COLUMNS as $column => $definition) {
                $ids = collect($definition['verbs'])
                    ->map(fn ($verb) => $byName[$verb.$group] ?? null)
                    ->filter()
                    ->values()
                    ->all();

                if ($ids) {
                    $cells[$column] = $ids;
                }
            }

            $apps[$appKey]['rows'][] = [
                'key' => $group,
                'label' => PermissionGroups::label($group),
                'category' => PermissionGroups::category($group),
                'cells' => $cells,
            ];
            $apps[$appKey]['total'] += $permissions->count();
        }

        // page_* permissions (General Settings, Working Hours) are plain
        // "can open this page" switches - shown as View-only rows.
        foreach ($this->pagePermissions() as $permission) {
            $apps['settings']['rows'][] = [
                'key' => $permission->name,
                'label' => Str::headline(Str::after($permission->name, 'page_')),
                'category' => 'Settings Pages',
                'cells' => ['view' => [$permission->id]],
            ];
            $apps['settings']['total']++;
        }

        foreach ($apps as $key => $app) {
            $apps[$key]['categories'] = collect($app['rows'])->groupBy('category')->map->all()->all();
            unset($apps[$key]['rows']);
        }

        return array_filter($apps, fn ($app) => $app['total'] > 0);
    }

    /**
     * @return array<string, array{label: string, icon: string, advanced: bool}>
     */
    protected function matrixColumns(): array
    {
        return array_map(fn ($column) => ['label' => $column['label'], 'icon' => $column['icon'], 'advanced' => $column['advanced']], self::MATRIX_COLUMNS);
    }

    /**
     * page_* permissions are Settings pages (General Settings, Working
     * Hours), so they're only offered when this client has Settings.
     */
    protected function pagePermissions()
    {
        if (! array_key_exists('settings', AppLauncher::companyApps())) {
            return collect();
        }

        return Permission::query()->where('name', 'like', 'page_%')->orderBy('name')->get();
    }

    /**
     * Every permission this client's roles may be given - exactly the ones
     * the Roles form shows.
     *
     * @return array<int>
     */
    protected function allowedPermissionIds(): array
    {
        return $this->allowedIds ??= $this->groupedPermissions()->flatten()
            ->merge($this->pagePermissions())
            ->pluck('id')
            ->all();
    }
}
