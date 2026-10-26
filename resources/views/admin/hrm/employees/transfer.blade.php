@extends('admin.hrm.employees.layout')

@section('title', $employee->name.' - Transfer')

@section('employee-content')
    <div class="card">
        <div class="card-body">
            <h5 class="card-title mb-3">Transfers</h5>
            <div class="table-responsive mb-3">
                <table class="table table-sm mb-0">
                    <thead class="thead-light"><tr><th>From</th><th>To</th><th>Date</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($employee->transfers as $transfer)
                            <tr>
                                <td>{{ $transfer->fromDepartment?->name ?: '—' }}</td>
                                <td>{{ $transfer->toDepartment?->name ?: '—' }}</td>
                                <td>{{ $transfer->transfer_date->format('M j, Y') }}</td>
                                <td class="text-end">
                                    @can('update', $employee)
                                        <form action="{{ route('admin.employees.transfers.destroy', [$employee, $transfer]) }}" method="POST" onsubmit="return confirm('Remove this record?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-icon" title="Remove"><i class="ti ti-trash font-16"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted text-center py-3">No transfers recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @can('update', $employee)
                <form action="{{ route('admin.employees.transfers.store', $employee) }}" method="POST" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label">To department</label>
                        <select name="to_department_id" class="form-select">
                            <option value="">No change</option>
                            @foreach ($employee->company->departments as $department)
                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">To branch</label>
                        <select name="to_branch_id" class="form-select">
                            <option value="">No change</option>
                            @foreach ($employee->company->branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2"><label class="form-label">Date</label><input type="date" name="transfer_date" class="form-control" required></div>
                    <div class="col-md-2"><button type="submit" class="btn btn-de-primary text-light w-100">Record</button></div>
                </form>
                <small class="form-text text-muted">Recording a transfer updates the employee's current department/branch.</small>
            @endcan
        </div>
    </div>
@endsection
