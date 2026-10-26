@extends('layouts.admin')

@php
    $isEditing = $case->exists;
@endphp

@section('title', $isEditing ? 'Edit Case' : 'New Case')

@section('content')
    <x-page-title :title="$isEditing ? 'Edit Case' : 'New Case'" :breadcrumbs="['Cases' => route('admin.crm.cases.index')]" />

    <div class="card form-section tone-indigo" style="max-width: 760px;">
        <div class="form-section-head">
            <span class="form-section-icon"><i class="ti ti-ticket"></i></span>
            <div>
                <h5 class="form-section-title">{{ $isEditing ? ($case->case_number.' - '.$case->subject) : 'New case' }}</h5>
                <p class="form-section-sub">Attach an SLA policy to auto-calculate response and resolution deadlines.</p>
            </div>
        </div>
        <div class="form-section-body">
            <form method="POST" action="{{ $isEditing ? route('admin.crm.cases.update', $case) : route('admin.crm.cases.store') }}">
                @csrf
                @if ($isEditing) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Subject <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('subject') is-invalid @enderror" name="subject" value="{{ old('subject', $case->subject) }}" required autofocus>
                        @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select">
                            @foreach (\App\Models\Crm\CaseModel::TYPES as $key => $label)
                                <option value="{{ $key }}" @selected(old('type', $case->type ?? 'case') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3">{{ old('description', $case->description) }}</textarea>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            @foreach (\App\Models\Crm\CaseModel::STATUSES as $key => $label)
                                <option value="{{ $key }}" @selected(old('status', $case->status ?? 'open') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Priority</label>
                        <select name="priority" class="form-select">
                            @foreach (\App\Models\Crm\CaseModel::PRIORITIES as $key => $label)
                                <option value="{{ $key }}" @selected(old('priority', $case->priority ?? 'medium') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Source</label>
                        <input type="text" class="form-control" name="source" value="{{ old('source', $case->source) }}" placeholder="Email, Phone, Portal...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">SLA Policy</label>
                        <select name="sla_policy_id" class="form-select">
                            <option value="">None</option>
                            @foreach ($slaPolicies as $policy)
                                <option value="{{ $policy->id }}" @selected(old('sla_policy_id', $case->sla_policy_id) == $policy->id)>{{ $policy->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Account</label>
                        <select name="account_id" class="form-select">
                            <option value="">None</option>
                            @foreach ($accounts as $account)
                                <option value="{{ $account->id }}" @selected(old('account_id', $case->account_id) == $account->id)>{{ $account->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Contact</label>
                        <select name="contact_id" class="form-select">
                            <option value="">None</option>
                            @foreach ($contacts as $contact)
                                <option value="{{ $contact->id }}" @selected(old('contact_id', $case->contact_id) == $contact->id)>{{ $contact->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Assigned To</label>
                        <select name="assigned_to_employee_id" class="form-select">
                            <option value="">Unassigned</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}" @selected(old('assigned_to_employee_id', $case->assigned_to_employee_id) == $employee->id)>{{ $employee->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Branch</label>
                        <select name="branch_id" class="form-select">
                            <option value="">Company-wide</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((string) old('branch_id', $case->branch_id) === (string) $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if ($isEditing && ($case->response_due_at || $case->resolution_due_at))
                        <div class="col-md-12">
                            <div class="alert alert-info mb-0 font-13">
                                @if ($case->response_due_at) Response due: {{ $case->response_due_at->format('M j, Y g:i A') }} @endif
                                @if ($case->resolution_due_at) &middot; Resolution due: {{ $case->resolution_due_at->format('M j, Y g:i A') }} @endif
                            </div>
                        </div>
                    @endif
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-de-primary text-light px-4">{{ $isEditing ? 'Save changes' : 'Create case' }}</button>
                    <a href="{{ route('admin.crm.cases.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>

            @if ($isEditing && ! $case->is_escalated)
                <hr>
                <form method="POST" action="{{ route('admin.crm.cases.escalate', $case) }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-5">
                        <label class="form-label">Escalate to</label>
                        <select name="escalated_to_employee_id" class="form-select form-select-sm" required>
                            <option value="">Select employee</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Reason</label>
                        <input type="text" name="escalation_reason" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2"><button type="submit" class="btn btn-light btn-sm w-100"><i class="ti ti-arrow-up-circle font-14 me-1"></i> Escalate</button></div>
                </form>
            @elseif ($isEditing && $case->is_escalated)
                <hr>
                <div class="alert alert-warning mb-0 font-13"><i class="ti ti-arrow-up-circle"></i> Escalated to {{ $case->escalatedTo?->name }} on {{ $case->escalated_at?->format('M j, Y') }} @if($case->escalation_reason) &mdash; {{ $case->escalation_reason }} @endif</div>
            @endif
        </div>
    </div>
@endsection
