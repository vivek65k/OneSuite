@extends('layouts.admin')

@section('title', 'Resume Bank')

@section('content')
    <x-page-title title="Resume Bank" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card">
        <div class="card-header d-flex align-items-center">
            <i class="ti ti-filter font-18 me-2 text-muted"></i>
            <h4 class="card-title mb-0">Filters</h4>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.resume-bank.index') }}" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">Search</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent"><i class="ti ti-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control" value="{{ $state['search'] ?? '' }}" placeholder="Name, email, or skills">
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
                    <a href="{{ route('admin.resume-bank.index') }}" class="btn btn-light"><i class="ti ti-x font-14 me-1"></i> Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center flex-wrap gap-2">
            @can('create', \App\Models\ResumeBankEntry::class)
                <div><a href="{{ route('admin.resume-bank.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> Add resume</a></div>
            @endcan
            <div class="ms-auto"><x-export-csv-button route="admin.resume-bank.export" /></div>
        </div>
        <div class="card-body">
            <div class="table-responsive table-responsive-sticky">
                <table class="table table-striped table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <x-th-sort column="name" :sort="$sort" :direction="$direction" label="Name" />
                            <th>Skills</th>
                            <th>Source</th>
                            <th>Resume</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($entries as $entry)
                            <tr>
                                <td>
                                    {{ $entry->name }}
                                    @if ($entry->email)
                                        <br><small class="text-muted">{{ $entry->email }}</small>
                                    @endif
                                </td>
                                <td>{{ $entry->skills ?: '—' }}</td>
                                <td>{{ $entry->source ?: '—' }}</td>
                                <td>
                                    @if ($entry->resume_path)
                                        <a href="{{ asset('storage/'.$entry->resume_path) }}" target="_blank">View</a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @can('update', $entry)
                                        <a href="{{ route('admin.resume-bank.edit', $entry) }}" class="btn-icon" title="Edit"><i class="ti ti-pencil font-16"></i></a>
                                    @endcan
                                    @can('delete', $entry)
                                        <form action="{{ route('admin.resume-bank.destroy', $entry) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this resume?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">
                                    <i class="ti ti-file-text font-24 d-block mb-2"></i>
                                    No resumes in the bank yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $entries->links() }}
            </div>
        </div>
    </div>
@endsection
