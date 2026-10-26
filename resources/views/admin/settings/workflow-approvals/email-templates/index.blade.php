@extends('layouts.admin')

@section('title', 'Email Templates')

@section('content')
    <x-page-title title="Email Templates" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card">
        <div class="card-header d-flex align-items-center">
            <i class="ti ti-filter font-18 me-2 text-muted"></i>
            <h4 class="card-title mb-0">Filters</h4>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.email-templates.index') }}" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">Search</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent"><i class="ti ti-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control" value="{{ $state['search'] ?? '' }}" placeholder="Name or subject">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Per page</label>
                    <select name="per_page" class="form-select">
                        @foreach ([10, 25, 50, 100] as $option)
                            <option value="{{ $option }}" @selected((int) ($state['per_page'] ?? 25) === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="direction" value="{{ $direction }}">
                <div class="col-auto ms-auto">
                    <button type="submit" class="btn btn-de-primary text-light"><i class="ti ti-filter font-14 me-1"></i> Filter</button>
                    <a href="{{ route('admin.email-templates.index') }}" class="btn btn-light"><i class="ti ti-x font-14 me-1"></i> Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center flex-wrap gap-2">
            @can('create', \App\Models\EmailTemplate::class)
                <div><a href="{{ route('admin.email-templates.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> New template</a></div>
            @endcan
            <div class="ms-auto"><x-export-csv-button route="admin.email-templates.export" /></div>
        </div>
        <div class="card-body">
            <div class="table-responsive table-responsive-sticky">
                <table class="table table-striped table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <x-th-sort column="name" :sort="$sort" :direction="$direction" label="Name" />
                            <x-th-sort column="subject" :sort="$sort" :direction="$direction" label="Subject" />
                            <x-th-sort column="is_active" :sort="$sort" :direction="$direction" label="Active" />
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($templates as $template)
                            <tr>
                                <td>{{ $template->name }}</td>
                                <td>{{ $template->subject }}</td>
                                <td>
                                    <span class="badge {{ $template->is_active ? 'badge-soft-success' : 'badge-soft-danger' }}">
                                        {{ $template->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    @can('update', $template)
                                        <a href="{{ route('admin.email-templates.edit', $template) }}" class="btn-icon" title="Edit"><i class="ti ti-pencil font-16"></i></a>
                                    @endcan
                                    @can('delete', $template)
                                        <form action="{{ route('admin.email-templates.destroy', $template) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this template?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-5">
                                    <i class="ti ti-mail font-24 d-block mb-2"></i>
                                    No email templates found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $templates->links() }}
            </div>
        </div>
    </div>
@endsection
