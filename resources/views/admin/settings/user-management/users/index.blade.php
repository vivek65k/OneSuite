@extends('layouts.admin')

@section('title', 'Users')

@php
    $filterLabels = [
        'search' => 'Search',
        'is_active' => 'Status',
        'is_super_admin' => 'Super admin',
        'role' => 'Role',
        'department' => 'Department',
        'joined_from' => 'Joined from',
        'joined_until' => 'Joined until',
    ];

    $activeFilters = collect($filterLabels)
        ->filter(fn ($label, $key) => filled($state[$key] ?? null))
        ->map(function ($label, $key) use ($state, $roles) {
            $value = $state[$key];

            if ($key === 'role') {
                $value = optional($roles->firstWhere('id', (int) $value))->name ?? $value;
            } elseif ($key === 'is_active') {
                $value = $value === '1' ? 'Active' : 'Inactive';
            } elseif ($key === 'is_super_admin') {
                $value = $value === '1' ? 'Yes' : 'No';
            }

            return ['key' => $key, 'label' => $label, 'value' => $value];
        });

    // Every list link is built from the full current state (sort, per page,
    // other filters) rather than the bare route - a query-less URL makes
    // PersistsTableState restore the previous session state instead.
    $listUrl = fn (array $overrides = []) => route('admin.users.index', array_filter(
        array_merge($state, ['page' => null], $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));

    $isActive = (string) ($state['is_active'] ?? '');
    $isSuper = (string) ($state['is_super_admin'] ?? '');
    $statusTabs = [
        ['tone' => 'indigo', 'label' => 'All users', 'count' => $statusCounts['all'], 'current' => $isActive === '' && $isSuper === '', 'url' => $listUrl(['is_active' => null, 'is_super_admin' => null])],
        ['tone' => 'success', 'label' => 'Active', 'count' => $statusCounts['active'], 'current' => $isActive === '1' && $isSuper === '', 'url' => $listUrl(['is_active' => '1', 'is_super_admin' => null])],
        ['tone' => 'slate', 'label' => 'Inactive', 'count' => $statusCounts['inactive'], 'current' => $isActive === '0' && $isSuper === '', 'url' => $listUrl(['is_active' => '0', 'is_super_admin' => null])],
        ['tone' => 'amber', 'label' => 'Super admins', 'count' => $statusCounts['super_admins'], 'current' => $isActive === '' && $isSuper === '1', 'url' => $listUrl(['is_active' => null, 'is_super_admin' => '1'])],
    ];

    $panelKeys = ['role', 'department', 'joined_from', 'joined_until'];
    $panelFilterCount = collect($panelKeys)->filter(fn ($key) => filled($state[$key] ?? null))->count();
    $clearAllUrl = $listUrl(array_fill_keys(array_keys($filterLabels), null));
    $perPage = (int) ($state['per_page'] ?? 25);
    $avatarTones = ['indigo', 'teal', 'pink', 'amber', 'violet', 'sky'];
    $tagTones = ['indigo', 'teal', 'pink', 'amber', 'violet', 'sky', 'rose', 'lime'];
@endphp

@section('content')
    <x-page-title title="Users" />

    @if (session('invite_url'))
        <div class="alert alert-success">
            <div class="fw-semibold mb-1">Invite sent to {{ session('invite_email') }}</div>
            <p class="text-muted font-13 mb-2">
                If mail isn't configured to actually deliver yet, share this link with them directly instead - it works once and expires in 7 days.
            </p>
            <div class="input-group">
                <input type="text" class="form-control font-13" id="invite-url-field" value="{{ session('invite_url') }}" readonly onclick="this.select()">
                <button class="btn btn-light" type="button" id="copy-invite-url"><i class="ti ti-copy font-14 me-1"></i> Copy</button>
            </div>
        </div>
    @elseif (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @error('user_limit')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror

    <div class="kpi-strip">
        <x-kpi-card label="Total users" :value="$kpis['total']" icon="ti-users" tone="indigo" :href="$listUrl(['is_active' => null, 'is_super_admin' => null])" />
        <x-kpi-card label="Active" :value="$kpis['active']" icon="ti-user-check" tone="success"
                    :hint="$company->hasUserLimit() ? 'of '.$company->max_users.' allowed' : null" :href="$listUrl(['is_active' => '1', 'is_super_admin' => null])" />
        <x-kpi-card label="Inactive" :value="$kpis['total'] - $kpis['active']" icon="ti-user-off" tone="slate" :href="$listUrl(['is_active' => '0', 'is_super_admin' => null])" />
        <x-kpi-card label="Joined this month" :value="$kpis['joined_this_month']" icon="ti-user-plus" tone="pink" :hint="now()->format('F Y')" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.users.index') }}">
            @foreach (['sort' => $sort, 'direction' => $direction, 'per_page' => $perPage, 'is_active' => $isActive, 'is_super_admin' => $isSuper] as $name => $value)
                @if ((string) $value !== '')
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endif
            @endforeach

            {{-- Toolbar: saved-view style status tabs, then search / filters / actions --}}
            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="User status">
                    @foreach ($statusTabs as $tab)
                        <a href="{{ $tab['url'] }}" class="list-tab tone-{{ $tab['tone'] }} {{ $tab['current'] ? 'active' : '' }}" @if ($tab['current']) aria-current="page" @endif>
                            {{ $tab['label'] }}
                            <span class="list-tab-count">{{ number_format($tab['count']) }}</span>
                        </a>
                    @endforeach
                </nav>

                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] ?? '' }}" placeholder="Search name or email" aria-label="Search users">
                    </div>
                    <button type="button" class="btn btn-light btn-sm list-filter-toggle {{ $panelFilterCount ? 'has-filters' : '' }}"
                            data-bs-toggle="collapse" data-bs-target="#users-filter-panel" aria-expanded="false" aria-controls="users-filter-panel">
                        <i class="ti ti-adjustments-horizontal font-15"></i> Filters
                        @if ($panelFilterCount)
                            <span class="list-filter-count">{{ $panelFilterCount }}</span>
                        @endif
                    </button>
                    <x-export-csv-button route="admin.users.export" />
                    @can('create', \App\Models\User::class)
                        @if ($company->userLimitReached())
                            <button type="button" class="btn btn-de-primary btn-sm text-light px-3" disabled title="User limit reached"><i class="ti ti-plus font-14 me-1"></i> New user</button>
                        @else
                            <a href="{{ route('admin.users.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> New user</a>
                        @endif
                    @endcan
                </div>
            </div>

            {{-- Advanced filters: a collapsed drawer, opened from the Filters button --}}
            <div class="collapse" id="users-filter-panel">
                <div class="list-filter-panel">
                    <div class="row g-3">
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select form-select-sm">
                                <option value="">Any role</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}" @selected(($state['role'] ?? '') == $role->id)>{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label">Department</label>
                            <select name="department" class="form-select form-select-sm">
                                <option value="">Any department</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department }}" @selected(($state['department'] ?? '') === $department)>{{ $department }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-12 col-lg-4">
                            <label class="form-label">Joined between</label>
                            <div class="list-date-range">
                                <input type="date" name="joined_from" class="form-control form-control-sm" value="{{ $state['joined_from'] ?? '' }}" aria-label="Joined from">
                                <span>to</span>
                                <input type="date" name="joined_until" class="form-control form-control-sm" value="{{ $state['joined_until'] ?? '' }}" aria-label="Joined until">
                            </div>
                        </div>
                        <div class="col-lg-2 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-de-primary btn-sm text-light flex-fill">Apply</button>
                            <a href="{{ $listUrl(array_fill_keys($panelKeys, null)) }}" class="btn btn-light btn-sm">Clear</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        @if ($activeFilters->isNotEmpty() || $company->hasUserLimit())
            <div class="list-subbar">
                @if ($activeFilters->isNotEmpty())
                    <div class="list-chips">
                        @foreach ($activeFilters as $filter)
                            <a href="{{ $listUrl([$filter['key'] => null]) }}" class="filter-chip" title="Remove filter">
                                {{ $filter['label'] }}: <strong>{{ $filter['value'] }}</strong>
                                <i class="ti ti-x"></i>
                            </a>
                        @endforeach
                        <a href="{{ $clearAllUrl }}" class="list-clear-all">Clear all</a>
                    </div>
                @endif
                @if ($company->hasUserLimit())
                    @php
                        $activeUsers = $company->activeUserCount();
                        $usedPercent = min(100, (int) round($activeUsers / max(1, $company->max_users) * 100));
                        $limitTone = $activeUsers >= $company->max_users ? 'danger' : ($usedPercent >= 80 ? 'warning' : 'success');
                    @endphp
                    <div class="user-limit-meter user-limit-{{ $limitTone }} ms-auto" title="Deactivated users don't count toward the limit">
                        <span class="user-limit-text"><strong>{{ $activeUsers }}</strong> / {{ $company->max_users }} active users</span>
                        <span class="user-limit-bar"><span style="width: {{ $usedPercent }}%"></span></span>
                    </div>
                @endif
            </div>
        @endif

        <div class="table-responsive table-responsive-sticky">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <x-th-sort column="name" :sort="$sort" :direction="$direction" label="User" />
                        <th>Roles</th>
                        <th>Branches</th>
                        <x-th-sort column="department" :sort="$sort" :direction="$direction" label="Department" />
                        <x-th-sort column="is_active" :sort="$sort" :direction="$direction" label="Status" />
                        <x-th-sort column="created_at" :sort="$sort" :direction="$direction" label="Joined" />
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        @php
                            $initials = collect(preg_split('/\s+/', trim($user->name)))
                                ->filter()
                                ->take(2)
                                ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                                ->implode('');
                        @endphp
                        <tr>
                            <td>
                                <div class="list-user">
                                    @if ($user->avatar)
                                        <img src="{{ asset('storage/'.$user->avatar) }}" alt="" class="list-avatar">
                                    @else
                                        <span class="list-avatar list-avatar-{{ $avatarTones[crc32($user->email) % count($avatarTones)] }}" aria-hidden="true">{{ $initials }}</span>
                                    @endif
                                    <div class="list-user-text">
                                        <div class="list-user-name">
                                            @can('update', $user)
                                                <a href="{{ route('admin.users.edit', $user) }}">{{ $user->name }}</a>
                                            @else
                                                <span>{{ $user->name }}</span>
                                            @endcan
                                            @if ($user->is_super_admin)
                                                <span class="list-flag" title="Super admin"><i class="ti ti-crown"></i> Super admin</span>
                                            @endif
                                            @if ($user->is(auth()->user()))
                                                <span class="list-flag list-flag-muted">You</span>
                                            @endif
                                        </div>
                                        <div class="list-user-meta">
                                            {{ $user->email }}
                                            @if ($user->designation)
                                                <span class="list-meta-sep">&middot;</span> {{ $user->designation }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @forelse ($user->roles as $role)
                                    <span class="badge badge-soft-primary">{{ $role->name }}</span>
                                @empty
                                    <span class="text-muted">&mdash;</span>
                                @endforelse
                            </td>
                            <td>
                                @forelse ($user->branches->take(2) as $branch)
                                    <span class="badge badge-soft-blue">{{ $branch->name }}</span>
                                @empty
                                    <span class="text-muted font-12">All branches</span>
                                @endforelse
                                @if ($user->branches->count() > 2)
                                    <span class="badge badge-soft-light" title="{{ $user->branches->slice(2)->pluck('name')->implode(', ') }}">+{{ $user->branches->count() - 2 }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($user->department)
                                    <span class="tag tone-{{ $tagTones[crc32(Str::slug($user->department, '_')) % count($tagTones)] }}">{{ $user->department }}</span>
                                @else
                                    <span class="text-muted">&mdash;</span>
                                @endif
                            </td>
                            <td>
                                <span class="status-dot {{ $user->is_active ? 'status-dot-success' : 'status-dot-muted' }}">
                                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-nowrap text-muted" title="{{ $user->created_at?->format('M j, Y g:i A') }}">
                                {{ $user->created_at?->format('M j, Y') }}
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="list-row-actions">
                                    @can('update', $user)
                                        <a href="{{ route('admin.users.edit', $user) }}" class="btn-icon btn-icon-accent" title="Edit"><i class="ti ti-pencil font-16"></i></a>
                                    @endcan
                                    @can('delete', $user)
                                        @unless ($user->is(auth()->user()))
                                            <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this user?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                            </form>
                                        @endunless
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="ti ti-users font-24 d-block mb-2"></i>
                                <div class="fw-semibold text-body mb-1">No users found</div>
                                @if ($activeFilters->isNotEmpty())
                                    <span class="font-13">Try adjusting your filters, or <a href="{{ $clearAllUrl }}">clear them all</a>.</span>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="list-footer">
            <div class="list-per-page dropdown">
                <span class="text-muted font-13">Rows per page</span>
                <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">{{ $perPage }}</button>
                <ul class="dropdown-menu">
                    @foreach ([10, 25, 50, 100] as $option)
                        <li><a class="dropdown-item {{ $perPage === $option ? 'active' : '' }}" href="{{ $listUrl(['per_page' => $option]) }}">{{ $option }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div class="ms-auto">{{ $users->links() }}</div>
        </div>
    </div>

    @if (session('invite_url'))
        @push('scripts')
            <script>
                document.getElementById('copy-invite-url').addEventListener('click', function () {
                    var field = document.getElementById('invite-url-field');
                    field.select();
                    navigator.clipboard.writeText(field.value).then(function () {
                        var button = document.getElementById('copy-invite-url');
                        var original = button.innerHTML;
                        button.innerHTML = '<i class="ti ti-check font-14 me-1"></i> Copied';
                        setTimeout(function () { button.innerHTML = original; }, 1500);
                    });
                });
            </script>
        @endpush
    @endif
@endsection
