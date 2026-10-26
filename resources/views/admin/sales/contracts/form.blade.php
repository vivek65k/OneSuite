@extends('layouts.admin')

@php
    $isEditing = $contract->exists;
    $cancelUrl = route('admin.sales.contracts.index');
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

    <form method="POST" action="{{ $isEditing ? route('admin.sales.contracts.update', $contract) : route('admin.sales.contracts.store') }}">
        @csrf
        @if ($isEditing) @method('PUT') @endif

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card form-section tone-indigo">
                    <div class="form-section-head">
                        <span class="form-section-icon"><i class="ti ti-file-text"></i></span>
                        <div>
                            <h5 class="form-section-title">Contract</h5>
                            @if ($isEditing)<p class="form-section-sub">{{ $contract->contract_number }}</p>@endif
                        </div>
                    </div>
                    <div class="form-section-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="title" value="{{ old('title', $contract->title) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Account</label>
                                <select class="form-select" name="account_id">
                                    <option value="">— None —</option>
                                    @foreach ($accounts as $id => $name)
                                        <option value="{{ $id }}" @selected((string) old('account_id', $contract->account_id) === (string) $id)>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Sales order</label>
                                <select class="form-select" name="sales_order_id">
                                    <option value="">— None —</option>
                                    @foreach ($salesOrders as $id => $number)
                                        <option value="{{ $id }}" @selected((string) old('sales_order_id', $contract->sales_order_id) === (string) $id)>{{ $number }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Contract value <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" class="form-control" name="value" value="{{ old('value', $contract->value) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Start date</label>
                                <input type="date" class="form-control" name="start_date" value="{{ old('start_date', optional($contract->start_date)->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">End date</label>
                                <input type="date" class="form-control" name="end_date" value="{{ old('end_date', optional($contract->end_date)->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Billing Frequency</label>
                                <select class="form-select" name="billing_frequency">
                                    @foreach (\App\Models\Crm\Contract::BILLING_FREQUENCIES as $key => $label)
                                        <option value="{{ $key }}" @selected(old('billing_frequency', $contract->billing_frequency ?? 'one_time') === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Next Billing Date</label>
                                <input type="date" class="form-control" name="next_billing_date" value="{{ old('next_billing_date', optional($contract->next_billing_date)->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Owner</label>
                                <select class="form-select" name="owner_employee_id">
                                    <option value="">— Unassigned —</option>
                                    @foreach ($employees as $id => $name)
                                        <option value="{{ $id }}" @selected((string) old('owner_employee_id', $contract->owner_employee_id) === (string) $id)>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="auto_renew" value="1" @checked(old('auto_renew', $contract->auto_renew))>
                                    <label class="form-check-label">Auto-renew when it ends</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if ($isEditing && $contract->renewals->isNotEmpty())
                    <div class="card list-view">
                        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-refresh"></i> Renewal Chain</div></div>
                        <div class="table-responsive">
                            <table class="table table-hover list-table mb-0">
                                <thead class="thead-light"><tr><th>Contract #</th><th>Status</th><th>Start</th><th>End</th></tr></thead>
                                <tbody>
                                    @foreach ($contract->renewals as $renewal)
                                        <tr>
                                            <td class="font-13"><a href="{{ route('admin.sales.contracts.edit', $renewal) }}">{{ $renewal->contract_number }}</a></td>
                                            <td><span class="tag tone-{{ $renewal->status === 'active' ? 'success' : 'slate' }} font-11">{{ \App\Models\Crm\Contract::STATUSES[$renewal->status] }}</span></td>
                                            <td class="text-muted font-13">{{ $renewal->start_date?->format('M j, Y') }}</td>
                                            <td class="text-muted font-13">{{ $renewal->end_date?->format('M j, Y') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
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
                        <div class="card form-section tone-amber">
                            <div class="form-section-head form-section-head-compact">
                                <span class="form-section-icon form-section-icon-sm"><i class="ti ti-repeat"></i></span>
                                <h5 class="form-section-title">Recurring Business</h5>
                            </div>
                            <div class="form-section-body d-grid gap-2">
                                @if ($contract->status === 'active' && $contract->billing_frequency !== 'one_time')
                                    <button type="submit" form="generate-order-form" class="btn btn-de-primary btn-sm text-light"><i class="ti ti-plus font-14 me-1"></i> Generate Next Order</button>
                                @endif
                                <button type="submit" form="renew-form" class="btn btn-light btn-sm"><i class="ti ti-refresh font-14 me-1"></i> Renew Contract</button>
                            </div>
                        </div>

                        @if ($contract->renewedFrom)
                            <div class="card form-section tone-sky">
                                <div class="form-section-head form-section-head-compact">
                                    <span class="form-section-icon form-section-icon-sm"><i class="ti ti-refresh"></i></span>
                                    <h5 class="form-section-title">Renewed from</h5>
                                </div>
                                <div class="form-section-body">
                                    <a href="{{ route('admin.sales.contracts.edit', $contract->renewedFrom) }}">{{ $contract->renewedFrom->contract_number }}</a>
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
                                            <span class="tag tone-{{ $sig->status === 'signed' ? 'success' : ($sig->status === 'declined' ? 'danger' : 'amber') }} font-11">{{ \App\Models\Crm\ESignatureRequest::STATUSES[$sig->status] ?? $sig->status }}</span>
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
            <button type="submit" class="btn btn-de-primary text-light px-4"><i class="ti ti-check font-14 me-1"></i> {{ $isEditing ? 'Save changes' : 'Create contract' }}</button>
        </div>
    </form>

    @if ($isEditing)
        <form id="renew-form" action="{{ route('admin.sales.contracts.renew', $contract) }}" method="POST" class="d-none">@csrf</form>
        <form id="generate-order-form" action="{{ route('admin.sales.contracts.generate-order', $contract) }}" method="POST" class="d-none">@csrf</form>
    @endif
@endsection
