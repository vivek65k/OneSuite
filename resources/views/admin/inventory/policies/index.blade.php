@extends('layouts.admin')

@section('title', 'Inventory Policies')

@section('content')
    <x-page-title title="Inventory Policies" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <div class="card form-section tone-indigo">
        <div class="form-section-head">
            <span class="form-section-icon"><i class="ti ti-settings"></i></span>
            <div>
                <h5 class="form-section-title">Company-wide Inventory Policies</h5>
                <p class="form-section-sub">Negative stock, reservations, backorders, costing defaults and tracking validation.</p>
            </div>
        </div>
        <div class="form-section-body">
            <form method="POST" action="{{ route('admin.inventory.policies.update') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6 form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="allow_negative_stock" value="1" @checked($settings->allow_negative_stock)>
                        <label class="form-check-label">Allow negative stock (allow issuing beyond on-hand quantity)</label>
                    </div>
                    <div class="col-md-6 form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="allow_backorder" value="1" @checked($settings->allow_backorder)>
                        <label class="form-check-label">Allow backorders (reserve beyond available stock)</label>
                    </div>
                    <div class="col-md-6 form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="require_batch_number_validation" value="1" @checked($settings->require_batch_number_validation)>
                        <label class="form-check-label">Require batch number on receipt for batch-tracked items</label>
                    </div>
                    <div class="col-md-6 form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="require_serial_number_validation" value="1" @checked($settings->require_serial_number_validation)>
                        <label class="form-check-label">Require serial number on receipt for serial-tracked items</label>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Default Costing Method (for new products)</label>
                        <select name="default_costing_method" class="form-select">
                            @foreach ($costingMethods as $key => $label)<option value="{{ $key }}" @selected($settings->default_costing_method === $key)>{{ $label }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Landed Cost Overhead %</label>
                        <input type="number" step="0.01" min="0" max="100" name="landed_cost_overhead_percent" class="form-control" value="{{ $settings->landed_cost_overhead_percent }}">
                        <p class="form-text text-muted font-11 mb-0">Applied as a reference overhead when estimating landed cost on top of supplier price.</p>
                    </div>
                </div>
                <button type="submit" class="btn btn-de-primary text-light px-4 mt-3">Save Policies</button>
            </form>
        </div>
    </div>
@endsection
