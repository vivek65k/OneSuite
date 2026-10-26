@extends('layouts.admin')

@section('title', 'Resignation')

@php
    use App\Models\Resignation;
    use App\Models\SeparationClearance;
    use Illuminate\Support\Str;

    $pageUrl = fn (array $overrides = []) => route('admin.resignations.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $statusMeta = [
        'pending' => ['Waiting for a decision', 'amber', 'ti-clock'],
        'accepted' => ['Serving notice', 'indigo', 'ti-history'],
        'completed' => ['Left', 'slate', 'ti-logout'],
        'withdrawn' => ['Withdrawn', 'success', 'ti-arrow-back-up'],
    ];
    $tabs = [
        '' => ['label' => 'All', 'tone' => 'indigo', 'count' => $counts['all']],
        'pending' => ['label' => 'Pending', 'tone' => 'amber', 'count' => $counts['pending']],
        'accepted' => ['label' => 'Serving notice', 'tone' => 'indigo', 'count' => $counts['accepted']],
        'completed' => ['label' => 'Left', 'tone' => 'slate', 'count' => $counts['completed']],
        'withdrawn' => ['label' => 'Withdrawn', 'tone' => 'success', 'count' => $counts['withdrawn']],
    ];
    $filterValues = [
        'employee_id' => $employees[$state['employee_id']] ?? null,
        'department_id' => $departments[$state['department_id']] ?? null,
        'search' => $state['search'],
    ];
    $activeFilters = collect(['employee_id' => 'Employee', 'department_id' => 'Department', 'search' => 'Search'])
        ->filter(fn ($label, $key) => filled($state[$key]))
        ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => $filterValues[$key] ?? 'Unknown']);
    $oldEmployee = (string) old('employee_id', '');
    $fromEdit = old('_method') === 'PUT';
    $attritionPct = ($kpis['staff'] + $kpis['left_year']) > 0 ? round($kpis['left_year'] / ($kpis['staff'] + $kpis['left_year']) * 100) : null;
@endphp

