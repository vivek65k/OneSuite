@extends('layouts.admin')

@section('title', 'Customer Commercial Profile')

@section('content')
    <x-page-title :title="$account->name" :breadcrumbs="['Customers' => route('admin.sales.customers.index')]" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <div class="record-header mb-3">
        <div>
            <h4 class="mb-0">{{ $account->name }}</h4>
            <p class="text-muted font-13 mb-0">{{ $account->industry ?? 'No industry set' }} · {{ $account->email ?? 'No email' }}</p>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card form-section tone-indigo">
                <div class="form-section-head">
                    <span class="form-section-icon"><i class="ti ti-id"></i></span>
                    <div>
                        <h5 class="form-section-title">Commercial Details</h5>
                        <p class="form-section-sub">Credit limit, payment terms, assigned price list and salesperson.</p>
                    </div>
                </div>
                <div class="form-section-body">
                    <form method="POST" action="{{ route('admin.sales.customers.update', $account) }}">
                        @csrf
                        @method('PUT')
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Credit Limit</label>
                                <input type="number" step="0.01" min="0" class="form-control" name="credit_limit" value="{{ old('credit_limit', $profile->credit_limit) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Payment Terms (days)</label>
                                <input type="number" min="0" class="form-control" name="payment_terms_days" value="{{ old('payment_terms_days', $profile->payment_terms_days) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    @foreach (\App\Models\Sales\CustomerProfile::STATUSES as $key => $label)
                                        <option value="{{ $key }}" @selected(old('status', $profile->status) === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Price List</label>
                                <select name="price_list_id" class="form-select">
                                    <option value="">— None (use list price) —</option>
                                    @foreach ($priceLists as $list)
                                        <option value="{{ $list->id }}" @selected((string) old('price_list_id', $profile->price_list_id) === (string) $list->id)>{{ $list->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Assigned Salesperson</label>
                                <select name="salesperson_employee_id" class="form-select">
                                    <option value="">— Unassigned —</option>
                                    @foreach ($employees as $employee)
                                        <option value="{{ $employee->id }}" @selected((string) old('salesperson_employee_id', $profile->salesperson_employee_id) === (string) $employee->id)>{{ $employee->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" rows="3" class="form-control">{{ old('notes', $profile->notes) }}</textarea>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-de-primary text-light px-4 mt-3">Save Commercial Profile</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card form-section tone-violet">
                <div class="form-section-head form-section-head-compact">
                    <span class="form-section-icon form-section-icon-sm"><i class="ti ti-wallet"></i></span>
                    <h5 class="form-section-title">Credit Standing</h5>
                </div>
                <div class="form-section-body">
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted font-13">Credit Limit</span><span class="font-13 fw-semibold">{{ number_format($profile->credit_limit, 2) }}</span></div>
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted font-13">Outstanding Balance</span><span class="font-13 fw-semibold">{{ number_format($profile->outstanding_balance, 2) }}</span></div>
                    <div class="d-flex justify-content-between"><span class="text-muted font-13">Available Credit</span><span class="font-13 fw-semibold {{ $profile->available_credit < 0 ? 'text-danger' : 'text-success' }}">{{ number_format($profile->available_credit, 2) }}</span></div>
                    @if ($profile->is_over_limit)
                        <div class="alert alert-danger mt-3 mb-0 font-13">This customer is over their credit limit.</div>
                    @endif
                    <p class="text-muted font-11 mt-3 mb-0">Outstanding balance will be kept in sync once Finance invoicing is integrated.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
