@extends('admin.hrm.employees.layout')

@section('title', $employee->name.' - Family')

@section('employee-content')
    <div class="card">
        <div class="card-body">
            <h5 class="card-title mb-3">Family</h5>
            <div class="table-responsive mb-3">
                <table class="table table-sm mb-0">
                    <thead class="thead-light"><tr><th>Name</th><th>Relation</th><th>Date of birth</th><th>Phone</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($employee->familyMembers as $member)
                            <tr>
                                <td>{{ $member->name }}</td>
                                <td>{{ $member->relation }}</td>
                                <td>{{ optional($member->date_of_birth)->format('M j, Y') ?: '—' }}</td>
                                <td>{{ $member->phone ?: '—' }}</td>
                                <td class="text-end">
                                    @can('update', $employee)
                                        <form action="{{ route('admin.employees.family-members.destroy', [$employee, $member]) }}" method="POST" onsubmit="return confirm('Remove this family member?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-icon" title="Remove"><i class="ti ti-trash font-16"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted text-center py-3">No family members added.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @can('update', $employee)
                <form action="{{ route('admin.employees.family-members.store', $employee) }}" method="POST" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" required></div>
                    <div class="col-md-3"><label class="form-label">Relation</label><input type="text" name="relation" class="form-control" placeholder="e.g. Spouse" required></div>
                    <div class="col-md-3"><label class="form-label">Date of birth</label><input type="date" name="date_of_birth" class="form-control"></div>
                    <div class="col-md-2"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control"></div>
                    <div class="col-md-1"><button type="submit" class="btn btn-de-primary text-light w-100">Add</button></div>
                </form>
            @endcan
        </div>
    </div>
@endsection
