@extends('layouts.admin')

@section('title', 'Document Expiry')

@php
    use App\Models\EmployeeDocument;
    use Illuminate\Support\Str;

    $query = fn (array $overrides = []) => array_filter(array_merge($state, $overrides), fn ($value) => $value !== null && $value !== '');
    $pageUrl = fn (array $overrides = []) => route('admin.document-expiry.index', $query($overrides));
    $tabs = [
        '' => ['label' => 'All', 'tone' => 'indigo', 'count' => $counts['all']],
        'expired' => ['label' => 'Expired', 'tone' => 'rose', 'count' => $counts['expired']],
        'soon' => ['label' => 'Expiring soon', 'tone' => 'amber', 'count' => $counts['soon']],
        'valid' => ['label' => 'Valid', 'tone' => 'success', 'count' => $counts['valid']],
        'replaced' => ['label' => 'Replaced', 'tone' => 'slate', 'count' => $counts['replaced']],
    ];
    $statusMeta = [
        'expired' => ['Expired', 'rose', 'ti-alert-triangle'],
        'soon' => ['Expiring soon', 'amber', 'ti-clock'],
        'valid' => ['Valid', 'success', 'ti-circle-check'],
        'replaced' => ['Replaced', 'slate', 'ti-rotate'],
    ];
    // Where a new copy of each kind is uploaded.
    $typeRoutes = [
        'offer_letter' => 'admin.documents.offer-letters', 'appointment_letter' => 'admin.documents.appointment-letters', 'contract' => 'admin.documents.contracts',
        'pan' => 'admin.documents.pan', 'aadhar' => 'admin.documents.aadhar', 'passport' => 'admin.documents.passport', 'visa' => 'admin.documents.visa',
        'certificate' => 'admin.documents.certificates', 'experience_letter' => 'admin.documents.experience-letters', 'salary_slip' => 'admin.documents.salary-slips',
    ];
    $tones = ['indigo', 'teal', 'amber', 'rose', 'success', 'slate'];
    $typeTone = fn (?string $type) => $tones[abs(crc32((string) $type)) % count($tones)];
    $filterValues = [
        'category' => $types[$state['category']] ?? (EmployeeDocument::TYPES[$state['category']] ?? null),
        'employee_id' => $employees[$state['employee_id']] ?? null,
        'department_id' => $departments[$state['department_id']] ?? null,
        'search' => $state['search'],
        'left' => 'included',
    ];
    $activeFilters = collect(['category' => 'Type', 'employee_id' => 'Employee', 'department_id' => 'Department', 'search' => 'Search', 'left' => 'People who left'])
        ->filter(fn ($label, $key) => filled($state[$key]))
        ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => $filterValues[$key] ?? 'Unknown']);
    $canUpload = auth()->user()->can('create', EmployeeDocument::class);
    $attention = $kpis['expired'] + $kpis['soon'];
@endphp

