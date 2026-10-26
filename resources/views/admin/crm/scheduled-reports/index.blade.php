@extends('layouts.admin')

@section('title', 'Scheduled Reports')

@section('content')
    <x-page-title title="Scheduled Reports" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> New schedule</div></div>
        <div class="list-filter-panel">
            <form method="POST" action="{{ route('admin.crm.scheduled-reports.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control form-control-sm" required></div>
                <div class="col-md-3">
                    <label class="form-label">Report</label>
                    <select name="custom_report_id" class="form-select form-select-sm">
                        <option value="">None</option>
                        @foreach ($reports as $report)
                            <option value="{{ $report->id }}">{{ $report->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Frequency</label>
                    <select name="frequency" class="form-select form-select-sm">
                        @foreach (\App\Models\Crm\ScheduledReport::FREQUENCIES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label">Recipients</label><input type="text" name="recipients" class="form-control form-control-sm" placeholder="email1@x.com, email2@x.com"></div>
                <div class="col-md-1"><button type="submit" class="btn btn-de-primary btn-sm text-light w-100">Add</button></div>
            </form>
        </div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-calendar-time"></i> All Schedules <span class="list-tab-count">{{ $schedules->count() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Name</th><th>Report</th><th>Frequency</th><th>Recipients</th><th>Next Run</th><th>Status</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($schedules as $schedule)
                        <tr>
                            <td class="fw-semibold">{{ $schedule->name }}</td>
                            <td class="text-muted font-13">{{ $schedule->customReport?->name ?? '—' }}</td>
                            <td><span class="tag tone-indigo">{{ \App\Models\Crm\ScheduledReport::FREQUENCIES[$schedule->frequency] ?? $schedule->frequency }}</span></td>
                            <td class="text-muted font-13">{{ $schedule->recipients ?: '—' }}</td>
                            <td class="text-muted font-13">{{ $schedule->next_run_at?->format('M j, Y') ?? '—' }}</td>
                            <td>
                                <form action="{{ route('admin.crm.scheduled-reports.toggle', $schedule) }}" method="POST" class="d-inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="switch-btn {{ $schedule->is_active ? 'is-on' : '' }}" role="switch" aria-checked="{{ $schedule->is_active ? 'true' : 'false' }}" title="{{ $schedule->is_active ? 'Active' : 'Paused' }}">
                                        <span class="visually-hidden">Toggle {{ $schedule->name }}</span>
                                    </button>
                                </form>
                            </td>
                            <td class="text-end">
                                <form action="{{ route('admin.crm.scheduled-reports.run', $schedule) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn-icon" title="Run now"><i class="ti ti-player-play font-16"></i></button>
                                </form>
                                <form action="{{ route('admin.crm.scheduled-reports.destroy', $schedule) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this schedule?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Remove"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-5"><i class="ti ti-calendar-time font-24 d-block mb-2"></i>No scheduled reports yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
