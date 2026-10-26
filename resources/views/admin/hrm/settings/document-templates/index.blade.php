@extends('layouts.admin')

@section('title', 'Document Templates')

@section('content')
    <x-page-title title="Document Templates" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card">
        <div class="card-header d-flex align-items-center flex-wrap gap-2">
            @can('create', \App\Models\DocumentTemplate::class)
                <div><a href="{{ route('admin.document-templates.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> Add Template</a></div>
            @endcan
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th class="text-center">Active</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($templates as $template)
                            <tr>
                                <td>{{ $template->name }}</td>
                                <td>{{ $template->type ? (\App\Models\EmployeeDocument::TYPES[$template->type] ?? $template->type) : '—' }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $template->is_active ? 'badge-soft-success' : 'badge-soft-danger' }}">{{ $template->is_active ? 'Active' : 'Inactive' }}</span>
                                </td>
                                <td class="text-end">
                                    @can('update', $template)
                                        <a href="{{ route('admin.document-templates.edit', $template) }}" class="btn-icon" title="Edit"><i class="ti ti-pencil font-16"></i></a>
                                    @endcan
                                    @can('delete', $template)
                                        <form action="{{ route('admin.document-templates.destroy', $template) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this template?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-icon" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-5">
                                    <i class="ti ti-file-text font-24 d-block mb-2"></i>
                                    No document templates found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
