@extends('layouts.admin')

@section('title', 'Deal Approval Matrix')

@section('content')
    <x-page-title title="Deal Approval Matrix" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <p class="text-muted font-13 mb-3">Define amount tiers that require sign-off before a deal can be marked Won. Leave min/max blank for no lower/upper bound.</p>

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> Add a rule</div></div>
        <div class="list-filter-panel">
            <form method="POST" action="{{ route('admin.crm.deal-approval-matrix.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Large deal sign-off" required></div>
                <div class="col-md-1"><label class="form-label">Level</label><input type="number" name="level" min="1" max="10" value="1" class="form-control form-control-sm" required></div>
                <div class="col-md-2"><label class="form-label">Min amount</label><input type="number" step="0.01" name="min_amount" class="form-control form-control-sm" placeholder="No minimum"></div>
                <div class="col-md-2"><label class="form-label">Max amount</label><input type="number" step="0.01" name="max_amount" class="form-control form-control-sm" placeholder="No maximum"></div>
                <div class="col-md-3">
                    <label class="form-label">Approver</label>
                    <select name="approver_employee_id" class="form-select form-select-sm" required>
                        <option value="">Select employee</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1"><button type="submit" class="btn btn-de-primary btn-sm text-light w-100">Add</button></div>
            </form>
        </div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-shield-check"></i> All Rules <span class="list-tab-count">{{ $rules->count() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Level</th><th>Name</th><th>Amount Range</th><th>Approver</th><th>Status</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($rules as $rule)
                        <tr>
                            <td><span class="tag tone-indigo">L{{ $rule->level }}</span></td>
                            <td class="fw-semibold">{{ $rule->name }}</td>
                            <td class="text-muted font-13">{{ $rule->min_amount ? number_format($rule->min_amount, 0) : 'Any' }} &ndash; {{ $rule->max_amount ? number_format($rule->max_amount, 0) : 'Any' }}</td>
                            <td class="text-muted font-13">{{ $rule->approver?->name ?? '—' }}</td>
                            <td>
                                <form action="{{ route('admin.crm.deal-approval-matrix.toggle', $rule) }}" method="POST" class="d-inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="switch-btn {{ $rule->is_active ? 'is-on' : '' }}" role="switch" aria-checked="{{ $rule->is_active ? 'true' : 'false' }}">
                                        <span class="visually-hidden">Toggle {{ $rule->name }}</span>
                                    </button>
                                </form>
                            </td>
                            <td class="text-end">
                                <form action="{{ route('admin.crm.deal-approval-matrix.destroy', $rule) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this rule?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Remove"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5"><i class="ti ti-shield-check font-24 d-block mb-2"></i>No approval rules yet - all deals can close freely.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
