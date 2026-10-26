@extends('admin.hrm.employees.layout')

@section('title', $employee->name.' - Education')

@section('employee-content')
    <div class="card">
        <div class="card-body">
            <h5 class="card-title mb-3">Education</h5>
            <div class="table-responsive mb-3">
                <table class="table table-sm mb-0">
                    <thead class="thead-light"><tr><th>Qualification</th><th>Institution</th><th>Field</th><th>Years</th><th>Grade</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($employee->educations as $education)
                            <tr>
                                <td>{{ $education->qualification }}</td>
                                <td>{{ $education->institution }}</td>
                                <td>{{ $education->field_of_study ?: '—' }}</td>
                                <td>{{ $education->start_year ?: '—' }}&ndash;{{ $education->end_year ?: '—' }}</td>
                                <td>{{ $education->grade ?: '—' }}</td>
                                <td class="text-end">
                                    @can('update', $employee)
                                        <form action="{{ route('admin.employees.educations.destroy', [$employee, $education]) }}" method="POST" onsubmit="return confirm('Remove this entry?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-icon" title="Remove"><i class="ti ti-trash font-16"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted text-center py-3">No education entries added.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @can('update', $employee)
                <form action="{{ route('admin.employees.educations.store', $employee) }}" method="POST" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-3"><label class="form-label">Qualification</label><input type="text" name="qualification" class="form-control" required></div>
                    <div class="col-md-3"><label class="form-label">Institution</label><input type="text" name="institution" class="form-control" required></div>
                    <div class="col-md-2"><label class="form-label">Field</label><input type="text" name="field_of_study" class="form-control"></div>
                    <div class="col-md-1"><label class="form-label">Start</label><input type="number" name="start_year" class="form-control" placeholder="YYYY"></div>
                    <div class="col-md-1"><label class="form-label">End</label><input type="number" name="end_year" class="form-control" placeholder="YYYY"></div>
                    <div class="col-md-1"><label class="form-label">Grade</label><input type="text" name="grade" class="form-control"></div>
                    <div class="col-md-1"><button type="submit" class="btn btn-de-primary text-light w-100">Add</button></div>
                </form>
            @endcan
        </div>
    </div>
@endsection