@section('content')
    <x-page-title title="Document Expiry" />

    <div class="kpi-strip">
        <x-kpi-card label="Expired" :value="$kpis['expired']" icon="ti-alert-triangle" :tone="$kpis['expired'] ? 'rose' : 'success'" :hint="$kpis['expired'] ? 'For '.$kpis['expired_people'].' current '.Str::plural('employee', $kpis['expired_people']).', with no newer copy' : 'Nothing has run out'" />
        <x-kpi-card label="Expiring soon" :value="$kpis['soon']" icon="ti-clock" :tone="$kpis['soon'] ? 'amber' : 'success'" :hint="$kpis['soon'] ? ($kpis['month'] ? $kpis['month'].' within 30 days' : 'Within the next '.$soonDays.' days') : 'Nothing in the next '.$soonDays.' days'" />
        <x-kpi-card label="Next to run out" :value="$kpis['next'] ? $kpis['next']->format('M j') : '—'" icon="ti-calendar-event" tone="indigo" :hint="$kpis['next'] ? ($kpis['next']->isToday() ? 'Today' : 'In '.(int) today()->diffInDays($kpis['next']).' '.Str::plural('day', (int) today()->diffInDays($kpis['next'])).', '.$kpis['next']->format('Y')) : 'No dated document ahead'" />
        <x-kpi-card label="Valid" :value="$kpis['valid']" icon="ti-circle-check" tone="teal" :hint="$kpis['tracked'] ? 'Of '.$kpis['tracked'].' '.Str::plural('document', $kpis['tracked']).' with an expiry date' : 'No document has an expiry date yet'" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.document-expiry.index') }}">
            @foreach (['tab' => $state['tab'], 'left' => $state['left']] as $key => $value)
                @if ($value !== '')
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Expiry status">
                    @foreach ($tabs as $value => $tab)
                        @if ($value !== 'replaced' || $tab['count'] || $state['tab'] === 'replaced')
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
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] }}" placeholder="Search employee or document" aria-label="Search documents">
                    </div>
                    @if ($types->count() > 1 || $state['category'] !== '')
                        <select name="category" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Document type">
                            <option value="">All types</option>
                            @foreach ($types as $value => $label)
                                <option value="{{ $value }}" @selected($state['category'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    @endif
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
                    @if ($counts['all'] || $counts['replaced'])
                        <a href="{{ route('admin.document-expiry.export', $query()) }}" class="btn btn-light btn-sm px-3" title="Download exactly this list as a spreadsheet"><i class="ti ti-download font-14 me-1"></i> Export</a>
                    @endif
                </div>
            </div>
        </form>

        @if ($activeFilters->isNotEmpty() || ($kpis['left'] && ! $state['left']))
            <div class="list-subbar">
                <div class="list-chips">
                    @foreach ($activeFilters as $filter)
                        <a href="{{ $pageUrl([$filter['key'] => null]) }}" class="filter-chip" title="Remove filter">{{ $filter['label'] }}: <strong>{{ $filter['value'] }}</strong> <i class="ti ti-x"></i></a>
                    @endforeach
                </div>
                @if ($kpis['left'] && ! $state['left'])
                    <span class="font-13 text-muted ms-auto">{{ $kpis['left'] }} expired or expiring {{ Str::plural('document', $kpis['left']) }} of people who have left {{ $kpis['left'] === 1 ? 'is' : 'are' }} hidden. <a href="{{ $pageUrl(['left' => '1']) }}">Show {{ $kpis['left'] === 1 ? 'it' : 'them' }}</a></span>
                @endif
            </div>
        @endif

        <div class="table-responsive table-responsive-sticky">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Employee</th>
                        <th>Document</th>
                        <th style="min-width: 190px">Expires</th>
                        <th>Status</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($documents as $row)
                        @php
                            $document = $row['document'];
                            $meta = $statusMeta[$row['status']];
                            $days = $row['days'];
                            $typeLabel = EmployeeDocument::TYPES[$document->category] ?? ucfirst((string) $document->category);
                            // How much of the last "soon" window is left: a bar that empties as the date approaches.
                            $leftPct = $row['status'] === 'soon' ? max(4, min(100, (int) round($days / max(1, $soonDays) * 100))) : null;
                        @endphp
                        <tr class="{{ in_array($row['status'], ['expired', 'soon'], true) ? 'is-pending-row' : '' }}">
                            <td>
                                <div class="list-user">
                                    <x-avatar :person="$document->employee" />
                                    <div class="list-user-text">
                                        <div class="list-user-name">{{ $row['name'] }}</div>
                                        <div class="list-user-meta">
                                            @if ($row['left'])
                                                <span class="text-danger">Has left the company</span>
                                            @else
                                                {{ collect([$document->employee?->employee_code, $document->employee?->department?->name])->filter()->implode(' · ') }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="tag tone-{{ $typeTone($document->category) }}">{{ $typeLabel }}</span>
                                <div class="payslip-parts text-wrap"><a href="{{ $document->url }}" target="_blank" rel="noopener" class="text-reset" title="Open the file">{{ Str::limit($document->name, 60) }}</a></div>
                            </td>
                            <td>
                                <div class="text-nowrap fw-semibold">{{ $document->expiry_date->format('M j, Y') }}</div>
                                @if ($leftPct !== null)
                                    <div class="mini-progress tone-{{ $days <= 14 ? 'rose' : 'amber' }}">
                                        <div class="mini-progress-bar is-toned"><span style="width: {{ $leftPct }}%"></span></div>
                                        <span class="mini-progress-label">{{ $days === 0 ? 'today' : $days.' d' }}</span>
                                    </div>
                                @else
                                    <div class="payslip-parts {{ $row['status'] === 'expired' ? 'text-danger' : '' }}">
                                        @if ($days < 0)
                                            {{ abs($days) < 60 ? abs($days).' '.Str::plural('day', abs($days)) : (abs($days) < 730 ? round(abs($days) / 30).' months' : round(abs($days) / 365, 1).' years') }} ago
                                        @else
                                            in {{ $days < 730 ? round($days / 30).' months' : round($days / 365, 1).' years' }}
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td>
                                <span class="tag tone-{{ $meta[1] }}"><i class="ti {{ $meta[2] }}"></i>{{ $meta[0] }}</span>
                                @if ($row['status'] === 'replaced')
                                    <div class="payslip-parts">A newer copy is on file</div>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="list-row-actions">
                                    @if ($canUpload && ! $row['left'] && in_array($row['status'], ['expired', 'soon'], true) && isset($typeRoutes[$document->category]))
                                        <a href="{{ route($typeRoutes[$document->category], ['employee_id' => $document->employee_id]) }}" class="btn btn-sm review-btn is-approve" title="Go to {{ $typeLabel }} with this employee selected"><i class="ti ti-upload"></i> Upload new copy</a>
                                    @endif
                                    <a href="{{ $document->url }}" target="_blank" rel="noopener" class="btn-icon" title="Open the file"><i class="ti ti-eye font-16"></i></a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="ti ti-calendar-event font-24 d-block mb-2"></i>
                                @if ($activeFilters->isNotEmpty() || $state['tab'] !== '')
                                    <div class="fw-semibold text-body mb-1">No documents match</div>
                                    <span class="font-13"><a href="{{ route('admin.document-expiry.index') }}">Show every dated document</a></span>
                                @else
                                    <div class="fw-semibold text-body mb-1">No document has an expiry date yet</div>
                                    <span class="font-13">Passports, visas, contracts and certificates appear here once they are uploaded with an expiry date.</span>
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
@endsection
