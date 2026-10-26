@extends('layouts.admin')

@section('title', 'Transfer Approval')

@php
    use Illuminate\Support\Str;

    $pageUrl = fn (array $overrides = []) => route('admin.transfer-requests.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $tabs = [
        '' => ['label' => 'All transfers', 'tone' => 'indigo', 'count' => $counts['all']],
        'pending' => ['label' => 'Waiting for approval', 'tone' => 'amber', 'count' => $counts['pending']],
        'approved' => ['label' => 'Approved', 'tone' => 'success', 'count' => $counts['approved']],
        'rejected' => ['label' => 'Rejected', 'tone' => 'rose', 'count' => $counts['rejected']],
    ];
    $filterValues = ['employee_id' => $filterPeople->firstWhere('id', $state['employee_id'])?->name, 'search' => $state['search']];
    $activeFilters = collect(['employee_id' => 'Employee', 'search' => 'Search'])
        ->filter(fn ($label, $key) => filled($state[$key]))
        ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => $filterValues[$key] ?? 'Unknown']);
    $statusMeta = ['pending' => ['amber', 'ti-clock', 'Waiting for approval'], 'approved' => ['success', 'ti-circle-check', 'Approved'], 'rejected' => ['rose', 'ti-circle-x', 'Rejected']];
    $fields = ['employee_id', 'to_department_id', 'to_branch_id', 'transfer_date', 'notes'];
    $formErrors = $errors->hasAny($fields);
    $typed = collect($fields)->mapWithKeys(fn ($field) => [$field => is_scalar(old($field)) ? (string) old($field) : ''])->all();
    // What the form's script needs: where each person is now, and which branch each department belongs to.
    $peopleData = $people->mapWithKeys(fn ($p) => [$p->id => ['department' => $p->department_id, 'departmentName' => $p->department?->name, 'branch' => $p->branch_id, 'branchName' => $p->branch?->name]]);
@endphp

