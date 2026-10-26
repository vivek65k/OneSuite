@extends('layouts.admin')

@section('title', 'HR Policies')

@section('content')
    <x-page-title title="HR Policies" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card">
        <div class="card-header d-flex align-items-center flex-wrap gap-2">
            @can('create', \App\Models\HrPolicy::class)
                <div><a href="{{ route('admin.hr-policies.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> Add Policy</a></div>
            @endcan
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Effective Date</th>
                            <th>File</th>
                            <th class="text-center">Active</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($policies as $policy)
                            <tr>
                                <td>{{ $policy->title }}</td>
                                <td>{{ $policy->category ?? '—' }}</td>
                                <td>{{ $policy->effective_date?->format('M j, Y') ?? '—' }}</td>
                                <td>
                                    @if ($policy->file_path)
                                        <a href="{{ asset('storage/'.$policy->file_path) }}" target="_blank">View</a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $policy->is_active ? 'badge-soft-success' : 'badge-soft-danger' }}">{{ $policy->is_active ? 'Active' : 'Inactive' }}</span>
                                </td>
                                <td class="text-end">
                                    @can('update', $policy)
                                        <a href="{{ route('admin.hr-policies.edit', $policy) }}" class="btn-icon" title="Edit"><i class="ti ti-pencil font-16"></i></a>
                                    @endcan
                                    @can('delete', $policy)
                                        <form action="{{ route('admin.hr-policies.destroy', $policy) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this policy?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-icon" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="ti ti-file-text font-24 d-block mb-2"></i>
                                    No HR policies found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
