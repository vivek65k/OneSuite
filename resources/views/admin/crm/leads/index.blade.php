@extends('layouts.admin')

@section('title', 'Leads')

@php
    use Illuminate\Support\Str;

    $statusTones = ['new' => 'sky', 'contacted' => 'amber', 'qualified' => 'indigo', 'unqualified' => 'rose', 'converted' => 'success'];
    $scopeRoute = match ($scope) {
        'mine' => 'admin.crm.leads.mine',
        'unassigned' => 'admin.crm.leads.unassigned',
        default => 'admin.crm.leads.index',
    };

    // Every link is built from the full current state (status/search/source)
    // so switching one filter never silently drops the others.
    $listUrl = fn (array $overrides = []) => route($scopeRoute, array_filter(
        array_merge(['status' => $status ?: null, 'search' => $search ?: null, 'lead_source_id' => $sourceId], $overrides, ['page' => null]),
        fn ($value) => $value !== null && $value !== ''
    ));

    $statusTabs = collect(['' => 'All statuses'])
        ->merge(collect(\App\Models\Crm\Lead::STATUSES))
        ->map(fn ($label, $key) => [
            'label' => $label,
            'count' => $key === '' ? $statusCounts->sum() : ($statusCounts[$key] ?? 0),
            'current' => $status === $key,
            'url' => $listUrl(['status' => $key ?: null]),
            'tone' => $key === '' ? 'indigo' : ($statusTones[$key] ?? 'slate'),
        ]);

    $activeFilters = collect();
    if ($search !== '') {
        $activeFilters->push(['key' => 'search', 'label' => 'Search', 'value' => $search]);
    }
    if ($sourceId) {
        $activeFilters->push(['key' => 'lead_source_id', 'label' => 'Source', 'value' => $sources[$sourceId] ?? $sourceId]);
    }
@endphp

