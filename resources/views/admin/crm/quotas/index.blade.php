@extends('layouts.admin')

@section('title', 'Targets & Quotas')

@section('content')
    <x-page-title title="Targets &amp; Quotas" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> Set a quota</div></div>
        <div class="list-filter-panel">
            <form method="POST" action="{{ route('admin.crm.quotas.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-3">
                    <label class="form-label">Employee</label>
                    <select name="employee_id" class="form-select form-select-sm">
                        <option value="">None</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Team</label>
                    <select name="team_id" class="form-select form-select-sm">
                        <option value="">None</option>
                        @foreach ($teams as $team)
                            <option value="{{ $team->id }}">{{ $team->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Period</label>
                    <select name="period" class="form-select form-select-sm">
                        @foreach (\App\Models\Crm\Quota::PERIODS as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label">Starts</label><input type="date" name="period_start" class="form-control form-control-sm" required></div>
                <div class="col-md-1"><label class="form-label">Target</label><input type="number" step="0.01" name="target_amount" class="form-control form-control-sm" required></div>
                <div class="col-md-1"><button type="submit" class="btn btn-de-primary btn-sm text-light w-100">Add</button></div>
            </form>
        </div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-target"></i> All Quotas <span class="list-tab-count">{{ $rows->count() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Owner</th><th>Period</th><th>Target</th><th>Achieved</th><th>Progress</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td class="fw-semibold">{{ $row['quota']->employee?->name ?? $row['quota']->team?->name ?? '—' }}</td>
                            <td class="text-muted font-13">{{ \App\Models\Crm\Quota::PERIODS[$row['quota']->period] }} from {{ $row['quota']->period_start->format('M j, Y') }}</td>
                            <td>{{ number_format($row['quota']->target_amount, 0) }}</td>
                            <td>{{ number_format($row['achieved'], 0) }}</td>
                            <td style="min-width:140px;">
                                <div class="mini-progress"><span style="width: {{ min($row['pct'], 100) }}%;" class="bg-{{ $row['pct'] >= 100 ? 'success' : ($row['pct'] >= 50 ? 'warning' : 'danger') }}"></span></div>
                                <span class="font-11 text-muted">{{ $row['pct'] }}%</span>
                            </td>
                            <td class="text-end">
                                <form action="{{ route('admin.crm.quotas.destroy', $row['quota']) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this quota?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Remove"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5"><i class="ti ti-target font-24 d-block mb-2"></i>No quotas set yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
