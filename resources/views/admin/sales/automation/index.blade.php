@extends('layouts.admin')

@section('title', 'Automation & AI')

@section('content')
    <x-page-title title="Automation & AI" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <p class="text-muted font-13 mb-3"><i class="ti ti-info-circle"></i> Rules here run on demand (click Run), same as every other automation screen in CRM - nothing fires automatically on a schedule yet. Recommendations below are computed from live Sales/Inventory/CRM data, not a trained model.</p>

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-link"></i> CRM AI Screens</div></div>
        <div class="list-filter-panel">
            <div class="row g-2">
                <div class="col-md-3"><a href="{{ route('admin.crm.ai-sales-assistant.index') }}" class="btn btn-light w-100"><i class="ti ti-headset font-14 me-1"></i> AI Sales Assistant</a></div>
                <div class="col-md-3"><a href="{{ route('admin.crm.ai-next-best-action.index') }}" class="btn btn-light w-100"><i class="ti ti-bulb font-14 me-1"></i> Next Best Action</a></div>
                <div class="col-md-3"><a href="{{ route('admin.crm.ai-win-probability.index') }}" class="btn btn-light w-100"><i class="ti ti-percentage font-14 me-1"></i> Win Probability</a></div>
                <div class="col-md-3"><a href="{{ route('admin.crm.ai-churn-prediction.index') }}" class="btn btn-light w-100"><i class="ti ti-user-x font-14 me-1"></i> Churn Prediction</a></div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            <div class="card list-view h-100">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-alert-triangle"></i> Backorders <span class="list-tab-count">{{ $recommendations['backorders']->count() }}</span></div></div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <tbody>
                            @forelse ($recommendations['backorders'] as $line)
                                <tr>
                                    <td class="font-13">
                                        <a href="{{ route('admin.sales.orders.edit', $line->salesOrder) }}">{{ $line->salesOrder->order_number }}</a>
                                        <div class="text-muted font-11">{{ $line->description }} — {{ $line->backorder_qty }} short</div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td class="text-center text-muted py-4">No backorders right now.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card list-view h-100">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-wallet"></i> Over Credit Limit <span class="list-tab-count">{{ $recommendations['overLimitCustomers']->count() }}</span></div></div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <tbody>
                            @forelse ($recommendations['overLimitCustomers'] as $profile)
                                <tr>
                                    <td class="font-13">
                                        <a href="{{ route('admin.sales.customers.edit', $profile->account) }}">{{ $profile->account?->name }}</a>
                                        <div class="text-muted font-11">Outstanding {{ number_format($profile->outstanding_balance, 2) }} vs limit {{ number_format($profile->credit_limit, 2) }}</div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td class="text-center text-muted py-4">No customers over their credit limit.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card list-view h-100">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-target"></i> Quotas At Risk <span class="list-tab-count">{{ $recommendations['quotasAtRisk']->count() }}</span></div></div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <tbody>
                            @forelse ($recommendations['quotasAtRisk'] as $quota)
                                <tr>
                                    <td class="font-13">
                                        <a href="{{ route('admin.sales.team-targets.index') }}">{{ $quota->employee?->name ?? $quota->team?->name }}</a>
                                        <div class="text-muted font-11">Pacing behind target this {{ $quota->period }}</div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td class="text-center text-muted py-4">Everyone is on pace.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> Create a rule</div></div>
        <div class="list-filter-panel">
            <form method="POST" action="{{ route('admin.sales.automation.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-3"><input type="text" name="name" class="form-control form-control-sm" placeholder="Rule name" required></div>
                <div class="col-md-2">
                    <select name="category" class="form-select form-select-sm" required>
                        @foreach ($categories as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="entity" class="form-select form-select-sm" required>
                        @foreach ($entities as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="trigger_event" class="form-select form-select-sm">
                        <option value="">— No trigger —</option>
                        @foreach ($triggerEvents as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-1"><input type="number" min="1" name="condition_value" class="form-control form-control-sm" placeholder="Days"></div>
                <div class="col-md-2">
                    <select name="action_employee_id" class="form-select form-select-sm">
                        <option value="">— Assign to —</option>
                        @foreach ($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-12"><button type="submit" class="btn btn-de-primary text-light btn-sm mt-1">Create Rule</button></div>
            </form>
        </div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-settings-automation"></i> Sales Automation Rules <span class="list-tab-count">{{ $rules->count() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Name</th><th>Category</th><th>Entity</th><th>Trigger</th><th>Assign To</th><th>Runs</th><th>Status</th><th class="text-end" style="min-width:160px"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($rules as $rule)
                        <tr>
                            <td class="font-13">{{ $rule->name }}</td>
                            <td class="text-muted font-13">{{ \App\Models\Crm\AutomationRule::CATEGORIES[$rule->category] ?? $rule->category }}</td>
                            <td class="text-muted font-13">{{ \App\Models\Crm\AutomationRule::ENTITIES[$rule->entity] ?? $rule->entity }}</td>
                            <td class="text-muted font-13">{{ \App\Models\Crm\AutomationRule::TRIGGER_EVENTS[$rule->trigger_event] ?? '—' }}</td>
                            <td class="text-muted font-13">{{ $rule->actionEmployee?->name ?? '—' }}</td>
                            <td class="text-muted font-13">{{ $rule->run_count }} {{ $rule->last_run_at ? '· '.$rule->last_run_at->diffForHumans() : '' }}</td>
                            <td>
                                <form action="{{ route('admin.sales.automation.toggle', $rule) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="tag font-11 border-0 {{ $rule->is_active ? 'tone-success' : 'tone-slate' }}">{{ $rule->is_active ? 'Active' : 'Paused' }}</button>
                                </form>
                            </td>
                            <td class="text-end">
                                <form action="{{ route('admin.sales.automation.run', $rule) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-light btn-sm">Run</button>
                                </form>
                                <form action="{{ route('admin.sales.automation.destroy', $rule) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-5"><i class="ti ti-settings-automation font-24 d-block mb-2"></i>No Sales automation rules yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