@section('content')
    <x-page-title title="Resignation" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Waiting for a decision" :value="$kpis['pending']" icon="ti-clock" :tone="$kpis['pending'] ? 'amber' : 'success'" :hint="$kpis['pending'] ? 'Accept or withdraw' : 'Nothing to decide'" />
        <x-kpi-card label="Serving notice" :value="$kpis['serving']" icon="ti-history" tone="indigo" :hint="$kpis['next'] ? 'Next to leave on '.$kpis['next']->format('M j') : ($kpis['serving'] ? 'All past their last day' : 'Nobody is on notice')" />
        <x-kpi-card label="Leaving within 30 days" :value="$kpis['month']" icon="ti-calendar-event" :tone="$kpis['overdue'] ? 'rose' : ($kpis['month'] ? 'amber' : 'teal')" :hint="$kpis['overdue'] ? $kpis['overdue'].' past their last day, not closed yet' : ($kpis['month'] ? 'Start handover and clearance' : 'Nobody leaves this month')" />
        <x-kpi-card :label="'Left in '.today()->year" :value="$kpis['left_year']" icon="ti-logout" tone="slate" :hint="$kpis['left_year'] && $attritionPct !== null ? 'About '.$attritionPct.'% of the team' : 'Nobody has left this year'" />
    </div>

    @if ($canCreate)
        <div class="row g-3 mb-3">
            <div class="col-xl-7">
                <form method="POST" action="{{ route('admin.resignations.store') }}" class="card form-section tone-rose mb-0 h-100" id="resign-form">
                    @csrf
                    <div class="form-section-head">
                        <span class="form-section-icon"><i class="ti ti-logout"></i></span>
                        <div class="flex-grow-1">
                            <h5 class="form-section-title">Record a resignation</h5>
                            <p class="form-section-sub">Enter it when an employee hands in their notice. Nothing changes for them until it is accepted.</p>
                        </div>
                    </div>
                    <div class="form-section-body">
                        @if ($formPeople->isEmpty())
                            <div class="run-note tone-amber"><i class="ti ti-alert-triangle"></i><span>Every current employee already has a resignation under way, or there are no active employees.</span></div>
                        @else
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="resign-employee">Employee</label>
                                    <select name="employee_id" id="resign-employee" class="form-select @error('employee_id') is-invalid @enderror" required>
                                        <option value="">Select employee</option>
                                        @foreach ($formPeople as $person)
                                            <option value="{{ $person->id }}" data-name="{{ $person->name }}" data-joined="{{ $person->joining_date?->toDateString() }}" data-meta="{{ collect([$person->designation, $person->department?->name])->filter()->implode(' · ') }}" @selected(! $fromEdit && $oldEmployee === (string) $person->id)>{{ $person->name }}{{ $person->employee_code ? ' ('.$person->employee_code.')' : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="resign-date">Resigned on</label>
                                    <input type="date" name="resignation_date" id="resign-date" value="{{ $fromEdit ? today()->toDateString() : old('resignation_date', today()->toDateString()) }}" min="{{ $limits['min'] }}" max="{{ $limits['max'] }}" class="form-control @error('resignation_date') is-invalid @enderror" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="resign-notice">Notice period (days)</label>
                                    <input type="number" min="0" max="{{ $limits['notice'] }}" step="1" name="notice_period_days" id="resign-notice" value="{{ $fromEdit ? 30 : old('notice_period_days', 30) }}" class="form-control @error('notice_period_days') is-invalid @enderror" required>
                                    <div class="quick-chips">
                                        @foreach ([0 => 'None', 15 => '15', 30 => '30', 60 => '60', 90 => '90'] as $days => $label)
                                            <button type="button" class="quick-chip" data-notice="{{ $days }}">{{ $label }}</button>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="resign-last">Last working day</label>
                                    <input type="date" name="last_working_day" id="resign-last" value="{{ $fromEdit ? '' : old('last_working_day') }}" class="form-control @error('last_working_day') is-invalid @enderror" required>
                                    <div class="font-12 text-muted mt-1" id="resign-last-note">Worked out from the notice period. Change it for an early release.</div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="resign-reason">Reason <span class="text-muted fw-normal">(optional)</span></label>
                                    <input type="text" name="reason" id="resign-reason" value="{{ $fromEdit ? '' : old('reason') }}" maxlength="2000" class="form-control" placeholder="Why they are leaving">
                                    <div class="quick-chips">
                                        @foreach (['Better opportunity', 'Higher studies', 'Relocation', 'Personal reasons', 'Health'] as $reason)
                                            <button type="button" class="quick-chip" data-reason="{{ $reason }}">{{ $reason }}</button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="submit" class="btn btn-de-primary text-light px-4"><i class="ti ti-check font-14 me-1"></i> Record resignation</button>
                            </div>
                        @endif
                    </div>
                </form>
            </div>
            <div class="col-xl-5">
                <div class="card side-list tone-teal mb-0 h-100">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-eye"></i></span>
                        <span class="fw-semibold" id="sum-title">What happens next</span>
                    </div>
                    <div class="card-body">
                        <div class="rev-compare">
                            <div>
                                <span class="rev-compare-label">Resigned</span>
                                <span class="rev-compare-value promo-title" id="sum-from">—</span>
                                <span class="rev-compare-meta" id="sum-who"></span>
                            </div>
                            <i class="ti ti-arrow-right"></i>
                            <div>
                                <span class="rev-compare-label">Last working day</span>
                                <span class="rev-compare-value promo-title" id="sum-to">—</span>
                                <span class="rev-compare-meta" id="sum-length"></span>
                            </div>
                        </div>
                        <div class="run-note tone-amber mt-3" id="sum-warn" hidden><i class="ti ti-alert-triangle"></i><span></span></div>
                        <div class="run-note tone-indigo mt-2"><i class="ti ti-info-circle"></i><span>Once accepted, a clearance checklist is created. The separation can be completed after the last working day and when clearance is done; that ends the employment record and switches off their login.</span></div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.resignations.index') }}">
            @if ($state['tab'] !== '')
                <input type="hidden" name="tab" value="{{ $state['tab'] }}">
            @endif
            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Resignation status">
                    @foreach ($tabs as $value => $tab)
                        @if ($value !== 'withdrawn' || $tab['count'] || $state['tab'] === 'withdrawn')
                            <a href="{{ $pageUrl(['tab' => $value ?: null]) }}" class="list-tab tone-{{ $tab['tone'] }} {{ $state['tab'] === $value ? 'active' : '' }}">
                                {{ $tab['label'] }}
                                <span class="list-tab-count">{{ $tab['count'] }}</span>
                            </a>
                        @endif
                    @endforeach
                </nav>
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] }}" placeholder="Search name or reason" aria-label="Search resignations">
                    </div>
                    @if ($departments->count() > 1)
                        <select name="department_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Department">
                            <option value="">All departments</option>
                            @foreach ($departments as $id => $name)
                                <option value="{{ $id }}" @selected($state['department_id'] === $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    @endif
                    @if ($employees->count() > 1 || $state['employee_id'])
                        <select name="employee_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Employee">
                            <option value="">All employees</option>
                            @foreach ($employees as $id => $name)
                                <option value="{{ $id }}" @selected($state['employee_id'] === $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>
            </div>
        </form>

        @if ($activeFilters->isNotEmpty())
            <div class="list-subbar">
                <div class="list-chips">
                    @foreach ($activeFilters as $filter)
                        <a href="{{ $pageUrl([$filter['key'] => null]) }}" class="filter-chip" title="Remove filter">{{ $filter['label'] }}: <strong>{{ $filter['value'] }}</strong> <i class="ti ti-x"></i></a>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="table-responsive table-responsive-sticky">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Employee</th>
                        <th>Resigned</th>
                        <th style="min-width: 180px">Last working day</th>
                        <th>Status</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($resignations as $r)
                        @php
                            $person = $r->employee;
                            $meta = $statusMeta[$r->status] ?? ['Unknown', 'slate', 'ti-circle'];
                            $pending = $r->status === 'pending';
                            $serving = $r->status === 'accepted';
                            $daysLeft = (int) today()->diffInDays($r->last_working_day, false);
                            $total = max(1, (int) $r->resignation_date->diffInDays($r->last_working_day));
                            $served = min(100, max(0, (int) round((int) $r->resignation_date->diffInDays(today(), false) / $total * 100)));
                            $cleared = $r->clearances->where('is_cleared', true)->count();
                            $clearTotal = $r->clearances->count();
                            $openDepartments = $r->clearances->where('is_cleared', false)->map(fn ($c) => SeparationClearance::DEPARTMENTS[$c->department] ?? $c->department);
                            $assets = (int) ($assetsHeld[$r->employee_id] ?? 0);
                            $own = $ownEmployeeId !== null && (int) $r->employee_id === (int) $ownEmployeeId;
                            // Why the separation can't be closed yet, if it can't.
                            $blocker = ! $serving ? null : ($daysLeft > 0 ? 'Their last working day is '.$r->last_working_day->format('M j, Y').'.' : ($openDepartments->isNotEmpty() ? 'Clearance is still open for '.$openDepartments->implode(', ').'.' : null));
                            $overdue = $serving && $daysLeft < 0;
                        @endphp
                        <tr class="{{ $pending || $overdue ? 'is-pending-row' : '' }}">
                            <td>
                                <div class="list-user">
                                    <x-avatar :person="$person" />
                                    <div class="list-user-text">
                                        <div class="list-user-name">{{ $person?->name ?? 'Removed employee' }}</div>
                                        <div class="list-user-meta">{{ collect([$person?->employee_code, $person?->designation, $person?->department?->name])->filter()->implode(' · ') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="text-nowrap">{{ $r->resignation_date->format('M j, Y') }}</div>
                                <div class="payslip-parts text-wrap" title="{{ $r->reason }}">{{ $r->reason ? Str::limit($r->reason, 50) : $r->notice_period_days.' days notice' }}</div>
                            </td>
                            <td>
                                <div class="text-nowrap fw-semibold">{{ $r->last_working_day->format('M j, Y') }}</div>
                                @if ($serving && $daysLeft >= 0)
                                    <div class="mini-progress tone-{{ $daysLeft <= 7 ? 'rose' : 'indigo' }}">
                                        <div class="mini-progress-bar is-toned"><span style="width: {{ $served }}%"></span></div>
                                        <span class="mini-progress-label">{{ $daysLeft === 0 ? 'today' : $daysLeft.' d' }}</span>
                                    </div>
                                @else
                                    <div class="payslip-parts {{ $overdue ? 'text-danger' : '' }}">
                                        @if ($pending)
                                            {{ $daysLeft > 0 ? 'in '.$daysLeft.' '.Str::plural('day', $daysLeft) : ($daysLeft === 0 ? 'today' : abs($daysLeft).' '.Str::plural('day', abs($daysLeft)).' ago') }} · {{ $r->notice_period_days }} days notice
                                        @elseif ($overdue)
                                            passed {{ abs($daysLeft) }} {{ Str::plural('day', abs($daysLeft)) }} ago, not closed
                                        @elseif ($r->status === 'completed')
                                            {{ $total }} days after resigning
                                        @else
                                            did not leave
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td>
                                <span class="tag tone-{{ $meta[1] }}"><i class="ti {{ $meta[2] }}"></i>{{ $meta[0] }}</span>
                                <div class="payslip-parts text-wrap">
                                    @if ($serving)
                                        {{ collect([
                                            $clearTotal ? 'Clearance '.$cleared.'/'.$clearTotal : null,
                                            $r->exitInterview ? 'exit interview done' : 'no exit interview',
                                            $r->finalSettlement ? 'settlement '.$r->finalSettlement->status : 'settlement not started',
                                            $assets ? $assets.' '.Str::plural('asset', $assets).' to return' : null,
                                        ])->filter()->implode(' · ') }}
                                    @elseif ($r->status === 'withdrawn')
                                        {{ $r->remarks ? Str::limit($r->remarks, 60) : ($r->reviewedBy ? 'by '.$r->reviewedBy->name : '') }}
                                    @elseif ($r->status === 'completed')
                                        {{ $r->finalSettlement ? 'Settlement '.$r->finalSettlement->status : 'No final settlement recorded' }}
                                    @endif
                                </div>
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="list-row-actions">
                                    @can('update', $r)
                                        @if ($own && ($pending || $serving))
                                            <span class="font-12 text-muted" title="Someone else has to handle your own resignation">Your own resignation</span>
                                        @elseif ($pending)
                                            <form method="POST" action="{{ route('admin.resignations.accept', $r) }}" class="d-inline" onsubmit="return confirm('Accept this resignation? The employee starts serving notice and a clearance checklist is created.');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm review-btn is-approve"><i class="ti ti-check"></i> Accept</button>
                                            </form>
                                        @elseif ($serving)
                                            @if ($blocker)
                                                <span class="btn btn-sm review-btn disabled" title="{{ $blocker }}" aria-disabled="true"><i class="ti ti-flag"></i> Complete</span>
                                            @else
                                                <button type="button" class="btn btn-sm review-btn is-approve" data-bs-toggle="modal" data-bs-target="#complete-modal"
                                                        data-action="{{ route('admin.resignations.complete', $r) }}" data-who="{{ $person?->name }}" data-last="{{ $r->last_working_day->format('M j, Y') }}" data-assets="{{ $assets }}" data-settlement="{{ $r->finalSettlement?->status }}" data-login="{{ $person?->user_id ? 1 : 0 }}"><i class="ti ti-flag"></i> Complete</button>
                                            @endif
                                        @endif
                                        @if (! $own && ($pending || $serving))
                                            <button type="button" class="btn-icon" title="Withdraw: the employee is staying" data-bs-toggle="modal" data-bs-target="#withdraw-modal"
                                                    data-action="{{ route('admin.resignations.withdraw', $r) }}" data-who="{{ $person?->name }}" data-serving="{{ $serving ? 1 : 0 }}"><i class="ti ti-arrow-back-up font-16"></i></button>
                                        @endif
                                        @if ($pending)
                                            <button type="button" class="btn-icon btn-icon-accent" title="Change resignation" data-bs-toggle="modal" data-bs-target="#resign-modal"
                                                    data-action="{{ route('admin.resignations.update', $r) }}" data-who="{{ $person?->name }}" data-date="{{ $r->resignation_date->toDateString() }}" data-notice="{{ $r->notice_period_days }}" data-last="{{ $r->last_working_day->toDateString() }}" data-reason="{{ $r->reason }}"><i class="ti ti-pencil font-16"></i></button>
                                        @endif
                                    @endcan
                                    @if ($pending)
                                        @can('delete', $r)
                                            <form action="{{ route('admin.resignations.destroy', $r) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this resignation? Use this only if it was entered by mistake; otherwise withdraw it.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-icon btn-icon-danger" title="Remove (entered by mistake)"><i class="ti ti-trash font-16"></i></button>
                                            </form>
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="ti ti-logout font-24 d-block mb-2"></i>
                                @if ($counts['all'] || $activeFilters->isNotEmpty() || $state['tab'] !== '')
                                    <div class="fw-semibold text-body mb-1">No resignations match</div>
                                    <span class="font-13"><a href="{{ route('admin.resignations.index') }}">Show all resignations</a></span>
                                @else
                                    <div class="fw-semibold text-body mb-1">No resignations on record</div>
                                    <span class="font-13">{{ $canCreate ? 'Record one with the form above when someone hands in their notice.' : 'Resignations appear here once they are recorded.' }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($resignations->hasPages())
            <div class="list-footer"><div class="ms-auto">{{ $resignations->links() }}</div></div>
        @endif
    </div>

    @if ($canReview)
        {{-- Withdraw --}}
        <div class="modal fade" id="withdraw-modal" tabindex="-1" aria-labelledby="withdraw-modal-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="#" class="modal-content" id="withdraw-form">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="withdraw-modal-title">Withdraw resignation</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="font-13 text-muted mb-3" id="withdraw-intro"></div>
                        <label class="form-label" for="withdraw-remarks">Note <span class="text-muted fw-normal">(optional)</span></label>
                        <textarea name="remarks" id="withdraw-remarks" rows="2" maxlength="1000" class="form-control" placeholder="e.g. Counter-offer accepted"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-de-primary text-light"><i class="ti ti-arrow-back-up font-14 me-1"></i> Withdraw</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Complete --}}
        <div class="modal fade" id="complete-modal" tabindex="-1" aria-labelledby="complete-modal-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="#" class="modal-content" id="complete-form">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="complete-modal-title">Complete separation</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3" id="complete-intro"></div>
                        <div class="run-note tone-rose"><i class="ti ti-alert-triangle"></i><span id="complete-effect"></span></div>
                        <div class="run-note tone-amber mt-2" id="complete-warn" hidden><i class="ti ti-alert-triangle"></i><span></span></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger"><i class="ti ti-flag font-14 me-1"></i> Complete separation</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Change a pending resignation --}}
        <div class="modal fade" id="resign-modal" tabindex="-1" aria-labelledby="resign-modal-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="#" class="modal-content" id="resign-edit">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="resign-modal-title">Change resignation</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="font-13 text-muted mb-3" id="edit-who"></div>
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label" for="edit-date">Resigned on</label>
                                <input type="date" name="resignation_date" id="edit-date" min="{{ $limits['min'] }}" max="{{ $limits['max'] }}" class="form-control" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="edit-notice">Notice period (days)</label>
                                <input type="number" min="0" max="{{ $limits['notice'] }}" step="1" name="notice_period_days" id="edit-notice" class="form-control" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="edit-last">Last working day</label>
                                <input type="date" name="last_working_day" id="edit-last" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="edit-reason">Reason</label>
                                <input type="text" name="reason" id="edit-reason" maxlength="2000" class="form-control">
                            </div>
                        </div>
                        <div class="font-12 text-muted mt-2" id="edit-note"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-de-primary text-light"><i class="ti ti-check font-14 me-1"></i> Save</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        (function () {
            var el = function (id) { return document.getElementById(id); };
            var today = @json(today()->toDateString());
            var addDays = function (iso, n) { var d = new Date(iso + 'T00:00:00'); d.setDate(d.getDate() + n); return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2); };
            var gap = function (a, b) { return Math.round((new Date(b + 'T00:00:00') - new Date(a + 'T00:00:00')) / 86400000); };
            var nice = function (s) { return new Date(s + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }); };
            // How the last working day compares with the notice period, in one sentence.
            var compare = function (date, notice, last) {
                if (!date || !last) return '';
                if (last < date) return 'The last working day is before the resignation date.';
                var served = gap(date, last);
                if (isNaN(notice)) return '';
                if (served < notice) return 'Leaving ' + (notice - served) + (notice - served === 1 ? ' day' : ' days') + ' before the ' + notice + '-day notice ends (early release).';
                if (served > notice) return 'Staying ' + (served - notice) + (served - notice === 1 ? ' day' : ' days') + ' beyond the ' + notice + '-day notice.';
                return '';
            };

            var withdraw = el('withdraw-modal');
            if (withdraw) {
                withdraw.addEventListener('show.bs.modal', function (event) {
                    var b = event.relatedTarget;
                    if (!b) return;
                    el('withdraw-form').action = b.dataset.action;
                    el('withdraw-intro').textContent = (b.dataset.who || 'The employee') + ' stays with the company.' + (b.dataset.serving === '1' ? ' Their notice period ends and the exit steps stop here.' : '');
                    el('withdraw-remarks').value = '';
                });

                el('complete-modal').addEventListener('show.bs.modal', function (event) {
                    var b = event.relatedTarget;
                    if (!b) return;
                    var who = b.dataset.who || 'The employee', assets = parseInt(b.dataset.assets, 10) || 0, settlement = b.dataset.settlement;
                    el('complete-form').action = b.dataset.action;
                    el('complete-intro').textContent = who + ' worked their last day on ' + b.dataset.last + ' and clearance is done.';
                    el('complete-effect').textContent = 'This marks ' + who + ' as having left' + (b.dataset.login === '1' ? ' and switches off their login' : '') + '. It can\'t be undone from here.';
                    var warn = [assets ? assets + (assets === 1 ? ' asset is' : ' assets are') + ' still recorded with them' : null, !settlement ? 'no final settlement has been worked out' : (settlement !== 'paid' ? 'the final settlement is not paid yet' : null)].filter(Boolean);
                    el('complete-warn').hidden = !warn.length;
                    el('complete-warn').querySelector('span').textContent = warn.length ? 'Before you go on: ' + warn.join(', and ') + '.' : '';
                });

                var editNote = function () { el('edit-note').textContent = compare(el('edit-date').value, parseInt(el('edit-notice').value, 10), el('edit-last').value); };
                el('resign-modal').addEventListener('show.bs.modal', function (event) {
                    var b = event.relatedTarget;
                    if (!b) return;
                    el('resign-edit').action = b.dataset.action;
                    el('edit-who').textContent = b.dataset.who || '';
                    el('edit-date').value = b.dataset.date; el('edit-notice').value = b.dataset.notice; el('edit-last').value = b.dataset.last; el('edit-reason').value = b.dataset.reason || '';
                    editNote();
                });
                ['edit-date', 'edit-notice', 'edit-last'].forEach(function (id) { el(id).addEventListener('input', editNote); });
            }

            var employee = el('resign-employee');
            if (!employee) return;
            var date = el('resign-date'), notice = el('resign-notice'), last = el('resign-last');
            var auto = last.value === '';

            function render(recalc) {
                var opt = employee.options[employee.selectedIndex];
                var n = parseInt(notice.value, 10);
                // Until the user picks a date themselves, the last day follows the notice period.
                if (recalc && auto && date.value && !isNaN(n)) last.value = addDays(date.value, n);
                last.min = date.value || '';
                el('sum-title').textContent = employee.value ? 'If ' + opt.dataset.name + ' leaves' : 'What happens next';
                el('sum-from').textContent = date.value ? nice(date.value) : '—';
                el('sum-who').textContent = employee.value ? opt.dataset.meta : '';
                el('sum-to').textContent = last.value ? nice(last.value) : '—';
                el('sum-length').textContent = date.value && last.value && last.value >= date.value ? (last.value === date.value ? 'leaves the same day' : gap(date.value, last.value) + ' days from resigning') + (last.value >= today ? ' · ' + (last.value === today ? 'today' : 'in ' + gap(today, last.value) + ' days') : ' · already passed') : '';
                var joined = employee.value ? opt.dataset.joined : '';
                var warn = joined && date.value && date.value < joined ? opt.dataset.name + ' joined on ' + nice(joined) + ', after this resignation date. Saving will be refused.' : compare(date.value, n, last.value);
                el('sum-warn').hidden = !warn;
                el('sum-warn').querySelector('span').textContent = warn;
            }

            employee.addEventListener('change', function () { render(false); });
            [date, notice].forEach(function (input) { input.addEventListener('input', function () { render(true); }); input.addEventListener('change', function () { render(true); }); });
            last.addEventListener('input', function () { auto = last.value === ''; render(false); });
            document.querySelectorAll('#resign-form [data-notice]').forEach(function (chip) {
                chip.addEventListener('click', function () { notice.value = chip.dataset.notice; auto = true; render(true); });
            });
            document.querySelectorAll('#resign-form [data-reason]').forEach(function (chip) {
                chip.addEventListener('click', function () { el('resign-reason').value = chip.dataset.reason; });
            });
            render(true);
        })();
    </script>
@endpush
