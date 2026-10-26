@extends('layouts.admin')

@section('title', 'Scheduled Jobs')

@section('content')
    <x-page-title title="Scheduled Jobs" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> New job</div></div>
        <div class="list-filter-panel">
            <form method="POST" action="{{ route('admin.crm.scheduled-jobs.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-4"><label class="form-label">Name</label><input type="text" name="name" class="form-control form-control-sm" required></div>
                <div class="col-md-4">
                    <label class="form-label">Job</label>
                    <select name="job_type" class="form-select form-select-sm">
                        @foreach (\App\Models\Crm\ScheduledJob::JOB_TYPES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Frequency</label>
                    <select name="frequency" class="form-select form-select-sm">
                        @foreach (\App\Models\Crm\ScheduledJob::FREQUENCIES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1"><button type="submit" class="btn btn-de-primary btn-sm text-light w-100">Add</button></div>
            </form>
        </div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-calendar-time"></i> All Jobs <span class="list-tab-count">{{ $jobs->count() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Name</th><th>Job</th><th>Frequency</th><th>Next Run</th><th>Last Result</th><th>Status</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($jobs as $job)
                        <tr>
                            <td class="fw-semibold">{{ $job->name }}</td>
                            <td class="text-muted font-13">{{ \App\Models\Crm\ScheduledJob::JOB_TYPES[$job->job_type] ?? $job->job_type }}</td>
                            <td><span class="tag tone-indigo">{{ \App\Models\Crm\ScheduledJob::FREQUENCIES[$job->frequency] ?? $job->frequency }}</span></td>
                            <td class="text-muted font-13">{{ $job->next_run_at?->format('M j, Y g:i A') ?? '—' }}</td>
                            <td class="text-muted font-12">{{ \Illuminate\Support\Str::limit($job->last_result, 50) ?: '—' }}</td>
                            <td>
                                <form action="{{ route('admin.crm.scheduled-jobs.toggle', $job) }}" method="POST" class="d-inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="switch-btn {{ $job->is_active ? 'is-on' : '' }}" role="switch" aria-checked="{{ $job->is_active ? 'true' : 'false' }}">
                                        <span class="visually-hidden">Toggle {{ $job->name }}</span>
                                    </button>
                                </form>
                            </td>
                            <td class="text-end">
                                <form action="{{ route('admin.crm.scheduled-jobs.run', $job) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn-icon" title="Run now"><i class="ti ti-player-play font-16"></i></button>
                                </form>
                                <form action="{{ route('admin.crm.scheduled-jobs.destroy', $job) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this job?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Remove"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-5"><i class="ti ti-calendar-time font-24 d-block mb-2"></i>No scheduled jobs yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
