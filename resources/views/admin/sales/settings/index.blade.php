@extends('layouts.admin')

@section('title', 'Settings & Configuration')

@section('content')
    <x-page-title title="Settings & Configuration" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-link"></i> Other Sales Configuration</div></div>
        <div class="list-filter-panel">
            <div class="row g-2">
                <div class="col-md-3"><a href="{{ route('admin.inventory.numbering-documents.index') }}" class="btn btn-light w-100"><i class="ti ti-hash font-14 me-1"></i> Document Numbering</a></div>
                <div class="col-md-3"><a href="{{ route('admin.sales.pricing.index') }}" class="btn btn-light w-100"><i class="ti ti-tag font-14 me-1"></i> Price Lists</a></div>
                <div class="col-md-3"><a href="{{ route('admin.sales.quotation-templates.index') }}" class="btn btn-light w-100"><i class="ti ti-copy font-14 me-1"></i> Quotation Templates</a></div>
                <div class="col-md-3"><a href="{{ route('admin.sales.automation.index') }}" class="btn btn-light w-100"><i class="ti ti-settings-automation font-14 me-1"></i> Automation Rules</a></div>
            </div>
        </div>
    </div>

    <div class="card form-section tone-indigo">
        <div class="form-section-head">
            <span class="form-section-icon"><i class="ti ti-settings"></i></span>
            <div>
                <h5 class="form-section-title">Company-wide Sales Rules</h5>
                <p class="form-section-sub">Discount approval, payment terms, credit control and default pricing.</p>
            </div>
        </div>
        <div class="form-section-body">
            <form method="POST" action="{{ route('admin.sales.settings.update') }}">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Discount Approval Threshold %</label>
                        <input type="number" min="0" max="100" class="form-control" name="discount_approval_threshold_percent" value="{{ $settings->discount_approval_threshold_percent }}">
                        <p class="form-text font-11 mb-0">Quotations discounted above this percent need manager sign-off before they can be sent — shared by both CRM and Sales Quotation screens.</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Default Payment Terms (days)</label>
                        <input type="number" min="0" class="form-control" name="default_payment_terms_days" value="{{ $settings->default_payment_terms_days }}">
                        <p class="form-text font-11 mb-0">Suggested default when setting up a new customer's commercial profile.</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Default Price List</label>
                        <select name="default_price_list_id" class="form-select">
                            <option value="">— None (use catalog price) —</option>
                            @foreach ($priceLists as $priceList)
                                <option value="{{ $priceList->id }}" @selected($settings->default_price_list_id === $priceList->id)>{{ $priceList->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 d-flex flex-column gap-2 justify-content-center">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="require_credit_check" value="1" @checked($settings->require_credit_check)>
                            <label class="form-check-label">Check customer credit limit when confirming an order</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="block_order_over_credit_limit" value="1" @checked($settings->block_order_over_credit_limit)>
                            <label class="form-check-label">Block confirmation if it would exceed the limit (otherwise just warn)</label>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-de-primary text-light px-4 mt-3">Save Settings</button>
            </form>
        </div>
    </div>
@endsection
