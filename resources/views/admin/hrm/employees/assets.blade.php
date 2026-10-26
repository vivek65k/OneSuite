@extends('admin.hrm.employees.layout')

@section('title', $employee->name.' - Assets')

@section('employee-content')
    <div class="card">
        <div class="card-body">
            <h5 class="card-title mb-3">Assets</h5>
            <div class="table-responsive mb-3">
                <table class="table table-sm mb-0">
                    <thead class="thead-light"><tr><th>Asset</th><th>Tag</th><th>Assigned</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($employee->assets as $asset)
                            <tr>
                                <td>{{ $asset->asset_name }}</td>
                                <td>{{ $asset->asset_tag ?: '—' }}</td>
                                <td>{{ optional($asset->assigned_date)->format('M j, Y') ?: '—' }}</td>
                                <td><span class="badge {{ $asset->status === 'assigned' ? 'badge-soft-primary' : 'badge-soft-success' }}">{{ Str::headline($asset->status) }}</span></td>
                                <td class="text-end">
                                    @can('update', $employee)
                                        <form action="{{ route('admin.employees.assets.destroy', [$employee, $asset]) }}" method="POST" onsubmit="return confirm('Remove this asset?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-icon" title="Remove"><i class="ti ti-trash font-16"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted text-center py-3">No assets assigned.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @can('update', $employee)
                <form action="{{ route('admin.employees.assets.store', $employee) }}" method="POST" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-3"><label class="form-label">Asset name</label><input type="text" name="asset_name" class="form-control" required></div>
                    <div class="col-md-2"><label class="form-label">Tag</label><input type="text" name="asset_tag" class="form-control"></div>
                    <div class="col-md-3"><label class="form-label">Assigned date</label><input type="date" name="assigned_date" class="form-control"></div>
                    <div class="col-md-2"><label class="form-label">Status</label><select name="status" class="form-select"><option value="assigned">Assigned</option><option value="returned">Returned</option></select></div>
                    <div class="col-md-2"><button type="submit" class="btn btn-de-primary text-light w-100">Add</button></div>
                </form>
            @endcan
        </div>
    </div>
@endsection
