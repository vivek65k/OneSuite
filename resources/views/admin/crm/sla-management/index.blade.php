@extends('layouts.admin')

@section('title', 'SLA Management')

@section('content')
    <x-page-title title="SLA Management" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="SLA Policies" :value="$policies->count()" icon="ti-shield-check" tone="indigo" />
        <x-kpi-card label="Breached" :value="$breached->count()" icon="ti-alert-triangle" tone="rose" />
        <x-kpi-card label="Due in 24h" :value="$dueSoon->count()" icon="ti-clock" tone="amber" />
    </div>

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> Add SLA policy</div></div>
        <div class="list-filter-panel">
            <form method="POST" action="{{ route('admin.crm.sla-management.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control form-control-sm" required></div>
                <div class="col-md-3">
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-select form-select-sm">
                        @foreach (\App\Models\Crm\CaseModel::PRIORITIES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label">Response (hrs)</label><input type="number" name="response_hours" class="form-control form-control-sm" value="4" required></div>
                <div class="col-md-2"><label class="form-label">Resolve (hrs)</label><input type="number" name="resolution_hours" class="form-control form-control-sm" value="24" required></div>
                <div class="col-md-2"><button type="submit" class="btn btn-de-primary btn-sm text-light w-100">Add</button></div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Name</th><th>Priority</th><th>Response</th><th>Resolution</th><th>Cases</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($policies as $policy)
                        <tr>
                            <td class="fw-semibold">{{ $policy->name }}</td>
                            <td><span class="tag tone-indigo">{{ \App\Models\Crm\CaseModel::PRIORITIES[$policy->priority] ?? $policy->priority }}</span></td>
                            <td>{{ $policy->response_hours }}h</td>
                            <td>{{ $policy->resolution_hours }}h</td>
                            <td>{{ $policy->cases_count }}</td>
                            <td class="text-end">
                                <form action="{{ route('admin.crm.sla-management.destroy', $policy) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this policy?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Remove"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No SLA policies configured yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card list-view h-100">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-alert-triangle"></i> SLA Breached</div></div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <thead class="thead-light"><tr><th>Case</th><th>Due</th><th>Owner</th></tr></thead>
                        <tbody>
                            @forelse ($breached as $case)
                                <tr>
                                    <td class="fw-semibold"><a href="{{ route('admin.crm.cases.edit', $case) }}">{{ $case->subject }}</a></td>
                                    <td class="text-danger font-13">{{ $case->resolution_due_at->format('M j, g:i A') }}</td>
                                    <td class="text-muted font-13">{{ $case->assignedTo?->name ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-4">Nothing breached. Great job.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card list-view h-100">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-clock"></i> Due Within 24 Hours</div></div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <thead class="thead-light"><tr><th>Case</th><th>Due</th><th>Owner</th></tr></thead>
                        <tbody>
                            @forelse ($dueSoon as $case)
                                <tr>
                                    <td class="fw-semibold"><a href="{{ route('admin.crm.cases.edit', $case) }}">{{ $case->subject }}</a></td>
                                    <td class="text-muted font-13">{{ $case->resolution_due_at->format('M j, g:i A') }}</td>
                                    <td class="text-muted font-13">{{ $case->assignedTo?->name ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-4">Nothing due soon.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
