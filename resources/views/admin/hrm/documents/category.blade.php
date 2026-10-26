@extends('layouts.admin')

@section('title', $typeLabel)

@php
    use App\Models\EmployeeDocument;
    use Illuminate\Support\Str;

    $meta = [
        'offer_letter' => ['icon' => 'ti-file-text', 'tone' => 'indigo', 'route' => 'admin.documents.offer-letters', 'plural' => 'Offer letters', 'one' => 'an offer letter', 'expires' => false],
        'appointment_letter' => ['icon' => 'ti-file-certificate', 'tone' => 'teal', 'route' => 'admin.documents.appointment-letters', 'plural' => 'Appointment letters', 'one' => 'an appointment letter', 'expires' => false],
        'contract' => ['icon' => 'ti-file-text', 'tone' => 'amber', 'route' => 'admin.documents.contracts', 'plural' => 'Contracts', 'one' => 'a contract', 'expires' => true],
        'pan' => ['icon' => 'ti-id', 'tone' => 'indigo', 'route' => 'admin.documents.pan', 'plural' => 'PAN cards', 'one' => 'a PAN card', 'expires' => false],
        'aadhar' => ['icon' => 'ti-id', 'tone' => 'teal', 'route' => 'admin.documents.aadhar', 'plural' => 'Aadhar cards', 'one' => 'an Aadhar card', 'expires' => false],
        'passport' => ['icon' => 'ti-id', 'tone' => 'rose', 'route' => 'admin.documents.passport', 'plural' => 'Passports', 'one' => 'a passport', 'expires' => true],
        'visa' => ['icon' => 'ti-plane', 'tone' => 'amber', 'route' => 'admin.documents.visa', 'plural' => 'Visas', 'one' => 'a visa', 'expires' => true],
        'certificate' => ['icon' => 'ti-certificate', 'tone' => 'success', 'route' => 'admin.documents.certificates', 'plural' => 'Certificates', 'one' => 'a certificate', 'expires' => true],
        'experience_letter' => ['icon' => 'ti-file-text', 'tone' => 'slate', 'route' => 'admin.documents.experience-letters', 'plural' => 'Experience letters', 'one' => 'an experience letter', 'expires' => false],
        'salary_slip' => ['icon' => 'ti-receipt', 'tone' => 'teal', 'route' => 'admin.documents.salary-slips', 'plural' => 'Salary slips', 'one' => 'a salary slip', 'expires' => false],
    ][$type];
    $pageUrl = fn (array $overrides = []) => route($meta['route'], array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $tabs = [
        '' => ['label' => 'All', 'tone' => 'indigo', 'count' => $counts['all']],
        'soon' => ['label' => 'Expiring soon', 'tone' => 'amber', 'count' => $counts['soon']],
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
    $fileMeta = ['pdf' => ['rose', 'ti-file-text', 'PDF'], 'doc' => ['indigo', 'ti-file-text', 'Word'], 'docx' => ['indigo', 'ti-file-text', 'Word'], 'jpg' => ['teal', 'ti-photo', 'Image'], 'jpeg' => ['teal', 'ti-photo', 'Image'], 'png' => ['teal', 'ti-photo', 'Image'], 'webp' => ['teal', 'ti-photo', 'Image']];
    $accept = '.'.implode(',.', EmployeeDocument::EXTENSIONS);
    $maxMb = EmployeeDocument::MAX_KILOBYTES / 1024;
    $oldEmployee = (string) old('employee_id', $state['employee_id'] ?? '');
    $canUpdate = auth()->user()->can('update', new EmployeeDocument);
    $problems = $kpis['soon'] + $kpis['expired'];
@endphp

@section('content')
    <x-page-title :title="$typeLabel" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card :label="$meta['plural'].' on file'" :value="$kpis['total']" :icon="$meta['icon']" :tone="$meta['tone']" :hint="$kpis['total'] ? ($kpis['month'] ? $kpis['month'].' added this month' : 'None added this month') : 'Nothing filed yet'" />
        <x-kpi-card label="Staff covered" :value="$kpis['staff'] ? $kpis['holders'].' / '.$kpis['staff'] : '—'" icon="ti-users" :tone="$expected && $kpis['staff'] && $kpis['holders'] < $kpis['staff'] ? 'amber' : 'teal'" :hint="$kpis['staff'] ? ($expected ? ($missing->count() ? $missing->count().' current '.Str::plural('employee', $missing->count()).' without '.$meta['one'] : 'Every current employee has '.$meta['one']) : 'Current employees with at least one on file') : 'No active employees'" />
        <x-kpi-card label="Expiring soon" :value="$kpis['soon']" icon="ti-clock" :tone="$kpis['soon'] ? 'amber' : 'success'" :hint="$kpis['soon'] ? 'Within the next '.$soonDays.' days' : ($kpis['next'] ? 'Next one runs out '.$kpis['next']->format('M j, Y') : ($kpis['dated'] ? 'None coming up' : 'No expiry dates recorded'))" />
        <x-kpi-card label="Expired" :value="$kpis['expired']" icon="ti-alert-triangle" :tone="$kpis['expired'] ? 'rose' : 'success'" :hint="$kpis['expired'] ? 'Need a new copy from the employee' : 'Nothing has run out'" />
    </div>

    @if ($canCreate)
        <div class="row g-3 mb-3">
            <div class="col-xl-7">
                <form method="POST" action="{{ route('admin.documents.store') }}" enctype="multipart/form-data" class="card form-section tone-{{ $meta['tone'] }} mb-0 h-100" id="document-form">
                    @csrf
                    <input type="hidden" name="category" value="{{ $type }}">
                    <div class="form-section-head">
                        <span class="form-section-icon"><i class="ti ti-upload"></i></span>
                        <div class="flex-grow-1">
                            <h5 class="form-section-title">File {{ $meta['one'] }}</h5>
                            <p class="form-section-sub">Upload a scan or the signed copy. Only people with access to employee documents, the employee and their managers can open it.</p>
                        </div>
                    </div>
                    <div class="form-section-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="document-employee">Employee</label>
                                <select name="employee_id" id="document-employee" class="form-select @error('employee_id') is-invalid @enderror" required>
                                    <option value="">Select employee</option>
                                    @foreach ($formPeople as $person)
                                        <option value="{{ $person->id }}" data-name="{{ $person->name }}" data-has="{{ $holderIds->has($person->id) ? 1 : 0 }}" @selected($oldEmployee === (string) $person->id)>{{ $person->name }}{{ $person->employee_code ? ' ('.$person->employee_code.')' : '' }}{{ $expected && $holderIds->has($person->id) ? ' — already on file' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="document-name">Document name</label>
                                <input type="text" name="name" id="document-name" value="{{ old('name') }}" maxlength="255" class="form-control @error('name') is-invalid @enderror" placeholder="e.g. {{ $typeLabel }} 2026" required>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label" for="document-file">File</label>
                                <input type="file" name="file" id="document-file" accept="{{ $accept }}" class="form-control @error('file') is-invalid @enderror" required>
                                <div class="font-12 text-muted mt-1" id="document-file-note">PDF, image or Word, up to {{ $maxMb }} MB.</div>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label" for="document-expiry">Expires on <span class="text-muted fw-normal">({{ $meta['expires'] ? 'if it has a date' : 'optional' }})</span></label>
                                <input type="date" name="expiry_date" id="document-expiry" value="{{ old('expiry_date') }}" class="form-control @error('expiry_date') is-invalid @enderror">
                            </div>
                        </div>
                        <div class="run-note tone-amber mt-3" id="document-warn" hidden><i class="ti ti-alert-triangle"></i><span></span></div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-de-primary text-light px-4"><i class="ti ti-upload font-14 me-1"></i> Upload</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-xl-5">
                @if ($expected)
                    <div class="card side-list tone-{{ $missing->isEmpty() ? 'success' : 'amber' }} mb-0 h-100">
                        <div class="side-list-head">
                            <span class="side-list-icon"><i class="ti ti-user-check"></i></span>
                            <span class="fw-semibold">Staff without {{ $meta['one'] }}</span>
                            <span class="side-list-count">{{ $missing->count() }}</span>
                        </div>
                        @forelse ($missing->take(6) as $person)
                            <a href="#document-form" class="side-list-item" data-pick-employee="{{ $person->id }}" title="Put {{ $person->name }} in the form">
                                <x-avatar :person="$person" />
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold">{{ $person->name }}</div>
                                    <div class="font-12 text-muted">{{ collect([$person->employee_code, $person->department?->name])->filter()->implode(' · ') }}</div>
                                </div>
                                <i class="ti ti-plus text-muted"></i>
                            </a>
                        @empty
                            <div class="card-body font-13 text-muted">{{ $formPeople->isEmpty() ? 'There are no active employees.' : 'Every current employee has '.$meta['one'].' on file.' }}</div>
                        @endforelse
                        @if ($missing->count() > 6)
                            <div class="card-body py-2 font-12 text-muted">and {{ $missing->count() - 6 }} more. They are the ones not marked "already on file" in the employee list.</div>
                        @endif
                    </div>
                @else
                    <div class="card side-list tone-teal mb-0 h-100">
                        <div class="side-list-head">
                            <span class="side-list-icon"><i class="ti ti-lock"></i></span>
                            <span class="fw-semibold">How these are kept</span>
                        </div>
                        <div class="card-body">
                            <div class="run-note tone-indigo"><i class="ti ti-lock"></i><span>Files are stored privately. They can't be reached by a web address; each one opens only after a login and permission check.</span></div>
                            <div class="run-note tone-indigo mt-2"><i class="ti ti-file-text"></i><span>Accepted: PDF, JPG, PNG, WebP and Word files, up to {{ $maxMb }} MB each.</span></div>
                            @if ($meta['expires'])
                                <div class="run-note tone-amber mt-2"><i class="ti ti-clock"></i><span>Enter the expiry date where there is one. It is flagged here {{ $soonDays }} days before it runs out.</span></div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <div class="card list-view">
        <form method="GET" action="{{ route($meta['route']) }}">
            @if ($state['tab'] !== '')
                <input type="hidden" name="tab" value="{{ $state['tab'] }}">
            @endif
            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Expiry">
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
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] }}" placeholder="Search employee or document name" aria-label="Search documents">
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
                        <th>Document</th>
                        <th>Added</th>
                        <th>Expires</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($documents as $document)
                        @php
                            $file = $fileMeta[$document->extension] ?? ['slate', 'ti-file', strtoupper($document->extension ?: 'File')];
                            $daysLeft = $document->expiry_date ? (int) today()->diffInDays($document->expiry_date, false) : null;
                            $expired = $daysLeft !== null && $daysLeft < 0;
                            $soon = $daysLeft !== null && ! $expired && $daysLeft <= $soonDays;
                        @endphp
                        <tr class="{{ $expired || $soon ? 'is-pending-row' : '' }}">
                            <td>
                                <div class="list-user">
                                    <x-avatar :person="$document->employee" />
                                    <div class="list-user-text">
                                        <div class="list-user-name">{{ $document->employee?->name ?? 'Removed employee' }}</div>
                                        <div class="list-user-meta">{{ collect([$document->employee?->employee_code, $document->employee?->department?->name])->filter()->implode(' · ') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="date-range-cell">
                                    <span class="list-avatar list-avatar-square tone-{{ $file[0] }}" aria-hidden="true"><i class="ti {{ $file[1] }} font-18"></i></span>
                                    <div>
                                        <a href="{{ $document->url }}" target="_blank" rel="noopener" class="fw-semibold text-body" title="Open the file">{{ $document->name }}</a>
                                        <div class="payslip-parts">{{ $file[2] }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-nowrap">{{ $document->created_at?->format('M j, Y') ?? '—' }}</td>
                            <td class="text-nowrap">
                                @if ($document->expiry_date)
                                    {{ $document->expiry_date->format('M j, Y') }}
                                    <div class="payslip-parts">
                                        @if ($expired)
                                            <span class="text-danger">expired {{ abs($daysLeft) }} {{ Str::plural('day', abs($daysLeft)) }} ago</span>
                                        @elseif ($soon)
                                            <span class="text-danger">{{ $daysLeft === 0 ? 'runs out today' : 'in '.$daysLeft.' '.Str::plural('day', $daysLeft) }}</span>
                                        @else
                                            in {{ $daysLeft < 730 ? round($daysLeft / 30).' months' : round($daysLeft / 365, 1).' years' }}
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted">No expiry</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="list-row-actions">
                                    <a href="{{ $document->url }}" target="_blank" rel="noopener" class="btn btn-sm review-btn"><i class="ti ti-eye"></i> Open</a>
                                    @can('update', $document)
                                        <button type="button" class="btn-icon btn-icon-accent" title="Change name, expiry or replace the file" data-bs-toggle="modal" data-bs-target="#document-modal"
                                                data-action="{{ route('admin.documents.update', $document) }}" data-who="{{ $document->employee?->name }}" data-name="{{ $document->name }}" data-expiry="{{ $document->expiry_date?->toDateString() }}"><i class="ti ti-pencil font-16"></i></button>
                                    @endcan
                                    @can('delete', $document)
                                        <form action="{{ route('admin.documents.destroy', $document) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this document? The file is removed for good.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon btn-icon-danger" title="Delete document"><i class="ti ti-trash font-16"></i></button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="ti {{ $meta['icon'] }} font-24 d-block mb-2"></i>
                                @if ($counts['all'] || $activeFilters->isNotEmpty() || $state['tab'] !== '')
                                    <div class="fw-semibold text-body mb-1">No {{ strtolower($meta['plural']) }} match</div>
                                    <span class="font-13"><a href="{{ route($meta['route']) }}">Show all {{ strtolower($meta['plural']) }}</a></span>
                                @else
                                    <div class="fw-semibold text-body mb-1">No {{ strtolower($meta['plural']) }} on file yet</div>
                                    <span class="font-13">{{ $canCreate ? 'Upload the first one with the form above.' : 'Documents filed by HR will appear here.' }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($documents->hasPages())
            <div class="list-footer"><div class="ms-auto">{{ $documents->links() }}</div></div>
        @endif
    </div>

    @if ($canUpdate)
        <div class="modal fade" id="document-modal" tabindex="-1" aria-labelledby="document-modal-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="#" enctype="multipart/form-data" class="modal-content" id="document-edit">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="document-modal-title">Change document</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="font-13 text-muted mb-3" id="edit-who"></div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="edit-name">Document name</label>
                                <input type="text" name="name" id="edit-name" maxlength="255" class="form-control" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="edit-expiry">Expires on</label>
                                <input type="date" name="expiry_date" id="edit-expiry" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="edit-file">Replace the file <span class="text-muted fw-normal">(optional)</span></label>
                                <input type="file" name="file" id="edit-file" accept="{{ $accept }}" class="form-control">
                                <div class="font-12 text-muted mt-1">Leave empty to keep the current file. A new one replaces it for good.</div>
                            </div>
                        </div>
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
            var allowed = @json(EmployeeDocument::EXTENSIONS);
            var maxBytes = {{ (int) EmployeeDocument::MAX_KILOBYTES }} * 1024;
            var today = @json(today()->toDateString());
            var typeLabel = @json($typeLabel);
            var expected = @json($expected);
            // What is wrong with a chosen file, before it is sent; empty when it is fine.
            var fileProblem = function (input) {
                var f = input.files && input.files[0];
                if (!f) return '';
                var ext = (f.name.split('.').pop() || '').toLowerCase();
                if (allowed.indexOf(ext) === -1) return '"' + f.name + '" is not a PDF, image or Word file. It will be refused.';
                if (f.size > maxBytes) return 'That file is ' + (f.size / 1048576).toFixed(1) + ' MB. The limit is ' + (maxBytes / 1048576) + ' MB.';
                return '';
            };

            var modal = el('document-modal');
            if (modal) {
                modal.addEventListener('show.bs.modal', function (event) {
                    var b = event.relatedTarget;
                    if (!b) return;
                    el('document-edit').action = b.dataset.action;
                    el('edit-who').textContent = b.dataset.who || '';
                    el('edit-name').value = b.dataset.name; el('edit-expiry').value = b.dataset.expiry || '';
                    el('edit-file').value = '';
                });
            }

            var employee = el('document-employee');
            if (!employee) return;
            var name = el('document-name'), file = el('document-file'), expiry = el('document-expiry'), warn = el('document-warn');
            var autoName = '';

            function render() {
                var opt = employee.options[employee.selectedIndex];
                var who = employee.value ? opt.dataset.name : '';
                var problem = fileProblem(file);
                var f = file.files && file.files[0];
                el('document-file-note').textContent = f && !problem ? f.name + ' · ' + (f.size < 1048576 ? Math.max(1, Math.round(f.size / 1024)) + ' KB' : (f.size / 1048576).toFixed(1) + ' MB') : 'PDF, image or Word, up to ' + (maxBytes / 1048576) + ' MB.';
                var text = problem
                    || (expiry.value && expiry.value < today ? 'That expiry date has already passed. It will be filed as expired.' : '')
                    || (expected && employee.value && opt.dataset.has === '1' ? who + ' already has one on file. This adds another; to swap the file, use the pencil on the existing one.' : '');
                warn.hidden = !text;
                warn.querySelector('span').textContent = text;
            }

            // Suggest a name from the person, until the user types their own.
            employee.addEventListener('change', function () {
                var opt = employee.options[employee.selectedIndex];
                if (employee.value && (name.value === '' || name.value === autoName)) { autoName = typeLabel + ' - ' + opt.dataset.name; name.value = autoName; }
                render();
            });
            [file, expiry].forEach(function (input) { input.addEventListener('change', render); });
            document.querySelectorAll('[data-pick-employee]').forEach(function (link) {
                link.addEventListener('click', function () {
                    employee.value = link.dataset.pickEmployee;
                    employee.dispatchEvent(new Event('change'));
                    file.focus();
                });
            });
            render();
        })();
    </script>
@endpush
