@extends('layouts.admin')

@section('title', 'Territories')

@section('content')
    <x-page-title title="Territories" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> New territory</div></div>
        <div class="list-filter-panel">
            <form method="POST" action="{{ route('admin.crm.territories.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-4"><label class="form-label">Name</label><input type="text" name="name" class="form-control form-control-sm" required></div>
                <div class="col-md-3">
                    <label class="form-label">Team</label>
                    <select name="team_id" class="form-select form-select-sm">
                        <option value="">None</option>
                        @foreach ($teams as $team)
                            <option value="{{ $team->id }}">{{ $team->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4"><label class="form-label">Criteria</label><input type="text" name="criteria" class="form-control form-control-sm" placeholder="e.g. Region = West"></div>
                <div class="col-md-1"><button type="submit" class="btn btn-de-primary btn-sm text-light w-100">Add</button></div>
            </form>
        </div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-map-pin"></i> All Territories <span class="list-tab-count">{{ $territories->count() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Name</th><th>Team</th><th>Criteria</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($territories as $territory)
                        <tr>
                            <td class="fw-semibold">{{ $territory->name }}</td>
                            <td class="text-muted font-13">{{ $territory->team?->name ?? '—' }}</td>
                            <td class="text-muted font-13">{{ $territory->criteria ?? '—' }}</td>
                            <td class="text-end">
                                <form action="{{ route('admin.crm.territories.destroy', $territory) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this territory?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Remove"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-5"><i class="ti ti-map-pin font-24 d-block mb-2"></i>No territories yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
