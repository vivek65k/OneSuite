@extends('admin.hrm.employees.layout')

@section('title', $employee->name.' - Salary')

@section('employee-content')
    <div class="card">
        <div class="card-body">
            <h5 class="card-title mb-3">Salary</h5>
            <div class="table-responsive mb-3">
                <table class="table table-sm mb-0">
                    <thead class="thead-light"><tr><th>Amount</th><th>Effective</th><th>Current</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($employee->salaries as $salary)
                            <tr>
                                <td>{{ number_format((float) $salary->amount, 2) }} {{ $salary->currency }}</td>
                                <td>{{ $salary->effective_date->format('M j, Y') }}</td>
                                <td>@if ($salary->is_current)<span class="badge badge-soft-success">Current</span>@endif</td>
                                <td class="text-end">
                                    @can('update', $employee)
                                        <form action="{{ route('admin.employees.salaries.destroy', [$employee, $salary]) }}" method="POST" onsubmit="return confirm('Remove this record?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-icon" title="Remove"><i class="ti ti-trash font-16"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted text-center py-3">No salary records added.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @can('update', $employee)
                <form action="{{ route('admin.employees.salaries.store', $employee) }}" method="POST" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-3"><label class="form-label">Amount</label><input type="number" step="0.01" name="amount" class="form-control" required></div>
                    <div class="col-md-2"><label class="form-label">Currency</label><input type="text" name="currency" class="form-control" maxlength="3" placeholder="USD"></div>
                    <div class="col-md-3"><label class="form-label">Effective date</label><input type="date" name="effective_date" class="form-control" required></div>
                    <div class="col-md-2">
                        <div class="form-check form-switch form-switch-success mt-4">
                            <input class="form-check-input" type="checkbox" id="is_current" name="is_current" value="1">
                            <label class="form-check-label" for="is_current">Current</label>
                        </div>
                    </div>
                    <div class="col-md-2"><button type="submit" class="btn btn-de-primary text-light w-100">Add</button></div>
                </form>
            @endcan
        </div>
    </div>
@endsection
