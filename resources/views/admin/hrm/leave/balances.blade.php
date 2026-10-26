@extends('layouts.admin')

@section('title', 'Leave Balance')

@php
    use App\Support\LeaveTypeStyle;

    $pageUrl = fn (array $overrides = []) => route('admin.leave-balances.index', array_filter(
        array_merge($state, ['year' => $year], $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
    $usedPercent = $totals['allocated'] > 0 ? (int) round($totals['used'] / $totals['allocated'] * 100) : 0;
    $avatarTones = ['indigo', 'teal', 'pink', 'amber', 'violet', 'sky'];
@endphp

@section('content')
    <x-page-title title="Leave Balance" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Days entitled" :value="$fmt($totals['allocated'])" icon="ti-calendar-stats" tone="indigo" :hint="$employees->count().' '.Str::plural('employee', $employees->count()).' · '.$year" />
        <x-kpi-card label="Days used" :value="$fmt($totals['used'])" icon="ti-plane" tone="violet" :hint="$usedPercent.'% of entitlement'" />
        <x-kpi-card label="Days remaining" :value="$fmt($totals['remaining'])" icon="ti-circle-check" tone="success" />
        <x-kpi-card label="Needs attention" :value="$totals['missing'] + $totals['low']" icon="ti-alert-triangle" tone="{{ $totals['missing'] + $totals['low'] ? 'amber' : 'success' }}"
                    :hint="$totals['missing'].' not allocated · '.$totals['low'].' running low'" />
    </div>

    @if ($canEdit && $totals['missing'] > 0)
        <div class="nudge-card tone-amber">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <span class="nudge-card-icon"><i class="ti ti-calendar-plus"></i></span>
                <div class="flex-grow-1">
                    <div class="fw-semibold">{{ $totals['missing'] }} {{ Str::plural('balance', $totals['missing']) }} not allocated for {{ $year }}</div>
                    <div class="text-muted font-12">Run the yearly allocation to give everyone each active leave type's days, carrying forward unused {{ $year - 1 }} days where the type allows it. Existing balances aren't touched.</div>
                </div>
                <form method="POST" action="{{ route('admin.leave-balances.allocate') }}" onsubmit="return confirm('Allocate {{ $year }} balances for everyone who is missing one?');">
                    @csrf
                    <input type="hidden" name="year" value="{{ $year }}">
                    <button type="submit" class="btn btn-sm nudge-btn"><i class="ti ti-player-play font-14 me-1"></i> Run {{ $year }} allocation</button>
                </form>
            </div>
        </div>
    @endif

    <div class="card list-view">
        <div class="list-toolbar">
            <div class="month-nav">
                <a href="{{ $pageUrl(['year' => $year - 1]) }}" class="btn-icon" title="Previous year"><i class="ti ti-chevron-left font-18"></i></a>
                <div class="month-nav-label"><span class="fw-bold">{{ $year }}</span><span class="text-muted">leave year</span></div>
                <a href="{{ $pageUrl(['year' => $year + 1]) }}" class="btn-icon" title="Next year"><i class="ti ti-chevron-right font-18"></i></a>
                @if ($year !== (int) now()->year)
                    <a href="{{ $pageUrl(['year' => now()->year]) }}" class="btn btn-light btn-sm ms-1">This year</a>
                @endif
            </div>
            <form method="GET" action="{{ route('admin.leave-balances.index') }}" class="list-toolbar-actions">
                <input type="hidden" name="year" value="{{ $year }}">
                <div class="list-search">
                    <i class="ti ti-search"></i>
                    <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] ?? '' }}" placeholder="Search name or code" aria-label="Search employees">
                </div>
                <select name="department_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Department">
                    <option value="">All departments</option>
                    @foreach ($departments as $id => $name)
                        <option value="{{ $id }}" @selected((string) ($state['department_id'] ?? '') === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="list-segment-bar">
            <div class="stack-legend m-0">
                <span><span class="legend-dot tone-success"></span> Plenty left</span>
                <span><span class="legend-dot tone-amber"></span> 2 days or fewer</span>
                <span><span class="legend-dot tone-danger"></span> Used up / overdrawn</span>
                <span><span class="legend-dot is-unmarked"></span> Not allocated</span>
            </div>
            @if ($canEdit)
                <span class="list-segment-hint"><i class="ti ti-pencil"></i> Click a balance to edit it</span>
            @endif
        </div>

        <div class="table-responsive balance-wrap">
            <table class="table list-table balance-matrix mb-0">
                <thead class="thead-light">
                    <tr>
                        <th class="balance-name">Employee</th>
                        @foreach ($columns as $column)
                            @php $style = LeaveTypeStyle::for($column['name'], $column['code']); @endphp
                            <th class="balance-col">
                                <span class="balance-col-head tone-{{ $style['tone'] }}"><i class="ti {{ $style['icon'] }}"></i> {{ $column['name'] }}</span>
                            </th>
                        @endforeach
                        <th class="balance-col text-center">Total left</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employees as $employee)
                        @php
                            $initials = collect(preg_split('/\s+/', trim($employee->name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
                            $rowRemaining = 0.0;
                        @endphp
                        <tr>
                            <td class="balance-name">
                                <div class="list-user">
                                    <span class="list-avatar list-avatar-{{ $avatarTones[crc32($employee->employee_code.$employee->name) % count($avatarTones)] }}" aria-hidden="true">{{ $initials }}</span>
                                    <div class="list-user-text">
                                        <div class="list-user-name"><a href="{{ route('admin.employees.show', $employee) }}">{{ $employee->name }}</a></div>
                                        <div class="list-user-meta">{{ $employee->department?->name ?? $employee->employee_code }}</div>
                                    </div>
                                </div>
                            </td>
                            @foreach ($columns as $index => $column)
                                @php $cell = $cells[$employee->id][$index] ?? null; @endphp
                                <td class="balance-col">
                                    @if (! $cell)
                                        <span class="balance-na" title="Not offered in this employee's branch">—</span>
                                    @else
                                        @php
                                            $type = $cell['type'];
                                            $balance = $cell['balance'];
                                            $style = LeaveTypeStyle::for($type->name, $type->code);
                                            $entitled = $balance ? (float) $balance->allocated + (float) $balance->carried_forward + (float) $balance->adjusted : 0.0;
                                            $remaining = $balance ? (float) $balance->remaining : null;
                                            $rowRemaining += (float) ($remaining ?? 0);
                                            $level = $remaining === null ? 'none' : ($remaining <= 0 ? 'danger' : ($remaining <= 2 ? 'amber' : 'success'));
                                            $pct = $entitled > 0 ? max(0, min(100, round(($remaining ?? 0) / $entitled * 100))) : 0;
                                            $cellId = 'bal-'.$employee->id.'-'.$type->id;
                                        @endphp
                                        <div class="dropdown">
                                            <button type="button" class="balance-cell tone-{{ $style['tone'] }} is-{{ $level }}" @if ($canEdit) data-bs-toggle="dropdown" data-bs-auto-close="outside" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" @else disabled @endif
                                                    title="{{ $balance ? $fmt($remaining).' of '.$fmt($entitled).' days left' : 'Not allocated for '.$year }}">
                                                @if ($balance)
                                                    <span class="balance-cell-value">{{ $fmt($remaining) }}<small> / {{ $fmt($entitled) }}</small></span>
                                                    <span class="balance-cell-bar"><span style="width: {{ $pct }}%"></span></span>
                                                @else
                                                    <span class="balance-cell-empty">{{ $canEdit ? '+ Allocate' : 'Not allocated' }}</span>
                                                @endif
                                            </button>
                                            @if ($canEdit)
                                                <div class="dropdown-menu balance-editor p-3">
                                                    <form method="POST" action="{{ route('admin.leave-balances.update') }}" data-balance-form>
                                                        @csrf
                                                        <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                                                        <input type="hidden" name="leave_type_id" value="{{ $type->id }}">
                                                        <input type="hidden" name="year" value="{{ $year }}">
                                                        <div class="balance-editor-head tone-{{ $style['tone'] }}">
                                                            <span class="policy-card-icon"><i class="ti {{ $style['icon'] }}"></i></span>
                                                            <div class="min-w-0">
                                                                <div class="fw-semibold text-truncate">{{ $type->name }}</div>
                                                                <div class="list-subtext text-truncate">{{ $employee->name }} · {{ $year }}</div>
                                                            </div>
                                                        </div>
                                                        <div class="row g-2">
                                                            <div class="col-4">
                                                                <label class="form-label font-11" for="{{ $cellId }}-a">Allocated</label>
                                                                <input type="number" step="0.5" min="0" max="365" name="allocated" id="{{ $cellId }}-a" class="form-control form-control-sm" value="{{ $fmt($balance->allocated ?? $type->days_per_year) }}" data-part required>
                                                            </div>
                                                            <div class="col-4">
                                                                <label class="form-label font-11" for="{{ $cellId }}-c">Carried</label>
                                                                <input type="number" step="0.5" min="0" max="365" name="carried_forward" id="{{ $cellId }}-c" class="form-control form-control-sm" value="{{ $fmt($balance->carried_forward ?? 0) }}" data-part required>
                                                            </div>
                                                            <div class="col-4">
                                                                <label class="form-label font-11" for="{{ $cellId }}-j">Adjust ±</label>
                                                                <input type="number" step="0.5" min="-365" max="365" name="adjusted" id="{{ $cellId }}-j" class="form-control form-control-sm" value="{{ $fmt($balance->adjusted ?? 0) }}" data-part required>
                                                            </div>
                                                        </div>
                                                        <dl class="balance-editor-math" data-used="{{ (float) ($balance->used ?? 0) }}" data-encashed="{{ (float) ($balance->encashed ?? 0) }}">
                                                            <dt>Used</dt><dd>− {{ $fmt($balance->used ?? 0) }}</dd>
                                                            <dt>Encashed</dt><dd>− {{ $fmt($balance->encashed ?? 0) }}</dd>
                                                            <dt class="is-total">Remaining</dt><dd class="is-total" data-remaining>{{ $fmt($remaining ?? $type->days_per_year) }}</dd>
                                                        </dl>
                                                        <button type="submit" class="btn btn-de-primary btn-sm text-light w-100"><i class="ti ti-check font-14 me-1"></i> {{ $balance ? 'Save balance' : 'Allocate' }}</button>
                                                    </form>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            @endforeach
                            <td class="balance-col text-center">
                                <span class="hours-figure">{{ $fmt($rowRemaining) }}<small>d</small></span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $columns->count() + 2 }}" class="text-center text-muted py-5">
                                <i class="ti ti-users font-24 d-block mb-2"></i>
                                <div class="fw-semibold text-body mb-1">No employees found</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($columns->isEmpty())
            <div class="checklist-empty"><i class="ti ti-info-circle"></i> No active leave types - <a href="{{ route('admin.leave-types.index') }}">set some up</a> first.</div>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        // Live "Remaining" in each cell editor.
        document.querySelectorAll('[data-balance-form]').forEach(function (form) {
            var math = form.querySelector('.balance-editor-math');
            var out = form.querySelector('[data-remaining]');
            var parts = form.querySelectorAll('[data-part]');
            function recalc() {
                var total = 0;
                parts.forEach(function (input) { total += parseFloat(input.value) || 0; });
                total -= parseFloat(math.dataset.used) + parseFloat(math.dataset.encashed);
                out.textContent = (Math.round(total * 100) / 100).toString();
                out.classList.toggle('text-danger', total < 0);
            }
            parts.forEach(function (input) { input.addEventListener('input', recalc); });
        });
    </script>
@endpush
