@extends('layouts.admin')

@section('title', 'Trainer Management')

@section('content')
    <x-page-title title="Trainer Management" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card">
        <div class="card-header d-flex align-items-center flex-wrap gap-2">
            @can('create', \App\Models\Trainer::class)
                <div><a href="{{ route('admin.trainers.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> Add Trainer</a></div>
            @endcan
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Specialization</th>
                            <th class="text-center">Type</th>
                            <th class="text-center">Active</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($trainers as $trainer)
                            <tr>
                                <td>{{ $trainer->name }}</td>
                                <td>{{ $trainer->email }}{{ $trainer->phone ? ' · '.$trainer->phone : '' }}</td>
                                <td>{{ $trainer->specialization ?? '—' }}</td>
                                <td class="text-center">
                                    @if ($trainer->is_internal)
                                        <span class="badge badge-soft-primary">Internal{{ $trainer->employee ? ' — '.$trainer->employee->name : '' }}</span>
                                    @else
                                        <span class="badge badge-soft-info">External</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $trainer->is_active ? 'badge-soft-success' : 'badge-soft-danger' }}">{{ $trainer->is_active ? 'Active' : 'Inactive' }}</span>
                                </td>
                                <td class="text-end">
                                    @can('update', $trainer)
                                        <a href="{{ route('admin.trainers.edit', $trainer) }}" class="btn-icon" title="Edit"><i class="ti ti-pencil font-16"></i></a>
                                    @endcan
                                    @can('delete', $trainer)
                                        <form action="{{ route('admin.trainers.destroy', $trainer) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this trainer?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-icon" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="ti ti-presentation font-24 d-block mb-2"></i>
                                    No trainers found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
