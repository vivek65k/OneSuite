@extends('layouts.admin')

@section('title', 'Roles & Permissions')

@section('content')
    <x-page-title title="Roles & Permissions" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card">
        <div class="card-header">
            <form method="GET" action="{{ route('admin.roles.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent"><i class="ti ti-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control" value="{{ $state['search'] ?? '' }}" placeholder="Search roles...">
                    </div>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-de-primary text-light btn-sm"><i class="ti ti-filter font-14 me-1"></i> Filter</button>
                    <a href="{{ route('admin.roles.index') }}" class="btn btn-light btn-sm"><i class="ti ti-x font-14 me-1"></i> Reset</a>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="d-flex align-items-center flex-wrap gap-2 mb-3">
                @can('create', \Spatie\Permission\Models\Role::class)
                    <div><a href="{{ route('admin.roles.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> New role</a></div>
                @endcan
                <div class="ms-auto"><x-export-csv-button route="admin.roles.export" /></div>
            </div>

            <div class="table-responsive table-responsive-sticky">
                <table class="table table-striped table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <x-th-sort column="name" :sort="$sort" :direction="$direction" label="Name" />
                            <th>Data access</th>
                            <th>Guard</th>
                            <th>Permissions</th>
                            <x-th-sort column="updated_at" :sort="$sort" :direction="$direction" label="Updated" />
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($roles as $role)
                            <tr>
                                <td>{{ Str::headline($role->name) }}</td>
                                <td>
                                    @php $scope = \App\Support\DataScope::OPTIONS[$role->data_scope] ?? null; @endphp
                                    <span class="badge badge-soft-{{ ['all' => 'primary', 'team' => 'success', 'own' => 'secondary'][$role->data_scope] ?? 'secondary' }}"><i class="{{ $scope['icon'] ?? 'ti ti-help' }} me-1"></i>{{ $scope['label'] ?? $role->data_scope }}</span>
                                </td>
                                <td><span class="badge badge-soft-warning">{{ $role->guard_name }}</span></td>
                                <td><span class="badge badge-soft-success">{{ $role->permissions_count }}</span></td>
                                <td>{{ $role->updated_at->format('M j, Y') }}</td>
                                <td class="text-end">
                                    @can('update', $role)
                                        <a href="{{ route('admin.roles.edit', $role) }}" class="btn-icon" title="Edit"><i class="ti ti-pencil font-16"></i></a>
                                    @endcan
                                    @can('delete', $role)
                                        <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this role?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="ti ti-shield-lock font-24 d-block mb-2"></i>
                                    No roles found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $roles->links() }}
            </div>
        </div>
    </div>
@endsection
