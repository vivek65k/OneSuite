@extends('layouts.admin')

@php
    $isEditing = $contract->exists;
    $cancelUrl = route('admin.crm.contracts.index');
@endphp

@section('title', $isEditing ? 'Edit Contract' : 'New Contract')

@section('content')
    <x-page-title :title="$isEditing ? 'Edit Contract' : 'New Contract'" :breadcrumbs="['Contracts' => $cancelUrl]" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <form method="POST" action="{{ $isEditing ? route('admin.crm.contracts.update', $contract) : route('admin.crm.contracts.store') }}">
        @csrf
        @if ($isEditing) @method('PUT') @endif

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card form-section tone-indigo">
                    <div class="form-section-head">
                        <span class="form-section-icon"><i class="ti ti-file-invoice"></i></span>
                        <div>
                            <h5 class="form-section-title">Contract</h5>
                            @if ($isEditing)<p class="form-section-sub">{{ $contract->contract_number }}</p>@endif
                        </div>
                    </div>
                    <div class="form-section-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="title">Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" value="{{ old('title', $contract->title) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="account_id">Account</label>
                                <select class="form-select" id="account_id" name="account_id">
                                    <option value="">— None —</option>
                                    @foreach ($accounts as $id => $name)
                                        <option value="{{ $id }}" @selected((string) old('account_id', $contract->account_id) === (string) $id)>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="sales_order_id">Sales order</label>
                                <select class="form-select" id="sales_order_id" name="sales_order_id">
                                    <option value="">— None —</option>
                                    @foreach ($salesOrders as $id => $number)
                                        <option value="{{ $id }}" @selected((string) old('sales_order_id', $contract->sales_order_id) === (string) $id)>{{ $number }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="value">Contract value <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" class="form-control" id="value" name="value" value="{{ old('value', $contract->value) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="start_date">Start date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="{{ old('start_date', optional($contract->start_date)->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="end_date">End date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="{{ old('end_date', optional($contract->end_date)->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="owner_employee_id">Owner</label>
                                <select class="form-select" id="owner_employee_id" name="owner_employee_id">
                                    <option value="">— Unassigned —</option>
                                    @foreach ($employees as $id => $name)
                                        <option value="{{ $id }}" @selected((string) old('owner_employee_id', $contract->owner_employee_id) === (string) $id)>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="auto_renew" name="auto_renew" value="1" @checked(old('auto_renew', $contract->auto_renew))>
                                    <label class="form-check-label" for="auto_renew">Auto-renew when it ends</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="record-sidebar">
                    <div class="card form-section tone-violet">
                        <div class="form-section-head form-section-head-compact">
                            <span class="form-section-icon form-section-icon-sm"><i class="ti ti-flag"></i></span>
                            <h5 class="form-section-title">Status</h5>
                        </div>
                        <div class="form-section-body">
                            <select class="form-select" name="status">
                                @foreach (\App\Models\Crm\Contract::STATUSES as $key => $label)
                                    <option value="{{ $key }}" @selected(old('status', $contract->status ?? 'draft') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @if ($isEditing)
                        @if ($contract->renewedFrom)
                            <div class="card form-section tone-sky">
                                <div class="form-section-head form-section-head-compact">
                                    <span class="form-section-icon form-section-icon-sm"><i class="ti ti-refresh"></i></span>
                                    <h5 class="form-section-title">Renewed from</h5>
                                </div>
                                <div class="form-section-body">
                                    <a href="{{ route('admin.crm.contracts.edit', $contract->renewedFrom) }}">{{ $contract->renewedFrom->contract_number }}</a>
                                </div>
                            </div>
                        @endif

                        @if ($contract->signatureRequests->isNotEmpty())
                            <div class="card form-section tone-amber">
                                <div class="form-section-head form-section-head-compact">
                                    <span class="form-section-icon form-section-icon-sm"><i class="ti ti-signature"></i></span>
                                    <h5 class="form-section-title">E-signatures</h5>
                                </div>
                                <div class="form-section-body">
                                    @foreach ($contract->signatureRequests as $sig)
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="font-13">{{ $sig->signer_name }}</span>
                                            <span class="tag tone-{{ $sig->status === 'signed' ? 'success' : ($sig->status === 'declined' ? 'rose' : 'amber') }}">{{ \App\Models\Crm\ESignatureRequest::STATUSES[$sig->status] ?? $sig->status }}</span>
                                        </div>
                                    @endforeach
                                    <a href="{{ route('admin.crm.esignatures.index') }}" class="font-13">Manage signatures</a>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        <div class="record-footer">
            <a href="{{ $cancelUrl }}" class="btn btn-light px-4">Cancel</a>
            <button type="submit" class="btn btn-de-primary text-light px-4">
                <i class="ti ti-check font-14 me-1"></i> {{ $isEditing ? 'Save changes' : 'Create contract' }}
            </button>
        </div>
    </form>
@endsection
