@extends('admin.hrm.employees.layout')

@section('title', $employee->name.' - Skills')

@section('employee-content')
    <div class="card">
        <div class="card-body">
            <h5 class="card-title mb-3">Skills</h5>
            <div class="mb-3">
                @forelse ($employee->skills as $skill)
                    <span class="badge badge-soft-primary font-13 me-2 mb-2 p-2">
                        {{ $skill->skill_name }} &middot; {{ Str::headline($skill->proficiency) }}
                        @can('update', $employee)
                            <form action="{{ route('admin.employees.skills.destroy', [$employee, $skill]) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this skill?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-link p-0 border-0 text-danger ms-1" style="line-height: 1;"><i class="ti ti-x font-11"></i></button>
                            </form>
                        @endcan
                    </span>
                @empty
                    <p class="text-muted mb-0">No skills added.</p>
                @endforelse
            </div>
            @can('update', $employee)
                <form action="{{ route('admin.employees.skills.store', $employee) }}" method="POST" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-5"><label class="form-label">Skill</label><input type="text" name="skill_name" class="form-control" required></div>
                    <div class="col-md-4">
                        <label class="form-label">Proficiency</label>
                        <select name="proficiency" class="form-select">
                            @foreach (\App\Models\EmployeeSkill::PROFICIENCIES as $level)
                                <option value="{{ $level }}">{{ Str::headline($level) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3"><button type="submit" class="btn btn-de-primary text-light w-100">Add</button></div>
                </form>
            @endcan
        </div>
    </div>
@endsection
