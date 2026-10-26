@extends('layouts.admin')

@section('title', 'Overtime Rules')

@php
    use App\Support\ShiftTime;

    $listUrl = fn (array $overrides = []) => route('admin.overtime-rules.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $currentStatus = (string) ($state['status'] ?? '');
    $tabs = [
        '' => ['label' => 'All rules', 'tone' => 'indigo', 'count' => $counts['all']],
        'active' => ['label' => 'Active', 'tone' => 'success', 'count' => $counts['active']],
        'inactive' => ['label' => 'Inactive', 'tone' => 'slate', 'count' => $counts['inactive']],
        'general' => ['label' => 'All-shift rules', 'tone' => 'violet', 'count' => $counts['general']],
    ];
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
    $canCreate = auth()->user()->can('create', \App\Models\OvertimeRule::class);
    $multiplierLabel = fn ($m) => match (true) {
        (float) $m >= 3 => 'Triple time',
        (float) $m >= 2 => 'Double time',
        (float) $m >= 1.5 => 'Time and a half',
        (float) $m > 1 => 'Premium rate',
        default => 'Straight time',
    };
    $multiplierTone = fn ($m) => (float) $m >= 2 ? 'rose' : ((float) $m >= 1.5 ? 'amber' : 'teal');
    $gaps = $coverage->filter(fn ($c) => ! $c['rule'])->count();
@endphp

@section('content')
    <x-page-title title="Overtime Rules" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @error('rule')
        <div class="alert alert-danger d-flex align-items-center gap-2"><i class="ti ti-alert-circle font-18"></i> {{ $message }}</div>
    @enderror

    <div class="kpi-strip">
        <x-kpi-card label="Active rules" :value="$counts['active']" icon="ti-scale" tone="indigo" :hint="! $counts['all'] ? 'No rules yet' : ($counts['inactive'] ? $counts['inactive'].' inactive' : 'All rules active')" :href="$listUrl(['status' => 'active'])" />
        <x-kpi-card label="Shifts covered" :value="$kpis['covered'].' / '.$kpis['shifts']" icon="ti-shield-check" :tone="$gaps ? 'amber' : 'success'" :hint="$gaps ? $gaps.' active '.\Illuminate\Support\Str::plural('shift', $gaps).' without a rule' : 'Every active shift has a rule'" />
        <x-kpi-card label="Highest rate" :value="$kpis['top_multiplier'] ? $fmt($kpis['top_multiplier']).'×' : '—'" icon="ti-trending-up" tone="violet" :hint="$kpis['top_multiplier'] ? $multiplierLabel($kpis['top_multiplier']) : 'No active rules'" />
        <x-kpi-card label="Overtime approved" :value="$fmt($kpis['ot_hours']).' h'" icon="ti-alarm" tone="amber" :hint="now()->format('F').($kpis['ot_pending'] ? ' · '.$kpis['ot_pending'].' pending' : '')"
                    :href="route('admin.overtime-requests.index')" />
    </div>

    @if ($coverage->isNotEmpty())
        <div class="card side-list tone-teal mb-3">
            <div class="side-list-head">
                <span class="side-list-icon"><i class="ti ti-shield-check"></i></span>
                <span class="fw-semibold">Which rule applies</span>
                <span class="list-subtext ms-auto">A shift's own rule wins over the all-shifts rule</span>
            </div>
            <div class="ot-coverage">
                @foreach ($coverage as $row)
                    @php
                        $shift = $row['shift'];
                        $rule = $row['rule'];
                        $style = ShiftTime::style($shift);
                        $net = ShiftTime::netHours($shift);
                        $early = $rule && (float) $rule->daily_threshold_hours < $net;
                    @endphp
                    <div class="ot-coverage-row {{ $rule ? '' : 'is-gap' }}">
                        <div class="ot-coverage-shift">
                            <span class="roster-option-code tone-{{ $style['tone'] }}">{{ $shift->code }}</span>
                            <div class="min-w-0">
                                <div class="fw-semibold text-truncate">{{ $shift->name }}</div>
                                <div class="list-subtext">{{ ShiftTime::format($shift->start_time) }}–{{ ShiftTime::format($shift->end_time) }} · {{ $fmt($net) }} h work</div>
                            </div>
                        </div>
                        <i class="ti ti-arrow-right text-muted"></i>
                        @if ($rule)
                            <div class="ot-coverage-rule">
                                <div class="fw-semibold text-truncate">{{ $rule->name }}
                                    <span class="tag {{ $row['own'] ? 'tone-teal' : 'tag-outline tone-violet' }} ms-1">{{ $row['own'] ? 'own rule' : 'all-shifts rule' }}</span>
                                </div>
                                <div class="list-subtext">Overtime after <strong>{{ $fmt($rule->daily_threshold_hours) }} h</strong> a day at <strong>{{ $fmt($rule->multiplier) }}×</strong></div>
                                @if ($early)
                                    <div class="ot-coverage-warn"><i class="ti ti-alert-triangle"></i> Starts before the shift's own {{ $fmt($net) }} h - normal hours would count as overtime</div>
                                @endif
                            </div>
                        @else
                            <div class="ot-coverage-rule">
                                <span class="tag tone-amber"><i class="ti ti-alert-triangle"></i>No overtime rule</span>
                                @if ($canCreate)
                                    <a href="{{ route('admin.overtime-rules.create', ['shift_id' => $shift->id]) }}" class="font-12 ms-1">Add one</a>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="card list-view mb-3">
        <form method="GET" action="{{ route('admin.overtime-rules.index') }}">
            @if ($currentStatus !== '')
                <input type="hidden" name="status" value="{{ $currentStatus }}">
            @endif
            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Rule status">
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
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] ?? '' }}" placeholder="Search rule or shift" aria-label="Search rules">
                    </div>
                    @if ($canCreate)
                        <a href="{{ route('admin.overtime-rules.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> Add rule</a>
                    @endif
                </div>
            </div>
        </form>
        @if (filled($state['search'] ?? null))
            <div class="list-subbar">
                <div class="list-chips">
                    <a href="{{ $listUrl(['search' => null]) }}" class="filter-chip" title="Remove filter">Search: <strong>{{ $state['search'] }}</strong> <i class="ti ti-x"></i></a>
                </div>
            </div>
        @endif
    </div>

    @if ($rules->isEmpty())
        <div class="card">
            <div class="empty-state tone-indigo">
                <span class="empty-state-icon"><i class="ti ti-scale"></i></span>
                <h5>{{ $counts['all'] ? 'No rules match' : 'No overtime rules yet' }}</h5>
                <p>{{ $counts['all'] ? 'Try another tab or clear the search.' : 'Set when overtime starts and how it is paid - for every shift, or per shift.' }}</p>
                @if ($counts['all'])
                    <a href="{{ route('admin.overtime-rules.index', ['status' => '']) }}" class="btn btn-light btn-sm">Show all rules</a>
                @elseif ($canCreate)
                    <a href="{{ route('admin.overtime-rules.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> Add rule</a>
                @endif
            </div>
        </div>
    @else
        <div class="policy-grid">
            @foreach ($rules as $rule)
                @php
                    $threshold = (float) $rule->daily_threshold_hours;
                    $multiplier = (float) $rule->multiplier;
                    $tone = $multiplierTone($multiplier);
                    $shiftStyle = $rule->shift ? ShiftTime::style($rule->shift) : null;
                    $example = min(24, ceil($threshold) + 2);
                @endphp
                <article class="policy-card tone-{{ $tone }} {{ $rule->is_active ? '' : 'is-inactive' }}">
                    <div class="policy-card-head">
                        <span class="policy-card-icon"><i class="ti ti-alarm"></i></span>
                        <div class="min-w-0 flex-grow-1">
                            <h5 class="policy-card-title">
                                @can('update', $rule)
                                    <a href="{{ route('admin.overtime-rules.edit', $rule) }}">{{ $rule->name }}</a>
                                @else
                                    {{ $rule->name }}
                                @endcan
                            </h5>
                            <div class="policy-card-sub">
                                @if ($rule->shift)
                                    <span class="tag tone-{{ $shiftStyle['tone'] }}"><i class="ti {{ $shiftStyle['icon'] }}"></i>{{ $rule->shift->name }}</span>
                                    @unless ($rule->shift->is_active)
                                        <span class="tag tag-outline tone-slate">shift inactive</span>
                                    @endunless
                                @else
                                    <span class="tag tone-violet"><i class="ti ti-layers-intersect"></i>All shifts</span>
                                @endif
                            </div>
                        </div>
                        @can('update', $rule)
                            <form method="POST" action="{{ route('admin.overtime-rules.toggle', $rule) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="switch-btn {{ $rule->is_active ? 'is-on' : '' }}" role="switch" aria-checked="{{ $rule->is_active ? 'true' : 'false' }}"
                                        title="{{ $rule->is_active ? 'Active - click to deactivate' : 'Inactive - click to activate' }}">
                                    <span class="visually-hidden">{{ $rule->is_active ? 'Deactivate' : 'Activate' }} {{ $rule->name }}</span>
                                </button>
                            </form>
                        @endcan
                    </div>

                    <div class="ot-figures">
                        <div>
                            <span class="shift-time-label">Overtime after</span>
                            <span class="ot-figure">{{ $fmt($threshold) }}<small> h / day</small></span>
                        </div>
                        <div class="text-end">
                            <span class="shift-time-label">{{ $multiplierLabel($multiplier) }}</span>
                            <span class="ot-figure tone-text">{{ $fmt($multiplier) }}<small>×</small></span>
                        </div>
                    </div>

                    {{-- The working day: regular hours up to the threshold, overtime after --}}
                    <div class="ot-day" title="Regular up to {{ $fmt($threshold) }} h, overtime after">
                        <span class="ot-day-regular" style="width: {{ min(100, $threshold / 16 * 100) }}%"></span>
                        <span class="ot-day-overtime" style="left: {{ min(100, $threshold / 16 * 100) }}%"></span>
                    </div>
                    <div class="ot-day-scale"><span>0h</span><span>4h</span><span>8h</span><span>12h</span><span>16h</span></div>

                    <div class="ot-example">
                        <i class="ti ti-calculator"></i>
                        Work {{ $example }} h → <strong>{{ $fmt($example - $threshold) }} h</strong> overtime, paid as <strong>{{ $fmt(($example - $threshold) * $multiplier) }} h</strong>
                    </div>

                    <div class="policy-card-actions">
                        <span class="list-subtext">Updated {{ $rule->updated_at?->diffForHumans() }}</span>
                        <div class="ms-auto d-flex gap-1">
                            @can('update', $rule)
                                <a href="{{ route('admin.overtime-rules.edit', $rule) }}" class="btn-icon btn-icon-accent" title="Edit"><i class="ti ti-pencil font-16"></i></a>
                            @endcan
                            @can('delete', $rule)
                                <form action="{{ route('admin.overtime-rules.destroy', $rule) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this rule?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            @endcan
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
@endsection
