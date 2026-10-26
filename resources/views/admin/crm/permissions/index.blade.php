@extends('layouts.admin')

@section('title', 'Permissions')

@section('content')
    <x-page-title title="Permissions" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> New role</div></div>
        <div class="list-filter-panel">
            <form method="POST" action="{{ route('admin.crm.permissions.store') }}">
                @csrf
                <div class="row g-2">
                    <div class="col-md-4"><label class="form-label">Role Name</label><input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Sales Manager" required></div>
                </div>
                <div class="row g-2 mt-2">
                    @foreach ($permissions as $key => $label)
                        <div class="col-md-3 form-check">
                            <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $key }}" id="perm_{{ $key }}">
                            <label class="form-check-label font-13" for="perm_{{ $key }}">{{ $label }}</label>
                        </div>
                    @endforeach
                </div>
                <button type="submit" class="btn btn-de-primary btn-sm text-light px-3 mt-3">Create Role</button>
            </form>
        </div>
    </div>

    @forelse ($roles as $role)
        <div class="card list-view mb-3">
            <div class="list-toolbar">
                <div class="list-toolbar-title"><i class="ti ti-lock"></i> {{ $role->name }}</div>
                <div class="list-toolbar-actions">
                    <form action="{{ route('admin.crm.permissions.destroy', $role) }}" method="POST" onsubmit="return confirm('Delete this role?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                    </form>
                </div>
            </div>
            <div class="p-3">
                <div class="d-flex flex-wrap gap-1 mb-3">
                    @forelse ($role->permissions ?? [] as $perm)
                        <span class="tag tone-indigo">{{ $permissions[$perm] ?? $perm }}</span>
                    @empty
                        <span class="text-muted font-13">No permissions set.</span>
                    @endforelse
                </div>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    @forelse ($role->employees as $emp)
                        <span class="tag tone-violet d-inline-flex align-items-center gap-1">{{ $emp->name }}
                            <form action="{{ route('admin.crm.permissions.unassign', [$role, $emp]) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-icon" style="width:16px;height:16px;" title="Remove"><i class="ti ti-x font-12"></i></button>
                            </form>
                        </span>
                    @empty
                        <span class="text-muted font-13">No employees assigned.</span>
                    @endforelse
                </div>
                <form action="{{ route('admin.crm.permissions.assign', $role) }}" method="POST" class="d-flex gap-2">
                    @csrf
                    <select name="employee_id" class="form-select form-select-sm" style="max-width: 240px;" required>
                        <option value="">Assign employee</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-light btn-sm">Assign</button>
                </form>
            </div>
        </div>
    @empty
        <div class="card"><div class="empty-state"><span class="empty-state-icon tone-indigo"><i class="ti ti-lock"></i></span><h5>No roles yet</h5><p>Create a role to control what CRM users can do.</p></div></div>
    @endforelse
@endsection
