@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

<div class="card list-view mb-3">
    <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> New rule</div></div>
    <div class="list-filter-panel">
        <form method="POST" action="{{ route($routePrefix.'.store') }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-3"><label class="form-label">Name <span class="text-danger">*</span></label><input type="text" name="name" class="form-control form-control-sm" required></div>
            @if ($showEntity ?? true)
                <div class="col-md-2">
                    <label class="form-label">Entity</label>
                    <select name="entity" class="form-select form-select-sm">
                        @foreach ($entities as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            @if ($showTrigger ?? false)
                <div class="col-md-3">
                    <label class="form-label">Trigger Event</label>
                    <select name="trigger_event" class="form-select form-select-sm">
                        @foreach ($triggerEvents as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            @if ($showCondition ?? true)
                <div class="col-md-2"><label class="form-label">Condition Value</label><input type="text" name="condition_value" class="form-control form-control-sm" placeholder="e.g. days or value"></div>
            @endif
            @if ($showActionType ?? false)
                <div class="col-md-2">
                    <label class="form-label">Action</label>
                    <select name="action_type" class="form-select form-select-sm">
                        @foreach ($actionTypes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-md-2">
                <label class="form-label">{{ $employeeLabel ?? 'Assign/Escalate To' }}</label>
                <select name="action_employee_id" class="form-select form-select-sm">
                    <option value="">None</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1"><button type="submit" class="btn btn-de-primary btn-sm text-light w-100">Add</button></div>
        </form>
    </div>
</div>

<div class="card list-view">
    <div class="list-toolbar"><div class="list-toolbar-title"><i class="{{ $icon }}"></i> All Rules <span class="list-tab-count">{{ $rules->count() }}</span></div></div>
    <div class="table-responsive">
        <table class="table table-hover list-table mb-0">
            <thead class="thead-light"><tr><th>Name</th><th>Entity</th><th>Condition</th><th>Assigned/Escalated To</th><th>Runs</th><th>Status</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
            <tbody>
                @forelse ($rules as $rule)
                    <tr>
                        <td class="fw-semibold">{{ $rule->name }}</td>
                        <td class="text-muted font-13">{{ $entities[$rule->entity] ?? ($triggerEvents[$rule->trigger_event] ?? '—') }}</td>
                        <td class="text-muted font-13">{{ $rule->condition_value ?: '—' }}</td>
                        <td class="text-muted font-13">{{ $rule->actionEmployee?->name ?? '—' }}</td>
                        <td>{{ $rule->run_count }} @if($rule->last_run_at)<span class="d-block font-11 text-muted">{{ $rule->last_run_at->diffForHumans() }}</span>@endif</td>
                        <td>
                            <form action="{{ route($routePrefix.'.toggle', $rule) }}" method="POST" class="d-inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="switch-btn {{ $rule->is_active ? 'is-on' : '' }}" role="switch" aria-checked="{{ $rule->is_active ? 'true' : 'false' }}" title="{{ $rule->is_active ? 'Active' : 'Paused' }}">
                                    <span class="visually-hidden">Toggle {{ $rule->name }}</span>
                                </button>
                            </form>
                        </td>
                        <td class="text-end">
                            @if ($showRun ?? true)
                                <form action="{{ route($routePrefix.'.run', $rule) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn-icon" title="Run now"><i class="ti ti-player-play font-16"></i></button>
                                </form>
                            @endif
                            <form action="{{ route($routePrefix.'.destroy', $rule) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this rule?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-icon btn-icon-danger" title="Remove"><i class="ti ti-trash font-16"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-5"><i class="{{ $icon }} font-24 d-block mb-2"></i>No rules configured yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
