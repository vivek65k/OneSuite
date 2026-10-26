@extends('layouts.admin')

@section('title', 'CRM Settings')

@section('content')
    <x-page-title title="CRM Settings" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card list-view mb-3" style="max-width: 640px;">
        <div class="list-filter-panel">
            <form method="GET" action="{{ route('admin.crm.settings.index') }}" class="d-flex gap-2 align-items-end">
                <div class="flex-fill">
                    <label class="form-label">Configuring</label>
                    <select name="branch_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="" @selected(! $branchId)>Company Default (applies to every branch)</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) $branchId === (string) $branch->id)>{{ $branch->name }} (override)</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    <div class="card form-section tone-indigo" style="max-width: 640px;">
        <div class="form-section-head">
            <span class="form-section-icon"><i class="ti ti-settings"></i></span>
            <div>
                <h5 class="form-section-title">{{ $branchId ? 'Branch override' : 'Company default settings' }}</h5>
                <p class="form-section-sub">
                    @if ($branchId)
                        These values override the company default for this branch only. Leave the Company Default page unchanged to fall back for every other branch.
                    @else
                        Applied to every branch unless a branch has its own override saved.
                    @endif
                </p>
            </div>
        </div>
        <div class="form-section-body">
            <form method="POST" action="{{ route('admin.crm.settings.update') }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="branch_id" value="{{ $branchId }}">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Default Currency</label>
                        <input type="text" class="form-control" name="default_currency" value="{{ old('default_currency', $settings->default_currency) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Fiscal Year Start Month</label>
                        <select name="fiscal_year_start_month" class="form-select">
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" @selected((int) old('fiscal_year_start_month', $settings->fiscal_year_start_month) === $m)>{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Business Hours Start</label>
                        <input type="time" class="form-control" name="business_hours_start" value="{{ old('business_hours_start', $settings->business_hours_start) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Business Hours End</label>
                        <input type="time" class="form-control" name="business_hours_end" value="{{ old('business_hours_end', $settings->business_hours_end) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Default SLA Policy</label>
                        <select name="default_sla_policy_id" class="form-select">
                            <option value="">None</option>
                            @foreach ($slaPolicies as $policy)
                                <option value="{{ $policy->id }}" @selected(old('default_sla_policy_id', $settings->default_sla_policy_id) == $policy->id)>{{ $policy->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Pipeline Mode</label>
                        <select name="pipeline_mode" class="form-select">
                            @foreach (\App\Models\Crm\CrmSetting::PIPELINE_MODES as $key => $label)
                                <option value="{{ $key }}" @selected(old('pipeline_mode', $settings->pipeline_mode) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" name="auto_assign_leads" id="auto_assign_leads" value="1" @checked(old('auto_assign_leads', $settings->auto_assign_leads))>
                            <label class="form-check-label" for="auto_assign_leads">Auto-assign new leads</label>
                        </div>
                    </div>
                </div>

                @if ($branchId && $companyDefault)
                    <div class="alert alert-info font-13 mt-3 mb-0">Company default for comparison: {{ $companyDefault->default_currency }}, SLA "{{ $companyDefault->default_sla_policy_id ? \App\Models\Crm\SlaPolicy::find($companyDefault->default_sla_policy_id)?->name : 'None' }}", pipeline mode "{{ \App\Models\Crm\CrmSetting::PIPELINE_MODES[$companyDefault->pipeline_mode] ?? $companyDefault->pipeline_mode }}".</div>
                @endif

                <div class="mt-3"><button type="submit" class="btn btn-de-primary text-light px-4">Save Settings</button></div>
            </form>
        </div>
    </div>
@endsection
