@extends('layouts.admin')

@section('title', 'Lead Recycle')

@section('content')
    <x-page-title title="Lead Recycle" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Eligible to Recycle" :value="$eligible->count()" icon="ti-recycle" tone="amber" />
        <x-kpi-card label="In Recycle Pool" :value="$recycled->total()" icon="ti-archive" tone="indigo" />
    </div>

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-recycle"></i> Unqualified Leads Eligible for Recycling</div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Name</th><th>Source</th><th>Last Owner</th><th>Marked Unqualified</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($eligible as $lead)
                        <tr>
                            <td class="fw-semibold">{{ $lead->name }}</td>
                            <td class="text-muted font-13">{{ $lead->source?->name ?? '—' }}</td>
                            <td class="text-muted font-13">{{ $lead->assignedTo?->name ?? '—' }}</td>
                            <td class="text-muted font-13">{{ $lead->updated_at->format('M j, Y') }}</td>
                            <td class="text-end">
                                <form action="{{ route('admin.crm.lead-recycle.recycle', $lead) }}" method="POST" class="d-flex gap-1 justify-content-end">
                                    @csrf
                                    <input type="text" name="recycle_reason" class="form-control form-control-sm" placeholder="Reason (optional)" style="width:160px;">
                                    <button type="submit" class="btn btn-light btn-sm"><i class="ti ti-recycle font-14 me-1"></i> Recycle</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-5"><i class="ti ti-recycle font-24 d-block mb-2"></i>No unqualified leads waiting to be recycled.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.crm.lead-recycle.index') }}">
            <div class="list-toolbar">
                <div class="list-toolbar-title"><i class="ti ti-archive"></i> Recycle Pool <span class="list-tab-count">{{ $recycled->total() }}</span></div>
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $search }}" placeholder="Search recycled leads" aria-label="Search">
                    </div>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Name</th><th>Source</th><th>Reason</th><th>Recycled</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($recycled as $lead)
                        <tr>
                            <td class="fw-semibold">{{ $lead->name }}</td>
                            <td class="text-muted font-13">{{ $lead->source?->name ?? '—' }}</td>
                            <td class="text-muted font-13">{{ $lead->recycle_reason ?: '—' }}</td>
                            <td class="text-muted font-13">{{ $lead->recycled_at?->format('M j, Y') ?? '—' }}</td>
                            <td class="text-end">
                                <form action="{{ route('admin.crm.lead-recycle.reactivate', $lead) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-light btn-sm"><i class="ti ti-refresh font-14 me-1"></i> Reactivate</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-5"><i class="ti ti-archive font-24 d-block mb-2"></i>The recycle pool is empty.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($recycled->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $recycled->links() }}</div></div>
        @endif
    </div>
@endsection
