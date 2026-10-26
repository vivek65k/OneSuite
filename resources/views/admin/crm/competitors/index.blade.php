@extends('layouts.admin')

@section('title', 'Competitors')

@section('content')
    <x-page-title title="Competitors" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Total Competitors" :value="$total" icon="ti-shield" tone="indigo" />
        <x-kpi-card label="Deal Encounters" :value="$totalEncounters" icon="ti-target" tone="rose" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.crm.competitors.index') }}">
            @if ($sort !== 'name')<input type="hidden" name="sort" value="{{ $sort }}">@endif
            @if ($direction !== 'asc')<input type="hidden" name="direction" value="{{ $direction }}">@endif
            <div class="list-toolbar">
                <div class="list-toolbar-title"><i class="ti ti-shield"></i> Competitors</div>
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $search }}" placeholder="Search name" aria-label="Search competitors">
                    </div>
                    <a href="{{ route('admin.crm.competitors.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> Add Competitor</a>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <x-th-sort column="name" :sort="$sort" :direction="$direction" label="Name" />
                        <th>Website</th>
                        <x-th-sort column="opportunities_count" :sort="$sort" :direction="$direction" label="Deals Involved" />
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($competitors as $competitor)
                        <tr>
                            <td class="fw-semibold"><a href="{{ route('admin.crm.competitors.edit', $competitor) }}">{{ $competitor->name }}</a></td>
                            <td>{{ $competitor->website ?? '—' }}</td>
                            <td class="text-center"><span class="tag tone-indigo">{{ $competitor->opportunities_count }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('admin.crm.competitors.edit', $competitor) }}" class="btn-icon btn-icon-accent" title="Edit"><i class="ti ti-edit font-16"></i></a>
                                <form action="{{ route('admin.crm.competitors.destroy', $competitor) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this competitor?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-5">
                                <i class="ti ti-shield font-24 d-block mb-2"></i>
                                No competitors tracked yet.
                                <div><a href="{{ route('admin.crm.competitors.create') }}">Add your first one</a>.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
