@extends('layouts.admin')

@section('title', 'Sales Team & Targets')

@section('content')
    <x-page-title title="Sales Team & Targets" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <p class="text-muted font-13 mb-3"><i class="ti ti-info-circle"></i> Quota achievement here is real booked Sales Order revenue, not CRM's opportunity pipeline figure.</p>

    <div class="kpi-strip mb-3">
        <x-kpi-card label="Total Target" :value="number_format($totalTarget, 2)" icon="ti-target" />
        <x-kpi-card label="Total Achieved" :value="number_format($totalAchieved, 2)" icon="ti-trending-up" tone="success" />
        <x-kpi-card label="Incentive Earned" :value="number_format($totalCommission, 2)" icon="ti-coin" tone="amber" />
        <x-kpi-card label="Teams" :value="$teams->count()" icon="ti-users" />
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-7">
            <div class="card list-view mb-3">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> Create a team</div></div>
                <div class="list-filter-panel">
                    <form method="POST" action="{{ route('admin.sales.team-targets.teams.store') }}" class="row g-2 align-items-end">
                        @csrf
                        <div class="col-md-6"><input type="text" name="name" class="form-control form-control-sm" placeholder="Team name" required></div>
                        <div class="col-md-5">
                            <select name="manager_employee_id" class="form-select form-select-sm">
                                <option value="">— No manager —</option>
                                @foreach ($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-1"><button type="submit" class="btn btn-de-primary text-light btn-sm w-100">Add</button></div>
                    </form>
                </div>
            </div>

            @foreach ($teams as $team)
                <div class="card list-view mb-3">
                    <div class="list-toolbar">
                        <div class="list-toolbar-title"><i class="ti ti-users"></i> {{ $team->name }} <span class="text-muted font-11">({{ $team->manager?->name ?? 'No manager' }})</span></div>
                        <form action="{{ route('admin.sales.team-targets.teams.destroy', $team) }}" method="POST" onsubmit="return confirm('Remove this team?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                        </form>
                    </div>
                    <div class="list-filter-panel">
                        <div class="d-flex gap-2 flex-wrap mb-2">
                            @forelse ($team->members as $member)
                                <form action="{{ route('admin.sales.team-targets.teams.members.remove', [$team, $member]) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="tag tone-sky font-11 border-0" title="Remove {{ $member->name }}">{{ $member->name }} <i class="ti ti-x font-11"></i></button>
                                </form>
                            @empty
                                <span class="text-muted font-11">No members yet.</span>
                            @endforelse
                        </div>
                        <form action="{{ route('admin.sales.team-targets.teams.members.add', $team) }}" method="POST" class="d-flex gap-1">
                            @csrf
                            <select name="employee_id" class="form-select form-select-sm" required>
                                @foreach ($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->name }}</option>@endforeach
                            </select>
                            <button type="submit" class="btn btn-light btn-sm">Add Member</button>
                        </form>
                        @if ($team->territories->isNotEmpty())
                            <p class="text-muted font-11 mt-2 mb-0">Territories: {{ $team->territories->pluck('name')->implode(', ') }}</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="col-lg-5">
            <div class="card list-view">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-map-pin"></i> Territories</div></div>
                <div class="list-filter-panel">
                    <form method="POST" action="{{ route('admin.sales.team-targets.territories.store') }}" class="row g-2 align-items-end mb-2">
                        @csrf
                        <div class="col-md-4"><input type="text" name="name" class="form-control form-control-sm" placeholder="Territory name" required></div>
                        <div class="col-md-4">
                            <select name="team_id" class="form-select form-select-sm">
                                <option value="">— No team —</option>
                                @foreach ($teams as $team)<option value="{{ $team->id }}">{{ $team->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-3"><input type="text" name="criteria" class="form-control form-control-sm" placeholder="Criteria"></div>
                        <div class="col-md-1"><button type="submit" class="btn btn-de-primary text-light btn-sm w-100">Add</button></div>
                    </form>
                    <table class="table table-hover list-table mb-0">
                        <thead class="thead-light"><tr><th>Territory</th><th>Team</th><th>Criteria</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                        <tbody>
                            @forelse ($territories as $territory)
                                <tr>
                                    <td class="font-13">{{ $territory->name }}</td>
                                    <td class="text-muted font-13">{{ $territory->team?->name ?? '—' }}</td>
                                    <td class="text-muted font-13">{{ $territory->criteria ?? '—' }}</td>
                                    <td class="text-end">
                                        <form action="{{ route('admin.sales.team-targets.territories.destroy', $territory) }}" method="POST">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">No territories yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-target"></i> Set a quota</div></div>
        <div class="list-filter-panel">
            <form method="POST" action="{{ route('admin.sales.team-targets.quotas.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-2">
                    <select name="employee_id" class="form-select form-select-sm">
                        <option value="">— Employee —</option>
                        @foreach ($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="team_id" class="form-select form-select-sm">
                        <option value="">— Or Team —</option>
                        @foreach ($teams as $team)<option value="{{ $team->id }}">{{ $team->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="period" class="form-select form-select-sm" required>
                        @foreach (\App\Models\Crm\Quota::PERIODS as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2"><input type="date" name="period_start" class="form-control form-control-sm" required></div>
                <div class="col-md-2"><input type="number" step="0.01" min="0" name="target_amount" class="form-control form-control-sm" placeholder="Target amount" required></div>
                <div class="col-md-1"><input type="number" step="0.01" min="0" max="100" name="incentive_percent" class="form-control form-control-sm" placeholder="Incentive %"></div>
                <div class="col-md-1"><button type="submit" class="btn btn-de-primary text-light btn-sm w-100">Set</button></div>
            </form>
        </div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-chart-bar"></i> Quotas & Incentives <span class="list-tab-count">{{ $quotaRows->count() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Who</th><th>Period</th><th>Target</th><th>Achieved</th><th style="min-width:160px">Progress</th><th>Incentive %</th><th>Earned</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($quotaRows as $row)
                        <tr>
                            <td class="font-13">{{ $row['quota']->employee?->name ?? $row['quota']->team?->name ?? '—' }}</td>
                            <td class="text-muted font-13">{{ \App\Models\Crm\Quota::PERIODS[$row['quota']->period] }} from {{ $row['quota']->period_start->format('M j, Y') }}</td>
                            <td class="text-muted font-13">{{ number_format($row['quota']->target_amount, 2) }}</td>
                            <td class="text-muted font-13">{{ number_format($row['achieved'], 2) }}</td>
                            <td>
                                <div class="mini-progress"><span style="width: {{ $row['pct'] }}%;" class="bg-{{ $row['pct'] >= 100 ? 'success' : ($row['pct'] >= 50 ? 'warning' : 'danger') }}"></span></div>
                                <span class="font-11 text-muted">{{ $row['pct'] }}%</span>
                            </td>
                            <td class="text-muted font-13">{{ $row['quota']->incentive_percent }}%</td>
                            <td class="font-13 fw-semibold text-success">{{ number_format($row['commission'], 2) }}</td>
                            <td class="text-end">
                                <form action="{{ route('admin.sales.team-targets.quotas.destroy', $row['quota']) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-5"><i class="ti ti-target font-24 d-block mb-2"></i>No quotas set yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
