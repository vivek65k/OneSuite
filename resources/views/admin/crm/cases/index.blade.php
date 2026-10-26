@extends('layouts.admin')

@section('title', 'Customer Cases')

@section('content')
    <x-page-title title="Customer Cases" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Open Cases" :value="$openCount" icon="ti-ticket" tone="indigo" />
        <x-kpi-card label="Overdue" :value="$overdueCount" icon="ti-alert-triangle" tone="rose" />
        <x-kpi-card label="Resolved" :value="$resolvedCount" icon="ti-circle-check" tone="success" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.crm.cases.index') }}">
            @if ($sort !== 'created_at')<input type="hidden" name="sort" value="{{ $sort }}">@endif
            @if ($direction !== 'desc')<input type="hidden" name="direction" value="{{ $direction }}">@endif
            <div class="list-toolbar">
                <div class="list-toolbar-title"><i class="ti ti-ticket"></i> All Cases <span class="list-tab-count">{{ $cases->total() }}</span></div>
                <div class="list-toolbar-actions">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()" style="width:auto;">
                        <option value="">All Statuses</option>
                        @foreach ($statuses as $key => $label)
                            <option value="{{ $key }}" @selected($statusFilter === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="priority" class="form-select form-select-sm" onchange="this.form.submit()" style="width:auto;">
                        <option value="">All Priorities</option>
                        @foreach ($priorities as $key => $label)
                            <option value="{{ $key }}" @selected($priorityFilter === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $search }}" placeholder="Search cases" aria-label="Search">
                    </div>
                    <a href="{{ route('admin.crm.cases.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> New Case</a>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <x-th-sort column="case_number" :sort="$sort" :direction="$direction" label="Case #" />
                        <x-th-sort column="subject" :sort="$sort" :direction="$direction" label="Subject" />
                        <th>Account / Contact</th>
                        <x-th-sort column="priority" :sort="$sort" :direction="$direction" label="Priority" />
                        <x-th-sort column="status" :sort="$sort" :direction="$direction" label="Status" />
                        <th>Assigned To</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cases as $case)
                        <tr>
                            <td class="font-13 text-muted">{{ $case->case_number }}</td>
                            <td class="fw-semibold">
                                <a href="{{ route('admin.crm.cases.edit', $case) }}">{{ $case->subject }}</a>
                                @if ($case->is_overdue)<span class="tag tone-rose ms-1">Overdue</span>@endif
                            </td>
                            <td class="text-muted font-13">{{ $case->account?->name ?? $case->contact?->name ?? '—' }}</td>
                            <td><span class="tag tone-{{ $case->priority === 'urgent' ? 'rose' : ($case->priority === 'high' ? 'amber' : 'indigo') }}">{{ $priorities[$case->priority] ?? $case->priority }}</span></td>
                            <td><span class="tag tone-{{ $case->status === 'resolved' || $case->status === 'closed' ? 'success' : ($case->status === 'on_hold' ? 'amber' : 'sky') }}">{{ $statuses[$case->status] ?? $case->status }}</span></td>
                            <td class="text-muted font-13">{{ $case->assignedTo?->name ?? '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.crm.cases.edit', $case) }}" class="btn-icon btn-icon-accent" title="Edit"><i class="ti ti-pencil font-16"></i></a>
                                <form action="{{ route('admin.crm.cases.destroy', $case) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this case?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-5"><i class="ti ti-ticket font-24 d-block mb-2"></i>No cases yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($cases->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $cases->links() }}</div></div>
        @endif
    </div>
@endsection
