@extends('layouts.admin')

@section('title', 'Approval Rules')

@section('content')
    <x-page-title title="Approval Rules" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> Add an approval rule</div></div>
        <div class="list-filter-panel">
            <p class="text-muted font-13 mb-2"><i class="ti ti-info-circle"></i> For Stock Adjustments, a quantity threshold here is enforced live: adjustments at or under the threshold auto-post, anything above requires manual approval.</p>
            <form method="POST" action="{{ route('admin.inventory.approval-rules.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-3">
                    <select name="action_type" class="form-select form-select-sm" required>
                        @foreach ($actionTypes as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label font-11 mb-0">Qty Threshold</label><input type="number" step="0.01" min="0" name="threshold_qty" class="form-control form-control-sm" placeholder="e.g. 10"></div>
                <div class="col-md-3"><label class="form-label font-11 mb-0">Value Threshold</label><input type="number" step="0.01" min="0" name="threshold_value" class="form-control form-control-sm" placeholder="e.g. 5000"></div>
                <div class="col-md-2"><label class="form-label font-11 mb-0">Approver Role</label><input type="text" name="approver_role" class="form-control form-control-sm" placeholder="e.g. Warehouse Manager"></div>
                <div class="col-md-1"><button type="submit" class="btn btn-de-primary text-light btn-sm w-100">Save</button></div>
            </form>
        </div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-shield-check"></i> Approval Matrix <span class="list-tab-count">{{ $rules->count() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Action</th><th>Qty Threshold</th><th>Value Threshold</th><th>Approver Role</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($rules as $rule)
                        <tr>
                            <td class="font-13">{{ $actionTypes[$rule->action_type] ?? $rule->action_type }}</td>
                            <td class="text-muted font-13">{{ $rule->threshold_qty ?? '—' }}</td>
                            <td class="text-muted font-13">{{ $rule->threshold_value ?? '—' }}</td>
                            <td class="text-muted font-13">{{ $rule->approver_role ?? '—' }}</td>
                            <td class="text-end">
                                <form action="{{ route('admin.inventory.approval-rules.destroy', $rule) }}" method="POST" onsubmit="return confirm('Remove this rule?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-5">No approval rules configured - every adjustment currently requires manual approval.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
