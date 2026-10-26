@extends('admin.hrm.employees.layout')

@section('title', $employee->name.' - Promotion')

@section('employee-content')
    <div class="card">
        <div class="card-body">
            <h5 class="card-title mb-3">Promotions</h5>
            <div class="table-responsive mb-3">
                <table class="table table-sm mb-0">
                    <thead class="thead-light"><tr><th>From</th><th>To</th><th>Date</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($employee->promotions as $promotion)
                            <tr>
                                <td>{{ $promotion->from_designation ?: '—' }}</td>
                                <td>{{ $promotion->to_designation }}</td>
                                <td>{{ $promotion->promotion_date->format('M j, Y') }}</td>
                                <td class="text-end">
                                    @can('update', $employee)
                                        <form action="{{ route('admin.employees.promotions.destroy', [$employee, $promotion]) }}" method="POST" onsubmit="return confirm('Remove this record?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-icon" title="Remove"><i class="ti ti-trash font-16"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted text-center py-3">No promotions recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @can('update', $employee)
                <form action="{{ route('admin.employees.promotions.store', $employee) }}" method="POST" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-3"><label class="form-label">From designation</label><input type="text" name="from_designation" class="form-control" value="{{ $employee->designation }}"></div>
                    <div class="col-md-3"><label class="form-label">To designation</label><input type="text" name="to_designation" class="form-control" required></div>
                    <div class="col-md-3"><label class="form-label">Date</label><input type="date" name="promotion_date" class="form-control" required></div>
                    <div class="col-md-3"><button type="submit" class="btn btn-de-primary text-light w-100">Record</button></div>
                </form>
                <small class="form-text text-muted">Recording a promotion updates the employee's current designation.</small>
            @endcan
        </div>
    </div>
@endsection
