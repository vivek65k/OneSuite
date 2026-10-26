@extends('layouts.admin')

@section('title', 'Leave Types')

@php
    $listUrl = fn (array $overrides = []) => route('admin.leave-types.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $currentStatus = (string) ($state['status'] ?? '');
    $currentPaid = (string) ($state['paid'] ?? '');
    $tabs = [
        '' => ['label' => 'All types', 'tone' => 'indigo', 'count' => $counts['all']],
        'active' => ['label' => 'Active', 'tone' => 'success', 'count' => $counts['active']],
        'inactive' => ['label' => 'Inactive', 'tone' => 'slate', 'count' => $counts['inactive']],
    ];
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
    $canCreate = auth()->user()->can('create', \App\Models\LeaveType::class);
@endphp

@section('content')
    <x-page-title title="Leave Types" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @error('leave_type')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror

    <div class="kpi-strip">
        <x-kpi-card label="Active leave types" :value="$kpis['active']" icon="ti-calendar-off" tone="indigo" :hint="$counts['inactive'] ? $counts['inactive'].' inactive' : 'All types active'" :href="$listUrl(['status' => 'active'])" />
        <x-kpi-card label="Paid days per year" :value="$fmt($kpis['paid_days'])" icon="ti-coin" tone="success" hint="Total of active paid types" :href="$listUrl(['paid' => 'paid', 'status' => 'active'])" />
        <x-kpi-card label="Carry-forward types" :value="$kpis['carry_forward']" icon="ti-arrow-forward" tone="teal" hint="Unused days roll over" />
        <x-kpi-card label="Requests in {{ $year }}" :value="$kpis['requests']" icon="ti-calendar-event" tone="pink" :hint="$kpis['pending'] ? $kpis['pending'].' pending approval' : 'None pending'" />
    </div>

    <div class="card list-view mb-3">
        <form method="GET" action="{{ route('admin.leave-types.index') }}">
            @if ($currentStatus !== '')
                <input type="hidden" name="status" value="{{ $currentStatus }}">
            @endif
            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Leave type status">
                    @foreach ($tabs as $value => $tab)
                        <a href="{{ $listUrl(['status' => $value === '' ? null : $value]) }}" class="list-tab tone-{{ $tab['tone'] }} {{ $currentStatus === (string) $value ? 'active' : '' }}">
                            {{ $tab['label'] }}
                            <span class="list-tab-count">{{ $tab['count'] }}</span>
                        </a>
                    @endforeach
                </nav>
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] ?? '' }}" placeholder="Search name or code" aria-label="Search leave types">
                    </div>
                    <select name="paid" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Paid or unpaid">
                        <option value="">Paid &amp; unpaid</option>
                        <option value="paid" @selected($currentPaid === 'paid')>Paid only</option>
                        <option value="unpaid" @selected($currentPaid === 'unpaid')>Unpaid only</option>
                    </select>
                    @if ($canCreate)
                        <a href="{{ route('admin.leave-types.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> Add leave type</a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    @if ($leaveTypes->isEmpty())
        <div class="card">
            <div class="empty-state">
                <span class="empty-state-icon tone-indigo"><i class="ti ti-calendar-off"></i></span>
                <h5>{{ $counts['all'] ? 'No leave types match' : 'No leave types yet' }}</h5>
                <p>
                    @if ($counts['all'])
                        Try a different search or <a href="{{ route('admin.leave-types.index') }}">clear the filters</a>.
                    @else
                        Set up the kinds of leave employees can take - Casual, Sick, Earned and so on.
                    @endif
                </p>
                @if ($canCreate && ! $counts['all'])
                    <a href="{{ route('admin.leave-types.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> Add leave type</a>
                @endif
            </div>
        </div>
    @else
        <div class="policy-grid">
            @foreach ($leaveTypes as $leaveType)
                @php
                    $style = \App\Support\LeaveTypeStyle::for($leaveType->name, $leaveType->code);
                    $stats = $requestStats->get($leaveType->id);
                    $holders = (int) ($balanceHolders[$leaveType->id] ?? 0);
                    $monthly = (float) $leaveType->days_per_year / 12;
                @endphp
                <article class="policy-card tone-{{ $style['tone'] }} {{ $leaveType->is_active ? '' : 'is-inactive' }}">
                    <div class="policy-card-head">
                        <span class="policy-card-icon"><i class="ti {{ $style['icon'] }}"></i></span>
                        <div class="min-w-0 flex-grow-1">
                            <h5 class="policy-card-title">
                                @can('update', $leaveType)
                                    <a href="{{ route('admin.leave-types.edit', $leaveType) }}">{{ $leaveType->name }}</a>
                                @else
                                    {{ $leaveType->name }}
                                @endcan
                            </h5>
                            <div class="policy-card-sub">
                                <span class="list-code">{{ $leaveType->code }}</span>
                                @if ($branchNames->isNotEmpty() && $leaveType->branch_id)
                                    <span class="list-icon-text"><i class="ti ti-building-store"></i>{{ $branchNames[$leaveType->branch_id] ?? 'Branch' }}</span>
                                @endif
                            </div>
                        </div>
                        @can('update', $leaveType)
                            <form method="POST" action="{{ route('admin.leave-types.toggle', $leaveType) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="switch-btn {{ $leaveType->is_active ? 'is-on' : '' }}" role="switch" aria-checked="{{ $leaveType->is_active ? 'true' : 'false' }}"
                                        title="{{ $leaveType->is_active ? 'Active - click to deactivate' : 'Inactive - click to activate' }}">
                                    <span class="visually-hidden">{{ $leaveType->is_active ? 'Deactivate' : 'Activate' }} {{ $leaveType->name }}</span>
                                </button>
                            </form>
                        @else
                            <span class="tag tone-{{ $leaveType->is_active ? 'success' : 'slate' }}">{{ $leaveType->is_active ? 'Active' : 'Inactive' }}</span>
                        @endcan
                    </div>

                    <div class="policy-card-figure">
                        <span class="policy-card-days">{{ $fmt($leaveType->days_per_year) }}</span>
                        <span class="policy-card-unit">{{ Str::plural('day', (float) $leaveType->days_per_year == 1 ? 1 : 2) }} / year</span>
                        @if ($leaveType->days_per_year > 0)
                            <span class="policy-card-accrual" title="If accrued evenly each month">≈ {{ $fmt(round($monthly, 2)) }} / month</span>
                        @endif
                    </div>

                    <div class="policy-card-rules">
                        <span class="rule-chip {{ $leaveType->is_paid ? 'is-yes tone-success' : 'is-no tone-slate' }}">
                            <i class="ti {{ $leaveType->is_paid ? 'ti-coin' : 'ti-ban' }}"></i> {{ $leaveType->is_paid ? 'Paid' : 'Unpaid' }}
                        </span>
                        <span class="rule-chip {{ $leaveType->carry_forward ? 'is-yes tone-teal' : 'is-no tone-slate' }}">
                            <i class="ti ti-arrow-forward"></i>
                            @if ($leaveType->carry_forward)
                                Carry forward {{ $leaveType->max_carry_forward_days !== null ? 'up to '.$fmt($leaveType->max_carry_forward_days).'d' : '(no limit)' }}
                            @else
                                No carry forward
                            @endif
                        </span>
                    </div>

                    <div class="policy-card-stats">
                        <div><strong>{{ (int) ($stats->requests ?? 0) }}</strong><span>Requests {{ $year }}</span></div>
                        <div><strong>{{ $fmt($stats->days_taken ?? 0) }}</strong><span>Days taken</span></div>
                        <div><strong>{{ $holders }}</strong><span>With balance</span></div>
                    </div>

                    <div class="policy-card-actions">
                        @if ((int) ($stats->pending ?? 0))
                            <span class="countdown tone-amber"><i class="ti ti-clock"></i> {{ (int) $stats->pending }} pending</span>
                        @endif
                        <div class="ms-auto d-flex gap-1">
                            @can('update', $leaveType)
                                <a href="{{ route('admin.leave-types.edit', $leaveType) }}" class="btn-icon btn-icon-accent" title="Edit"><i class="ti ti-pencil font-16"></i></a>
                            @endcan
                            @can('delete', $leaveType)
                                @php $inUse = (int) ($stats->requests ?? 0) > 0 || $holders > 0; @endphp
                                <form action="{{ route('admin.leave-types.destroy', $leaveType) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm({{ $inUse ? json_encode('This leave type is in use and cannot be deleted - deactivate it instead. Try anyway?') : json_encode('Delete this leave type?') }});">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="{{ $inUse ? 'In use - deactivate instead of deleting' : 'Delete' }}"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            @endcan
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
@endsection