@section('content')
    <x-page-title title="Transfer Approval" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any() && ! $formErrors)
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Waiting for approval" :value="$kpis['pending']" icon="ti-clock" :tone="$kpis['pending'] ? 'amber' : 'success'" :hint="$kpis['pending'] ? ($kpis['oldest'] ? 'oldest proposed '.$kpis['oldest'].' '.Str::plural('day', $kpis['oldest']).' ago' : 'proposed today') : 'Nothing to decide'" :href="$kpis['pending'] ? $pageUrl(['tab' => 'pending']) : null" />
        <x-kpi-card label="Due within a week" :value="$kpis['due']" icon="ti-calendar-event" :tone="$kpis['due'] ? 'rose' : 'teal'" :hint="$kpis['due'] ? 'Effective date is close or has passed' : 'No pending transfer is due soon'" />
        <x-kpi-card label="Branch moves pending" :value="$kpis['branch_moves']" icon="ti-building" tone="violet" hint="Leave types, shifts and expense categories change with the branch" />
        <x-kpi-card label="Moved in {{ now()->year }}" :value="$kpis['moved_year']" icon="ti-arrows-left-right" tone="indigo" :hint="$kpis['rejected'] ? $kpis['rejected'].' rejected in the last 30 days' : 'Approved transfers'" />
    </div>

    @if ($canCreate)
        <form method="POST" action="{{ route('admin.transfer-requests.store') }}" class="card form-section tone-indigo" id="transfer-form">
            @csrf
            <div class="form-section-head">
                <span class="form-section-icon"><i class="ti ti-arrows-left-right"></i></span>
                <div class="flex-grow-1">
                    <h5 class="form-section-title">Propose a transfer</h5>
                    <p class="form-section-sub">Move someone to another department, another branch, or both. Nothing changes until it is approved.</p>
                </div>
            </div>
            <div class="form-section-body">
                @if ($formErrors)
                    <div class="alert alert-danger py-2 font-13">@foreach (collect($fields)->flatMap(fn ($field) => $errors->get($field)) as $message)<div>{{ $message }}</div>@endforeach</div>
                @endif
                @if ($people->isEmpty())
                    <div class="run-note tone-amber"><i class="ti ti-info-circle"></i><span>Everyone who can be transferred already has a transfer waiting for approval.</span></div>
                @else
                    <div class="row g-3">
                        <div class="col-md-6 col-lg-3">
                            <label class="form-label" for="transfer-person">Who? <span class="text-danger">*</span></label>
                            <select name="employee_id" id="transfer-person" class="form-select" required>
                                <option value="">Choose an employee</option>
                                @foreach ($people as $person)
                                    <option value="{{ $person->id }}" @selected($typed['employee_id'] === (string) $person->id)>{{ $person->name }} ({{ $person->employee_code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label class="form-label" for="transfer-branch">To branch</label>
                            <select name="to_branch_id" id="transfer-branch" class="form-select">
                                <option value="">Stays in the same branch</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected($typed['to_branch_id'] === (string) $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label class="form-label" for="transfer-department">To department</label>
                            <select name="to_department_id" id="transfer-department" class="form-select">
                                <option value="">Stays in the same department</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}" data-branch="{{ $department->branch_id }}" @selected($typed['to_department_id'] === (string) $department->id)>{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label class="form-label" for="transfer-date">Effective from <span class="text-danger">*</span></label>
                            <input type="date" name="transfer_date" id="transfer-date" class="form-control" value="{{ $typed['transfer_date'] }}" min="{{ $minDate }}" max="{{ $maxDate }}" required>
                            <div class="quick-chips">
                                <button type="button" class="quick-chip" data-date="today">Today</button>
                                <button type="button" class="quick-chip" data-date="month">1st of next month</button>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="transfer-notes">Why? <span class="text-danger">*</span></label>
                            <input type="text" name="notes" id="transfer-notes" class="form-control" value="{{ $typed['notes'] }}" maxlength="1000" placeholder="e.g. Taking over the Kolkata sales team" required>
                        </div>
                    </div>
                    <div class="run-note tone-indigo mt-3" id="transfer-preview"><i class="ti ti-info-circle"></i><span>Choose who is moving to see where they are now.</span></div>
                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn-de-primary text-light px-4" id="transfer-submit"><i class="ti ti-send font-14 me-1"></i> Send for approval</button>
                    </div>
                @endif
            </div>
        </form>
    @endif

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.transfer-requests.index') }}">
            @if ($state['tab'] !== '')
                <input type="hidden" name="tab" value="{{ $state['tab'] }}">
            @endif
            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Transfer status">
                    @foreach ($tabs as $value => $tab)
                        @if ($value === '' || $tab['count'] || $state['tab'] === $value)
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
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] }}" placeholder="Search employee or destination" aria-label="Search">
                    </div>
                    @if ($filterPeople->count() > 1 || $state['employee_id'])
                        <select name="employee_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Employee">
                            <option value="">Everyone</option>
                            @foreach ($filterPeople as $person)
                                <option value="{{ $person->id }}" @selected($state['employee_id'] === $person->id)>{{ $person->name }}</option>
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
                        <th>From</th>
                        <th>To</th>
                        <th>Effective</th>
                        <th style="width: 22%">Why</th>
                        <th>Status</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $r)
                        @php
                            $person = $r->employee;
                            $status = $statusMeta[$r->status] ?? ['slate', 'ti-point', Str::headline($r->status)];
                            $pending = $r->status === 'pending';
                            $own = $ownEmployeeId !== null && (int) $r->employee_id === (int) $ownEmployeeId;
                            $name = $person?->name ?? 'Removed employee';
                            $to = collect([$r->toDepartment?->name, $r->toBranch?->name])->filter()->implode(' in ') ?: 'a new position';
                        @endphp
                        <tr class="{{ $pending ? 'is-pending-row' : '' }}">
                            <td>
                                <div class="list-user">
                                    <x-avatar :person="$person" />
                                    <div class="list-user-text">
                                        <div class="list-user-name">{{ $name }}</div>
                                        <div class="list-user-meta">{{ collect([$person?->employee_code, $person?->designation])->filter()->implode(' · ') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if ($r->from_department || $r->from_branch)
                                    {{ $r->from_department ?? 'No department' }}
                                    <div class="payslip-parts">{{ $r->from_branch ?? 'No branch' }}</div>
                                @else
                                    <span class="text-muted">Not recorded</span>
                                @endif
                            </td>
                            <td>
                                @if ($r->toDepartment)
                                    <span class="tag tone-indigo"><i class="ti ti-sitemap"></i>{{ $r->toDepartment->name }}</span>
                                @endif
                                @if ($r->toBranch)
                                    <span class="tag tone-violet"><i class="ti ti-building"></i>{{ $r->toBranch->name }}</span>
                                @endif
                                <div class="payslip-parts">{{ $r->toDepartment && $r->toBranch ? 'new department and branch' : ($r->toBranch ? 'same department, new branch' : 'same branch') }}</div>
                            </td>
                            <td class="text-nowrap">
                                {{ $r->transfer_date->format('M j, Y') }}
                                @if ($pending)
                                    <div class="payslip-parts {{ $r->days_to_go < 0 ? 'text-danger' : '' }}">{{ $r->days_to_go < 0 ? abs($r->days_to_go).' '.Str::plural('day', abs($r->days_to_go)).' ago' : ($r->days_to_go === 0 ? 'today' : 'in '.$r->days_to_go.' '.Str::plural('day', $r->days_to_go)) }}</div>
                                @endif
                            </td>
                            <td><span title="{{ Str::length((string) $r->notes) > 90 ? $r->notes : '' }}">{{ $r->notes ? Str::limit($r->notes, 90) : '—' }}</span></td>
                            <td>
                                <span class="tag tone-{{ $status[0] }}"><i class="ti {{ $status[1] }}"></i>{{ $status[2] }}</span>
                                @if ($pending)
                                    <div class="payslip-parts">proposed {{ $r->waited === 0 ? 'today' : $r->waited.' '.Str::plural('day', $r->waited).' ago' }}</div>
                                @else
                                    <div class="payslip-parts">{{ collect([$r->reviewedBy?->name ? 'by '.$r->reviewedBy->name : null, $r->reviewed_at?->format('M j, Y')])->filter()->implode(' · ') }}</div>
                                @endif
                                @if ($r->remarks)
                                    <div class="payslip-parts text-danger" title="{{ $r->remarks }}">“{{ Str::limit($r->remarks, 70) }}”</div>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                @if ($pending)
                                    <div class="list-row-actions">
                                        @if ($canReview && $own)
                                            <span class="font-12 text-muted" title="Someone else has to decide your own transfer">Your own transfer</span>
                                        @elseif ($canReview)
                                            <form action="{{ route('admin.transfer-requests.approve', $r) }}" method="POST" class="d-inline" onsubmit="return confirm(@js('Approve and move '.$name.' to '.$to.' now?'.($r->days_to_go > 0 ? ' The effective date is '.$r->transfer_date->format('M j').', but they are moved as soon as you approve.' : '').($r->toBranch ? ' A branch move also changes which leave types, shifts and expense categories apply to them.' : '')))">
                                                @csrf
                                                <button type="submit" class="btn btn-sm review-btn is-approve"><i class="ti ti-check"></i> Approve</button>
                                            </form>
                                            <button type="button" class="btn btn-sm review-btn is-reject" data-bs-toggle="modal" data-bs-target="#reject-modal" data-action="{{ route('admin.transfer-requests.reject', $r) }}" data-who="{{ $name }}" data-to="{{ $to }}"><i class="ti ti-x"></i> Reject</button>
                                        @endif
                                        @if ($canDelete)
                                            <form action="{{ route('admin.transfer-requests.destroy', $r) }}" method="POST" class="d-inline" onsubmit="return confirm(@js('Withdraw the transfer proposed for '.$name.'?'))">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-icon btn-icon-danger" title="Withdraw this proposal"><i class="ti ti-trash font-16"></i></button>
                                            </form>
                                        @endif
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="ti ti-arrows-left-right font-24 d-block mb-2"></i>
                                @if ($activeFilters->isNotEmpty() || $state['tab'] !== '')
                                    <div class="fw-semibold text-body mb-1">{{ $state['tab'] === 'pending' && $activeFilters->isEmpty() ? 'Nothing is waiting for approval' : 'No transfers match' }}</div>
                                    <span class="font-13"><a href="{{ route('admin.transfer-requests.index') }}">Show all transfers</a></span>
                                @else
                                    <div class="fw-semibold text-body mb-1">No transfers proposed yet</div>
                                    <span class="font-13">Proposals appear here for approval; the employee moves only when one is approved.</span>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($canReview)
        <div class="modal fade" id="reject-modal" tabindex="-1" aria-labelledby="reject-modal-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="#" class="modal-content" id="reject-form">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="reject-modal-title">Reject transfer</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="font-13 text-muted mb-3" id="reject-intro"></div>
                        <label class="form-label" for="reject-remarks">Why? <span class="text-danger">*</span></label>
                        <input type="text" name="remarks" id="reject-remarks" minlength="3" maxlength="500" class="form-control" placeholder="e.g. The team can't spare them this quarter" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger"><i class="ti ti-x font-14 me-1"></i> Reject transfer</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        (function () {
            var $ = function (id) { return document.getElementById(id); };
            var modal = $('reject-modal');
            if (modal) {
                modal.addEventListener('show.bs.modal', function (event) {
                    var b = event.relatedTarget;
                    if (!b) return;
                    $('reject-form').action = b.dataset.action;
                    $('reject-intro').textContent = b.dataset.who + ' stays where they are instead of moving to ' + b.dataset.to + '.';
                    $('reject-remarks').value = '';
                });
            }

            var person = $('transfer-person');
            if (!person) return;
            var people = @json($peopleData), branch = $('transfer-branch'), department = $('transfer-department');
            var pad = function (n) { return String(n).padStart(2, '0'); };
            var iso = function (d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); };
            var label = function (select) { return select.selectedOptions[0] ? select.selectedOptions[0].textContent.trim() : ''; };

            function refresh() {
                var p = people[person.value], note = $('transfer-preview'), why = '', text = 'Choose who is moving to see where they are now.';
                // Departments that belong to another branch than the one they end up in can't be chosen.
                var endBranch = branch.value || (p ? String(p.branch || '') : '');
                Array.prototype.forEach.call(department.options, function (option) {
                    var owner = option.dataset.branch;
                    option.hidden = option.disabled = !!(option.value && owner && endBranch && owner !== endBranch);
                });
                if (department.selectedOptions[0] && department.selectedOptions[0].disabled) department.value = '';
                if (p) {
                    var from = (p.departmentName || 'No department') + ' in ' + (p.branchName || 'no branch');
                    var newBranch = branch.value && branch.value !== String(p.branch || ''), newDepartment = department.value && department.value !== String(p.department || '');
                    var current = Array.prototype.find.call(department.options, function (o) { return o.value === String(p.department || ''); });
                    if (!newBranch && !newDepartment) { text = 'Now: ' + from + '. Choose the department or branch they move to.'; why = branch.value || department.value ? 'They are already there. Choose a different department or branch.' : ''; }
                    else if (newBranch && !newDepartment && current && current.dataset.branch && current.dataset.branch !== branch.value) why = 'Their department (' + p.departmentName + ') doesn’t exist in ' + label(branch) + '. Choose the department they join there.';
                    else text = from + '  →  ' + (newDepartment ? label(department) : (p.departmentName || 'No department')) + ' in ' + (newBranch ? label(branch) : (p.branchName || 'no branch')) + '.' + (newBranch ? ' Leave types, shifts and expense categories change with the branch.' : '');
                }
                note.className = 'run-note mt-3 tone-' + (why ? 'rose' : 'indigo');
                note.querySelector('span').textContent = why || text;
                $('transfer-submit').disabled = !!why;
            }
            [person, branch, department].forEach(function (input) { input.addEventListener('change', refresh); });
            document.querySelectorAll('#transfer-form [data-date]').forEach(function (chip) {
                chip.addEventListener('click', function () { var d = new Date(); if (chip.dataset.date === 'month') d = new Date(d.getFullYear(), d.getMonth() + 1, 1); $('transfer-date').value = iso(d); });
            });
            $('transfer-form').addEventListener('submit', function () { setTimeout(function () { $('transfer-submit').disabled = true; }, 0); });
            refresh();
        })();
    </script>
@endpush
