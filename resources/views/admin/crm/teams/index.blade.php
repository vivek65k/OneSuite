@extends('layouts.admin')

@section('title', 'Teams & Territories')

@section('content')
    <x-page-title title="Teams &amp; Territories" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> New team</div></div>
        <div class="list-filter-panel">
            <form method="POST" action="{{ route('admin.crm.teams.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-5"><label class="form-label">Team Name</label><input type="text" name="name" class="form-control form-control-sm" required></div>
                <div class="col-md-5">
                    <label class="form-label">Manager</label>
                    <select name="manager_employee_id" class="form-select form-select-sm">
                        <option value="">None</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><button type="submit" class="btn btn-de-primary btn-sm text-light w-100">Add</button></div>
            </form>
        </div>
    </div>

    @forelse ($teams as $team)
        <div class="card list-view mb-3">
            <div class="list-toolbar">
                <div class="list-toolbar-title"><i class="ti ti-users"></i> {{ $team->name }} <span class="text-muted font-13 ms-2">Manager: {{ $team->manager?->name ?? '—' }}</span></div>
                <div class="list-toolbar-actions">
                    <form action="{{ route('admin.crm.teams.destroy', $team) }}" method="POST" onsubmit="return confirm('Delete this team?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                    </form>
                </div>
            </div>
            <div class="p-3">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    @forelse ($team->members as $member)
                        <span class="tag tone-indigo d-inline-flex align-items-center gap-1">{{ $member->name }}
                            <form action="{{ route('admin.crm.teams.members.remove', [$team, $member]) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-icon" style="width:16px;height:16px;" title="Remove"><i class="ti ti-x font-12"></i></button>
                            </form>
                        </span>
                    @empty
                        <span class="text-muted font-13">No members yet.</span>
                    @endforelse
                </div>
                <form action="{{ route('admin.crm.teams.members.add', $team) }}" method="POST" class="d-flex gap-2">
                    @csrf
                    <select name="employee_id" class="form-select form-select-sm" style="max-width: 240px;" required>
                        <option value="">Add member</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-light btn-sm">Add</button>
                </form>
            </div>
        </div>
    @empty
        <div class="card"><div class="empty-state"><span class="empty-state-icon tone-indigo"><i class="ti ti-users"></i></span><h5>No teams yet</h5><p>Create a team to organize your sales force.</p></div></div>
    @endforelse
@endsection
