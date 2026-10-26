@extends('layouts.admin')

@section('title', 'Sales Activities')

@php
    $statusTone = fn ($status) => match ($status) {
        'completed' => 'success',
        'cancelled' => 'slate',
        default => 'amber',
    };
    $typeIcon = fn ($type) => match ($type) {
        'call' => 'ti-phone', 'meeting' => 'ti-users', 'email' => 'ti-mail', default => 'ti-checkbox',
    };
@endphp

@section('content')
    <x-page-title title="Sales Activities" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <p class="text-muted font-13 mb-3"><i class="ti ti-info-circle"></i> One daily-work view across tasks, calls, meetings and emails - full dedicated screens for each are still under CRM. Activities here can now also be logged against Quotations, Sales Orders and Contracts.</p>

    <div class="kpi-strip mb-3">
        <x-kpi-card label="Due Today" :value="$dueTodayCount" icon="ti-calendar-event" />
        <x-kpi-card label="Overdue" :value="$overdueCount" icon="ti-alert-triangle" tone="danger" />
        <x-kpi-card label="My Pending" :value="$pendingCount" icon="ti-checkbox" tone="amber" />
    </div>

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> Log an activity</div></div>
        <div class="list-filter-panel">
            <form method="POST" action="{{ route('admin.sales.activities.store') }}" id="activity-form">
                @csrf
                <div class="row g-2 mb-2">
                    <div class="col-md-2">
                        <select name="type" id="activity-type" class="form-select form-select-sm">
                            @foreach ($types as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-4"><input type="text" name="subject" class="form-control form-control-sm" placeholder="Subject" required></div>
                    <div class="col-md-3">
                        <select name="relatable" class="form-select form-select-sm">
                            <option value="">Not linked to a record</option>
                            @foreach ($relatable as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="owner_employee_id" class="form-select form-select-sm">
                            <option value="">— Unassigned —</option>
                            @foreach ($employees as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-3 field-due-at"><label class="form-label font-11 mb-0">Due</label><input type="datetime-local" name="due_at" class="form-control form-control-sm"></div>
                    <div class="col-md-3 field-start-at d-none"><label class="form-label font-11 mb-0">Start</label><input type="datetime-local" name="start_at" class="form-control form-control-sm"></div>
                    <div class="col-md-3 field-end-at d-none"><label class="form-label font-11 mb-0">End</label><input type="datetime-local" name="end_at" class="form-control form-control-sm"></div>
                    <div class="col-md-2 field-direction d-none">
                        <label class="form-label font-11 mb-0">Direction</label>
                        <select name="direction" class="form-select form-select-sm">
                            <option value="outbound">Outbound</option>
                            <option value="inbound">Inbound</option>
                        </select>
                    </div>
                    <div class="col-md-2 field-priority">
                        <label class="form-label font-11 mb-0">Priority</label>
                        <select name="priority" class="form-select form-select-sm">
                            @foreach ($priorities as $key => $label)<option value="{{ $key }}" @selected($key === 'medium')>{{ $label }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <textarea name="description" rows="2" class="form-control form-control-sm mb-2" placeholder="Notes (optional)"></textarea>
                <button type="submit" class="btn btn-de-primary text-light btn-sm px-4">Log Activity</button>
            </form>
        </div>
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.sales.activities.index') }}">
            <div class="list-toolbar">
                <div class="list-toolbar-title"><i class="ti ti-list-check"></i> Activities <span class="list-tab-count">{{ $activities->total() }}</span></div>
                <div class="list-toolbar-actions">
                    <select name="scope" class="form-select form-select-sm" onchange="this.form.submit()" style="width:auto;">
                        <option value="mine" @selected($scope === 'mine')>My Activities</option>
                        <option value="all" @selected($scope === 'all')>Everyone</option>
                    </select>
                    <select name="type" class="form-select form-select-sm" onchange="this.form.submit()" style="width:auto;">
                        <option value="">All Types</option>
                        @foreach ($types as $key => $label)<option value="{{ $key }}" @selected($type === $key)>{{ $label }}</option>@endforeach
                    </select>
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()" style="width:auto;">
                        <option value="">Any Status</option>
                        @foreach ($statuses as $key => $label)<option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>@endforeach
                    </select>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Type</th><th>Subject</th><th>Related To</th><th>Owner</th><th>Due</th><th>Priority</th><th>Status</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($activities as $activity)
                        <tr>
                            <td><i class="ti {{ $typeIcon($activity->type) }} font-16 me-1"></i><span class="font-13">{{ $types[$activity->type] }}</span></td>
                            <td class="font-13">{{ $activity->subject }}</td>
                            <td class="text-muted font-13">{{ $activity->relatedLabel ?? '—' }}</td>
                            <td class="text-muted font-13">{{ $activity->owner?->name ?? '—' }}</td>
                            <td class="text-muted font-13">{{ $activity->due_at?->format('M j, Y H:i') ?? $activity->start_at?->format('M j, Y H:i') ?? '—' }}</td>
                            <td><span class="tag font-11 {{ $activity->priority === 'high' ? 'tone-danger' : ($activity->priority === 'low' ? 'tone-slate' : 'tone-amber') }}">{{ $priorities[$activity->priority] ?? '—' }}</span></td>
                            <td><span class="tag tone-{{ $statusTone($activity->status) }} font-11">{{ $statuses[$activity->status] }}</span></td>
                            <td class="text-end">
                                @if ($activity->status === 'pending')
                                    <form action="{{ route('admin.sales.activities.complete', $activity) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn-icon btn-icon-accent" title="Complete"><i class="ti ti-check font-16"></i></button>
                                    </form>
                                @endif
                                <form action="{{ route('admin.sales.activities.destroy', $activity) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this activity?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-5"><i class="ti ti-list-check font-24 d-block mb-2"></i>No activities found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($activities->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $activities->links() }}</div></div>
        @endif
    </div>

    <script>
        (function () {
            var typeSelect = document.getElementById('activity-type');
            var form = document.getElementById('activity-form');
            function sync() {
                var type = typeSelect.value;
                form.querySelector('.field-due-at').classList.toggle('d-none', type === 'meeting');
                form.querySelector('.field-start-at').classList.toggle('d-none', type !== 'meeting');
                form.querySelector('.field-end-at').classList.toggle('d-none', type !== 'meeting');
                form.querySelector('.field-direction').classList.toggle('d-none', type !== 'call' && type !== 'email');
            }
            typeSelect.addEventListener('change', sync);
            sync();
        })();
    </script>
@endsection