@section('content')
    <x-page-title title="Leads" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="All Leads" :value="$counts['all']" icon="ti-list" tone="indigo" :href="route('admin.crm.leads.index')" />
        <x-kpi-card label="My Leads" :value="$counts['mine']" icon="ti-user" tone="violet" :href="route('admin.crm.leads.mine')" />
        <x-kpi-card label="Unassigned" :value="$counts['unassigned']" icon="ti-user-off" tone="amber" :href="route('admin.crm.leads.unassigned')" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route($scopeRoute) }}">
            @if ($status !== '')<input type="hidden" name="status" value="{{ $status }}">@endif

            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Lead status">
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
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $search }}" placeholder="Search name, email, phone" aria-label="Search leads">
                    </div>
                    <button type="button" class="btn btn-light btn-sm list-filter-toggle {{ $sourceId ? 'has-filters' : '' }}"
                            data-bs-toggle="collapse" data-bs-target="#leads-filter-panel" aria-expanded="false" aria-controls="leads-filter-panel">
                        <i class="ti ti-adjustments-horizontal font-15"></i> Filters
                        @if ($sourceId)
                            <span class="list-filter-count">1</span>
                        @endif
                    </button>
                    <button type="button" class="btn btn-light btn-sm" data-bs-toggle="collapse" data-bs-target="#lead-import-panel"><i class="ti ti-file-import font-14 me-1"></i> Import / Export</button>
                    <a href="{{ route('admin.crm.leads.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> Add Lead</a>
                </div>
            </div>

            <div class="collapse" id="leads-filter-panel">
                <div class="list-filter-panel">
                    <div class="row g-3">
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label">Source</label>
                            <select name="lead_source_id" class="form-select form-select-sm">
                                <option value="">Any source</option>
                                @foreach ($sources as $id => $name)
                                    <option value="{{ $id }}" @selected((string) $sourceId === (string) $id)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6 col-lg-3 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-de-primary btn-sm text-light flex-fill">Apply</button>
                            <a href="{{ $listUrl(['lead_source_id' => null]) }}" class="btn btn-light btn-sm">Clear</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div class="collapse {{ request('open') === 'import' ? 'show' : '' }}" id="lead-import-panel">
            <div class="list-filter-panel">
                <div class="row g-3">
                    <div class="col-lg-6">
                        <div class="import-export-pane">
                            <span class="import-export-icon tone-indigo"><i class="ti ti-cloud-upload"></i></span>
                            <div class="flex-grow-1">
                                <div class="fw-semibold">Import leads</div>
                                <p class="font-13 text-muted mb-2">CSV or XLSX - you'll map columns to fields on the next step.</p>
                                <form method="POST" action="{{ route('admin.crm.leads.import.preview') }}" enctype="multipart/form-data" class="d-flex gap-2">
                                    @csrf
                                    <input type="file" name="file" class="form-control form-control-sm" accept=".csv,.xlsx,.xls" required>
                                    <button type="submit" class="btn btn-de-primary btn-sm text-light px-3 flex-shrink-0">Next</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="import-export-pane">
                            <span class="import-export-icon tone-success"><i class="ti ti-file-export"></i></span>
                            <div class="flex-grow-1">
                                <div class="fw-semibold">Export leads</div>
                                <p class="font-13 text-muted mb-2">Downloads every lead matching your current filters as a CSV file.</p>
                                <a href="{{ route('admin.crm.leads.export') }}" class="btn btn-light btn-sm"><i class="ti ti-download font-14 me-1"></i> Download CSV</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if ($activeFilters->isNotEmpty())
            <div class="list-subbar">
                <div class="list-chips">
                    @foreach ($activeFilters as $filter)
                        <a href="{{ $listUrl([$filter['key'] => null]) }}" class="filter-chip" title="Remove filter">
                            {{ $filter['label'] }}: <strong>{{ $filter['value'] }}</strong>
                            <i class="ti ti-x"></i>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.crm.leads.bulk-action') }}" id="bulkForm">
            @csrf
            <div class="list-subbar d-flex align-items-center gap-2">
                <select name="bulk_action" class="form-select form-select-sm" style="width:auto;">
                    <option value="assign">Assign to...</option>
                    <option value="status">Set status...</option>
                    <option value="delete">Delete</option>
                </select>
                <select name="assigned_to_employee_id" class="form-select form-select-sm" style="width:auto;">
                    <option value="">Employee</option>
                    @foreach ($employees as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
                <select name="status" class="form-select form-select-sm" style="width:auto;">
                    @foreach (\App\Models\Crm\Lead::STATUSES as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-light btn-sm" onclick="return confirm('Apply this bulk action to the selected leads?');"><i class="ti ti-bolt font-14 me-1"></i> Apply to Selected</button>
                <span class="text-muted font-12 ms-auto"><span id="selectedCount">0</span> selected</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover list-table mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th style="width:36px;"><input type="checkbox" id="selectAll"></th>
                            <x-th-sort column="name" :sort="$sort" :direction="$direction" label="Lead" />
                            <th>Source</th>
                            <x-th-sort column="status" :sort="$sort" :direction="$direction" label="Status" />
                            <x-th-sort column="score" :sort="$sort" :direction="$direction" label="Score" align="center" />
                            <th>Assigned To</th>
                            <th class="text-end"><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($leads as $lead)
                            <tr>
                                <td><input type="checkbox" name="ids[]" value="{{ $lead->id }}" class="lead-checkbox" form="bulkForm"></td>
                                <td>
                                    <div class="list-user">
                                        <x-avatar :person="$lead" />
                                        <div class="list-user-text">
                                            <div class="list-user-name"><a href="{{ route('admin.crm.leads.show', $lead) }}">{{ $lead->name }}</a></div>
                                            <div class="list-user-meta">{{ collect([$lead->email, $lead->phone, $lead->company_name])->filter()->implode(' · ') ?: '—' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $lead->source?->name ?? '—' }}</td>
                                <td><span class="tag tone-{{ $statusTones[$lead->status] ?? 'slate' }}">{{ \App\Models\Crm\Lead::STATUSES[$lead->status] ?? $lead->status }}</span></td>
                                <td class="text-center">
                                    <div class="mini-progress tone-{{ $statusTones[$lead->status] ?? 'indigo' }}" style="width: 70px; margin: 0 auto;" title="Score {{ $lead->score }}">
                                        <div class="mini-progress-bar is-toned"><span style="width: {{ $lead->score }}%"></span></div>
                                    </div>
                                </td>
                                <td>{{ $lead->assignedTo?->name ?? '—' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.crm.leads.show', $lead) }}" class="btn-icon" title="View details"><i class="ti ti-eye font-16"></i></a>
                                    <a href="{{ route('admin.crm.leads.edit', $lead) }}" class="btn-icon btn-icon-accent" title="Edit"><i class="ti ti-edit font-16"></i></a>
                                    <form action="{{ route('admin.crm.leads.destroy', $lead) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this lead?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="ti ti-target font-24 d-block mb-2"></i>
                                    No leads found.
                                    @if ($activeFilters->isNotEmpty() || $status !== '')
                                        <div><a href="{{ route($scopeRoute) }}">Clear filters</a> to see every lead.</div>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>
        @if ($leads->hasPages())
            <div class="list-footer job-grid-footer">
                <div class="ms-auto">{{ $leads->links() }}</div>
            </div>
        @endif
    </div>

    <script>
        (function () {
            const selectAll = document.getElementById('selectAll');
            const countEl = document.getElementById('selectedCount');
            function boxes() { return document.querySelectorAll('.lead-checkbox'); }
            function updateCount() { countEl.textContent = document.querySelectorAll('.lead-checkbox:checked').length; }
            selectAll?.addEventListener('change', function () {
                boxes().forEach(b => b.checked = selectAll.checked);
                updateCount();
            });
            boxes().forEach(b => b.addEventListener('change', updateCount));
        })();
    </script>
@endsection
