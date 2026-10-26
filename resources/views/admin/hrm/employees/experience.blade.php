@extends('admin.hrm.employees.layout')

@section('title', $employee->name.' - Experience')

@section('employee-content')
    <div class="card">
        <div class="card-body">
            <h5 class="card-title mb-3">Experience</h5>
            <div class="table-responsive mb-3">
                <table class="table table-sm mb-0">
                    <thead class="thead-light"><tr><th>Company</th><th>Designation</th><th>From</th><th>To</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($employee->experiences as $experience)
                            <tr>
                                <td>{{ $experience->company_name }}</td>
                                <td>{{ $experience->designation }}</td>
                                <td>{{ optional($experience->start_date)->format('M Y') ?: '—' }}</td>
                                <td>{{ optional($experience->end_date)->format('M Y') ?: 'Present' }}</td>
                                <td class="text-end">
                                    @can('update', $employee)
                                        <form action="{{ route('admin.employees.experiences.destroy', [$employee, $experience]) }}" method="POST" onsubmit="return confirm('Remove this entry?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-icon" title="Remove"><i class="ti ti-trash font-16"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted text-center py-3">No experience entries added.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @can('update', $employee)
                <form action="{{ route('admin.employees.experiences.store', $employee) }}" method="POST" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-3"><label class="form-label">Company</label><input type="text" name="company_name" class="form-control" required></div>
                    <div class="col-md-3"><label class="form-label">Designation</label><input type="text" name="designation" class="form-control" required></div>
                    <div class="col-md-2"><label class="form-label">Start date</label><input type="date" name="start_date" class="form-control"></div>
                    <div class="col-md-2"><label class="form-label">End date</label><input type="date" name="end_date" class="form-control"></div>
                    <div class="col-md-2"><button type="submit" class="btn btn-de-primary text-light w-100">Add</button></div>
                </form>
            @endcan
        </div>
    </div>
@endsection
