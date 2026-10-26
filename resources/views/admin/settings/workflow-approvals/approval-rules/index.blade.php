@extends('layouts.admin')

@section('title', 'Approval Matrix')

@section('content')
    <x-page-title title="Approval Matrix" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card">
        <div class="card-header d-flex align-items-center">
            <i class="ti ti-filter font-18 me-2 text-muted"></i>
            <h4 class="card-title mb-0">Filters</h4>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.approval-rules.index') }}" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">Search</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent"><i class="ti ti-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control" value="{{ $state['search'] ?? '' }}" placeholder="Name or module">
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
                    <a href="{{ route('admin.approval-rules.index') }}" class="btn btn-light"><i class="ti ti-x font-14 me-1"></i> Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center flex-wrap gap-2">
            @can('create', \App\Models\ApprovalRule::class)
                <div><a href="{{ route('admin.approval-rules.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> New rule</a></div>
            @endcan
            <div class="ms-auto"><x-export-csv-button route="admin.approval-rules.export" /></div>
        </div>
        <div class="card-body">
            <div class="table-responsive table-responsive-sticky">
                <table class="table table-striped table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <x-th-sort column="level" :sort="$sort" :direction="$direction" label="Level" />
                            <x-th-sort column="name" :sort="$sort" :direction="$direction" label="Name" />
                            <x-th-sort column="module" :sort="$sort" :direction="$direction" label="Applies to" />
                            <th>Department</th>
                            <th>Amount range</th>
                            <th>Approver</th>
                            <x-th-sort column="is_active" :sort="$sort" :direction="$direction" label="Active" />
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rules as $rule)
                            <tr>
                                <td><span class="badge badge-soft-primary">{{ $rule->level }}</span></td>
                                <td>{{ $rule->name }}</td>
                                <td>{{ $rule->module ?: '—' }}</td>
                                <td>{{ $rule->department?->name ?: 'Any' }}</td>
                                <td>
                                    @if ($rule->min_amount || $rule->max_amount)
                                        {{ $rule->min_amount ?? 0 }} &ndash; {{ $rule->max_amount ?? '∞' }}
                                    @else
                                        <span class="text-muted">Any</span>
                                    @endif
                                </td>
                                <td>{{ $rule->approver_role }}</td>
                                <td>
                                    <span class="badge {{ $rule->is_active ? 'badge-soft-success' : 'badge-soft-danger' }}">
                                        {{ $rule->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    @can('update', $rule)
                                        <a href="{{ route('admin.approval-rules.edit', $rule) }}" class="btn-icon" title="Edit"><i class="ti ti-pencil font-16"></i></a>
                                    @endcan
                                    @can('delete', $rule)
                                        <form action="{{ route('admin.approval-rules.destroy', $rule) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this rule?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="ti ti-list-check font-24 d-block mb-2"></i>
                                    No approval rules found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $rules->links() }}
            </div>
        </div>
    </div>
@endsection
