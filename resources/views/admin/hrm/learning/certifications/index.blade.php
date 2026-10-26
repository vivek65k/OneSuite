@extends('layouts.admin')

@section('title', 'Certifications')

@php
    use App\Http\Controllers\Admin\Hrm\CertificationController;
    use App\Models\Certification;
    use Illuminate\Support\Str;

    $soonDays = CertificationController::SOON_DAYS;
    $pageUrl = fn (array $overrides = []) => route('admin.certifications.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $tabs = [
        '' => ['label' => 'All', 'tone' => 'indigo', 'count' => $counts['all']],
        'valid' => ['label' => 'Valid', 'tone' => 'success', 'count' => $counts['valid']],
        'expiring' => ['label' => 'Expiring soon', 'tone' => 'amber', 'count' => $counts['expiring']],
        'expired' => ['label' => 'Expired', 'tone' => 'rose', 'count' => $counts['expired']],
    ];
    $filterValues = [
        'employee_id' => $employees[$state['employee_id']] ?? null,
        'department_id' => $departments[$state['department_id']] ?? null,
        'search' => $state['search'],
    ];
    $activeFilters = collect(['employee_id' => 'Employee', 'department_id' => 'Department', 'search' => 'Search'])
        ->filter(fn ($label, $key) => filled($state[$key]))
        ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => $filterValues[$key] ?? 'Unknown']);
    $oldEmployee = (string) old('employee_id', $state['employee_id'] ?? '');
    $canRenew = $canCreate;
@endphp

@section('content')
    <x-page-title title="Certifications" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Valid certificates" :value="$kpis['valid']" icon="ti-certificate" tone="success" :hint="$kpis['valid'] ? 'Currently in force' : 'None on record yet'" />
        <x-kpi-card label="Expiring soon" :value="$kpis['soon']" icon="ti-clock" :tone="$kpis['soon'] ? 'amber' : 'teal'" :hint="$kpis['next'] ? 'Next: '.Str::limit($kpis['next']->name, 22).' on '.$kpis['next']->expiry_date->format('M j') : 'Nothing runs out in the next '.$soonDays.' days'" />
        <x-kpi-card label="Expired, not renewed" :value="$kpis['lapsed']" icon="ti-alert-triangle" :tone="$kpis['lapsed'] ? 'rose' : 'success'" :hint="$kpis['lapsed'] ? $kpis['lapsed_people'].' '.Str::plural('person', $kpis['lapsed_people']).' to follow up' : 'Nothing has lapsed'" />
        <x-kpi-card label="People certified" :value="$kpis['staff'] ? $kpis['holders'].' / '.$kpis['staff'] : '—'" icon="ti-users" tone="indigo" :hint="$kpis['staff'] ? round($kpis['holders'] / $kpis['staff'] * 100).'% hold at least one valid certificate' : 'No active employees'" />
    </div>

    @if ($canCreate)
        <div class="row g-3 mb-3">
            <div class="col-xl-7">
                <form method="POST" action="{{ route('admin.certifications.store') }}" class="card form-section tone-indigo mb-0 h-100" id="cert-form">
                    @csrf
                    <div class="form-section-head">
                        <span class="form-section-icon"><i class="ti ti-certificate"></i></span>
                        <div class="flex-grow-1">
                            <h5 class="form-section-title">Record a certification</h5>
                            <p class="form-section-sub">Enter the certificate as it is printed. Leave the expiry empty if it never runs out.</p>
                        </div>
                    </div>
                    <div class="form-section-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="cert-employee">Employee</label>
                                <select name="employee_id" id="cert-employee" class="form-select @error('employee_id') is-invalid @enderror" required>
                                    <option value="">Select employee</option>
                                    @foreach ($formPeople as $person)
                                        <option value="{{ $person->id }}" @selected($oldEmployee === (string) $person->id)>{{ $person->name }}{{ $person->employee_code ? ' ('.$person->employee_code.')' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="cert-name">Certification</label>
                                <input type="text" name="name" id="cert-name" value="{{ old('name') }}" maxlength="255" list="cert-names" class="form-control @error('name') is-invalid @enderror" placeholder="e.g. First Aid, PMP, AWS Solutions Architect" required>
                                <datalist id="cert-names">@foreach ($names as $name)<option value="{{ $name }}"></option>@endforeach</datalist>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="cert-body">Issued by <span class="text-muted fw-normal">(optional)</span></label>
                                <input type="text" name="issuing_body" id="cert-body" value="{{ old('issuing_body') }}" maxlength="255" list="cert-bodies" class="form-control" placeholder="e.g. Red Cross, PMI">
                                <datalist id="cert-bodies">@foreach ($bodies as $body)<option value="{{ $body }}"></option>@endforeach</datalist>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="cert-credential">Credential ID <span class="text-muted fw-normal">(optional)</span></label>
                                <input type="text" name="credential_id" id="cert-credential" value="{{ old('credential_id') }}" maxlength="255" class="form-control @error('credential_id') is-invalid @enderror" placeholder="Number printed on the certificate">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="cert-issue">Issued on</label>
                                <input type="date" name="issue_date" id="cert-issue" value="{{ old('issue_date', today()->toDateString()) }}" max="{{ today()->toDateString() }}" class="form-control @error('issue_date') is-invalid @enderror" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="cert-expiry">Expires on <span class="text-muted fw-normal">(optional)</span></label>
                                <input type="date" name="expiry_date" id="cert-expiry" value="{{ old('expiry_date') }}" class="form-control @error('expiry_date') is-invalid @enderror">
                                <div class="quick-chips">
                                    @foreach ([1 => '1 year', 2 => '2 years', 3 => '3 years', 5 => '5 years'] as $years => $label)
                                        <button type="button" class="quick-chip" data-years="{{ $years }}">{{ $label }}</button>
                                    @endforeach
                                    <button type="button" class="quick-chip" data-years="0">No expiry</button>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-de-primary text-light px-4"><i class="ti ti-check font-14 me-1"></i> Record certification</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-xl-5">
                <div class="card side-list tone-teal mb-0 h-100">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-eye"></i></span>
                        <span class="fw-semibold">What you are recording</span>
                    </div>
                    <div class="card-body">
                        <div class="rev-compare">
                            <div>
                                <span class="rev-compare-label">Certificate</span>
                                <span class="rev-compare-value promo-title" id="sum-name">Enter the name</span>
                                <span class="rev-compare-meta" id="sum-who"></span>
                            </div>
                            <i class="ti ti-arrow-right"></i>
                            <div>
                                <span class="rev-compare-label">Valid for</span>
                                <span class="rev-compare-value promo-title" id="sum-length">—</span>
                                <span class="rev-compare-meta" id="sum-until"></span>
                            </div>
                        </div>
                        <div class="run-note tone-amber mt-3" id="sum-warn" hidden><i class="ti ti-alert-triangle"></i><span></span></div>
                        <div class="run-note tone-indigo mt-2"><i class="ti ti-info-circle"></i><span>A certificate turns "expired" by itself the day after its expiry date, and shows as "expiring soon" {{ $soonDays }} days before.</span></div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.certifications.index') }}">
            @if ($state['tab'] !== '')
                <input type="hidden" name="tab" value="{{ $state['tab'] }}">
            @endif
            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Certificate status">
                    @foreach ($tabs as $value => $tab)
                        <a href="{{ $pageUrl(['tab' => $value ?: null]) }}" class="list-tab tone-{{ $tab['tone'] }} {{ $state['tab'] === $value ? 'active' : '' }}">
                            {{ $tab['label'] }}
                            <span class="list-tab-count">{{ $tab['count'] }}</span>
                        </a>
                    @endforeach
                </nav>
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] }}" placeholder="Search name, certificate or ID" aria-label="Search certifications">
                    </div>
                    @if ($departments->count() > 1)
                        <select name="department_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Department">
                            <option value="">All departments</option>
                            @foreach ($departments as $id => $name)
                                <option value="{{ $id }}" @selected($state['department_id'] === $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    @endif
                    <select name="employee_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Employee">
                        <option value="">All employees</option>
                        @foreach ($employees as $id => $name)
                            <option value="{{ $id }}" @selected($state['employee_id'] === $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                    @if ($counts['all'])
                        <a href="{{ route('admin.certifications.export', array_filter($state, fn ($v) => $v !== null && $v !== '')) }}" class="btn btn-light btn-sm px-3" title="Download this list as a spreadsheet"><i class="ti ti-download font-14 me-1"></i> Export</a>
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
                        <th>Certification</th>
                        <th>Issued</th>
                        <th style="min-width: 170px">Expires</th>
                        <th>Status</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($certifications as $c)
                        @php
                            $passed = $c->expiry_date && $c->expiry_date->lt(today());
                            $expired = $c->status === 'expired';
                            $withdrawn = $expired && ! $passed;
                            $daysLeft = $c->expiry_date ? (int) today()->diffInDays($c->expiry_date, false) : null;
                            $soon = ! $expired && $daysLeft !== null && $daysLeft <= $soonDays;
                            $wasRenewed = $renewed->has($c->id);
                            $status = match (true) {
                                $expired && $wasRenewed => ['Renewed since', 'slate', 'ti-rotate'],
                                $withdrawn => ['No longer valid', 'slate', 'ti-ban'],
                                $expired => ['Expired', 'rose', 'ti-alert-triangle'],
                                $soon => ['Expiring soon', 'amber', 'ti-clock'],
                                default => ['Valid', 'success', 'ti-circle-check'],
                            };
                            // How much of the validity period has been used up.
                            $span = $c->expiry_date ? max(1, (int) $c->issue_date->diffInDays($c->expiry_date)) : null;
                            $used = $span ? min(100, max(0, round((int) $c->issue_date->diffInDays(today(), false) / $span * 100))) : null;
                        @endphp
                        <tr class="{{ ($expired && ! $wasRenewed && ! $withdrawn) || $soon ? 'is-pending-row' : '' }}">
                            <td>
                                <div class="list-user">
                                    <x-avatar :person="$c->employee" />
                                    <div class="list-user-text">
                                        <div class="list-user-name">{{ $c->employee?->name ?? 'Removed employee' }}</div>
                                        <div class="list-user-meta">{{ collect([$c->employee?->employee_code, $c->employee?->department?->name])->filter()->implode(' · ') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $c->name }}</div>
                                <div class="payslip-parts text-wrap">{{ collect([$c->issuing_body, $c->credential_id ? 'ID '.$c->credential_id : null])->filter()->implode(' · ') }}</div>
                            </td>
                            <td class="text-nowrap">{{ $c->issue_date->format('M j, Y') }}</td>
                            <td>
                                @if ($c->expiry_date)
                                    <div class="text-nowrap">{{ $c->expiry_date->format('M j, Y') }}</div>
                                    @if (! $expired)
                                        <div class="mini-progress tone-{{ $soon ? 'amber' : 'teal' }}">
                                            <div class="mini-progress-bar is-toned"><span style="width: {{ $used }}%"></span></div>
                                            <span class="mini-progress-label">{{ $daysLeft === 0 ? 'today' : ($daysLeft < 60 ? $daysLeft.' d' : ($daysLeft < 730 ? round($daysLeft / 30).' mo' : round($daysLeft / 365, 1).' y')) }}</span>
                                        </div>
                                    @elseif ($passed)
                                        <div class="payslip-parts">{{ abs($daysLeft) }} {{ Str::plural('day', abs($daysLeft)) }} ago</div>
                                    @endif
                                @else
                                    <span class="text-muted">No expiry</span>
                                @endif
                            </td>
                            <td><span class="tag tone-{{ $status[1] }}"><i class="ti {{ $status[2] }}"></i>{{ $status[0] }}</span></td>
                            <td class="text-end text-nowrap">
                                <div class="list-row-actions">
                                    @can('update', $c)
                                        @if ($canRenew && $c->expiry_date && ! $wasRenewed && ! $withdrawn && ($expired || $soon))
                                            <button type="button" class="btn btn-sm review-btn is-approve" data-bs-toggle="modal" data-bs-target="#cert-modal" data-mode="renew"
                                                    data-action="{{ route('admin.certifications.renew', $c) }}" data-name="{{ $c->name }}" data-who="{{ $c->employee?->name }}" data-body="{{ $c->issuing_body }}" data-credential="{{ $c->credential_id }}"
                                                    data-issue="{{ $c->issue_date->toDateString() }}" data-expiry="{{ $c->expiry_date->toDateString() }}"><i class="ti ti-rotate"></i> Renew</button>
                                        @endif
                                        @if ($withdrawn)
                                            <form method="POST" action="{{ route('admin.certifications.update', $c) }}" class="d-inline">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="status" value="active">
                                                <button type="submit" class="btn-icon" title="Make valid again"><i class="ti ti-arrow-back-up font-16"></i></button>
                                            </form>
                                        @elseif (! $expired)
                                            <form method="POST" action="{{ route('admin.certifications.update', $c) }}" class="d-inline" onsubmit="return confirm('Mark this certificate as no longer valid? Use this when it was withdrawn or revoked before its expiry date.');">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="status" value="expired">
                                                <button type="submit" class="btn-icon" title="No longer valid (withdrawn)"><i class="ti ti-ban font-16"></i></button>
                                            </form>
                                        @endif
                                        <button type="button" class="btn-icon btn-icon-accent" title="Change details" data-bs-toggle="modal" data-bs-target="#cert-modal" data-mode="edit"
                                                data-action="{{ route('admin.certifications.update', $c) }}" data-name="{{ $c->name }}" data-who="{{ $c->employee?->name }}" data-body="{{ $c->issuing_body }}" data-credential="{{ $c->credential_id }}"
                                                data-issue="{{ $c->issue_date->toDateString() }}" data-expiry="{{ $c->expiry_date?->toDateString() }}"><i class="ti ti-pencil font-16"></i></button>
                                    @endcan
                                    @can('delete', $c)
                                        <form action="{{ route('admin.certifications.destroy', $c) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this certification from the record? If it only ran out, leave it; expired certificates are kept as history.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="ti ti-certificate font-24 d-block mb-2"></i>
                                @if ($counts['all'] || $activeFilters->isNotEmpty())
                                    <div class="fw-semibold text-body mb-1">No certifications match</div>
                                    <span class="font-13"><a href="{{ route('admin.certifications.index') }}">Show all certifications</a></span>
                                @else
                                    <div class="fw-semibold text-body mb-1">No certifications recorded yet</div>
                                    <span class="font-13">{{ $canCreate ? 'Record the first one with the form above.' : 'Certifications recorded by HR will appear here.' }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($certifications->hasPages())
            <div class="list-footer"><div class="ms-auto">{{ $certifications->links() }}</div></div>
        @endif
    </div>

    <div class="modal fade" id="cert-modal" tabindex="-1" aria-labelledby="cert-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="#" class="modal-content" id="cert-edit">
                @csrf
                <input type="hidden" name="_method" id="edit-method" value="PUT">
                <div class="modal-header">
                    <h5 class="modal-title" id="cert-modal-title">Change certification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="font-13 text-muted mb-3" id="edit-intro"></div>
                    <div class="row g-3">
                        <div class="col-12" data-edit-only>
                            <label class="form-label" for="edit-name">Certification</label>
                            <input type="text" name="name" id="edit-name" maxlength="255" list="cert-names" class="form-control" required>
                        </div>
                        <div class="col-12" data-edit-only>
                            <label class="form-label" for="edit-body">Issued by</label>
                            <input type="text" name="issuing_body" id="edit-body" maxlength="255" list="cert-bodies" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="edit-credential">Credential ID</label>
                            <input type="text" name="credential_id" id="edit-credential" maxlength="255" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="edit-issue">Issued on</label>
                            <input type="date" name="issue_date" id="edit-issue" max="{{ today()->toDateString() }}" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="edit-expiry">Expires on</label>
                            <input type="date" name="expiry_date" id="edit-expiry" class="form-control">
                        </div>
                    </div>
                    <div class="font-12 text-muted mt-2" id="edit-note"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-de-primary text-light"><i class="ti ti-check font-14 me-1"></i> <span id="edit-submit">Save</span></button>
                </div>
            </form>
        </div>
    </div>
    @unless ($canCreate)
        <datalist id="cert-names">@foreach ($names as $name)<option value="{{ $name }}"></option>@endforeach</datalist>
        <datalist id="cert-bodies">@foreach ($bodies as $body)<option value="{{ $body }}"></option>@endforeach</datalist>
    @endunless
@endsection

@push('scripts')
    <script>
        (function () {
            var el = function (id) { return document.getElementById(id); };
            var today = @json(today()->toDateString());
            var soonDays = {{ $soonDays }};
            var pad = function (n) { return ('0' + n).slice(-2); };
            var iso = function (d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); };
            var addYears = function (s, n) { var d = new Date(s + 'T00:00:00'); d.setFullYear(d.getFullYear() + n); return iso(d); };
            var days = function (a, b) { return Math.round((new Date(b + 'T00:00:00') - new Date(a + 'T00:00:00')) / 86400000); };
            var nice = function (s) { return new Date(s + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }); };
            // "2 years", "18 months", "45 days"
            var length = function (n) { return n >= 365 && n % 365 < 3 ? Math.round(n / 365) + (Math.round(n / 365) === 1 ? ' year' : ' years') : (n >= 60 ? Math.round(n / 30.44) + ' months' : n + (n === 1 ? ' day' : ' days')); };
            // What a pair of dates means, in one sentence; empty when all is fine.
            var problem = function (issue, expiry) {
                if (issue && issue > today) return 'The issue date is in the future.';
                if (issue && expiry && expiry <= issue) return 'The expiry date must be after the issue date.';
                if (expiry && expiry < today) return 'This certificate has already run out, so it will be listed as expired.';
                if (expiry && days(today, expiry) <= soonDays) return 'This runs out in ' + days(today, expiry) + ' days, so it will show as expiring soon.';
                return '';
            };

            // Edit / renew dialog
            var modal = el('cert-modal');
            if (modal) {
                var mode = 'edit';
                var note = function () {
                    var issue = el('edit-issue').value, expiry = el('edit-expiry').value;
                    el('edit-note').textContent = mode === 'renew' && expiry && expiry < today ? 'A renewal can\'t already be expired.' : (problem(issue, expiry) || (issue && expiry ? 'Valid for ' + length(days(issue, expiry)) + '.' : (issue ? 'No expiry date: it stays valid.' : '')));
                };
                modal.addEventListener('show.bs.modal', function (event) {
                    var b = event.relatedTarget;
                    if (!b) return;
                    mode = b.dataset.mode;
                    var renew = mode === 'renew';
                    el('cert-edit').action = b.dataset.action;
                    el('edit-method').value = renew ? 'POST' : 'PUT';
                    el('cert-modal-title').textContent = renew ? 'Renew certification' : 'Change certification';
                    el('edit-submit').textContent = renew ? 'Renew' : 'Save';
                    el('edit-intro').textContent = renew
                        ? b.dataset.name + ' for ' + (b.dataset.who || 'this employee') + ' ran until ' + nice(b.dataset.expiry) + '. Enter the new certificate; the old one is kept as expired.'
                        : (b.dataset.who || '');
                    [].forEach.call(modal.querySelectorAll('[data-edit-only]'), function (row) { row.hidden = renew; row.querySelector('input').disabled = renew; });
                    el('edit-name').value = b.dataset.name; el('edit-body').value = b.dataset.body || '';
                    el('edit-credential').value = b.dataset.credential || '';
                    if (renew) {
                        // Same length as before, counted from today.
                        el('edit-issue').value = today; el('edit-issue').min = addYears(b.dataset.issue, 0);
                        var d = new Date(today + 'T00:00:00'); d.setDate(d.getDate() + days(b.dataset.issue, b.dataset.expiry));
                        el('edit-expiry').value = iso(d);
                    } else {
                        el('edit-issue').value = b.dataset.issue; el('edit-issue').removeAttribute('min'); el('edit-expiry').value = b.dataset.expiry || '';
                    }
                    note();
                });
                ['edit-issue', 'edit-expiry'].forEach(function (id) { el(id).addEventListener('input', note); el(id).addEventListener('change', note); });
            }

            // Record form
            var form = el('cert-form');
            if (!form) return;
            var employee = el('cert-employee'), name = el('cert-name'), issue = el('cert-issue'), expiry = el('cert-expiry');

            function render() {
                var who = employee.options[employee.selectedIndex];
                var ok = issue.value && expiry.value && expiry.value > issue.value;
                var warn = problem(issue.value, expiry.value);
                el('sum-name').textContent = name.value.trim() || 'Enter the name';
                el('sum-who').textContent = [employee.value ? who.textContent.trim() : null, el('cert-body').value.trim() || null].filter(Boolean).join(' · ');
                el('sum-length').textContent = ok ? length(days(issue.value, expiry.value)) : (expiry.value ? '—' : 'No expiry');
                el('sum-until').textContent = ok ? 'until ' + nice(expiry.value) : (expiry.value ? '' : 'Stays valid until withdrawn');
                el('sum-warn').hidden = !warn;
                el('sum-warn').querySelector('span').textContent = warn;
            }

            [employee, name, el('cert-body'), issue, expiry].forEach(function (input) { input.addEventListener('input', render); input.addEventListener('change', render); });
            form.querySelectorAll('[data-years]').forEach(function (chip) {
                chip.addEventListener('click', function () {
                    var years = parseInt(chip.dataset.years, 10);
                    if (years && !issue.value) { issue.focus(); return; }
                    expiry.value = years ? addYears(issue.value, years) : '';
                    render();
                });
            });
            render();
        })();
    </script>
@endpush
