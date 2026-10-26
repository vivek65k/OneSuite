@extends('layouts.admin')

@php
    $isEditing = $account->exists;
    $cancelUrl = route('admin.crm.accounts.index');
@endphp

@section('title', $isEditing ? 'Edit Account' : 'Add Account')

@section('content')
    <x-page-title :title="$isEditing ? 'Edit Account' : 'Add Account'" :breadcrumbs="['Accounts' => $cancelUrl]" />

    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1">Please fix {{ $errors->count() === 1 ? 'this issue' : 'these '.$errors->count().' issues' }} before saving:</div>
            <ul class="mb-0 ps-3 font-13">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $isEditing ? route('admin.crm.accounts.update', $account) : route('admin.crm.accounts.store') }}">
        @csrf
        @if ($isEditing) @method('PUT') @endif

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card form-section tone-indigo">
                    <div class="form-section-head">
                        <span class="form-section-icon"><i class="ti ti-building"></i></span>
                        <div>
                            <h5 class="form-section-title">Account</h5>
                            <p class="form-section-sub">The company or organization this account represents.</p>
                        </div>
                    </div>
                    <div class="form-section-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $account->name) }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="industry">Industry</label>
                                <input type="text" class="form-control @error('industry') is-invalid @enderror" id="industry" name="industry" value="{{ old('industry', $account->industry) }}" placeholder="e.g. Manufacturing">
                                @error('industry')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="website">Website</label>
                                <input type="text" class="form-control @error('website') is-invalid @enderror" id="website" name="website" value="{{ old('website', $account->website) }}" placeholder="https://example.com">
                                @error('website')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="phone">Phone</label>
                                <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $account->phone) }}">
                                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="email">Email</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $account->email) }}">
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="branch_id">Branch</label>
                                <select class="form-select" id="branch_id" name="branch_id">
                                    <option value="">— Company-wide (no branch) —</option>
                                    @foreach ($branches as $id => $name)
                                        <option value="{{ $id }}" @selected((string) old('branch_id', $account->branch_id) === (string) $id)>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="parent_account_id">Parent account</label>
                                <select class="form-select @error('parent_account_id') is-invalid @enderror" id="parent_account_id" name="parent_account_id">
                                    <option value="">— None (top level) —</option>
                                    @foreach ($parents as $id => $name)
                                        <option value="{{ $id }}" @selected((string) old('parent_account_id', $account->parent_account_id) === (string) $id)>{{ $name }}</option>
                                    @endforeach
                                </select>
                                @error('parent_account_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="address">Address</label>
                                <textarea name="address" id="address" rows="2" class="form-control @error('address') is-invalid @enderror">{{ old('address', $account->address) }}</textarea>
                                @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="billing_address">Billing address</label>
                                <textarea name="billing_address" id="billing_address" rows="2" class="form-control">{{ old('billing_address', $account->billing_address) }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="shipping_address">Shipping address</label>
                                <textarea name="shipping_address" id="shipping_address" rows="2" class="form-control">{{ old('shipping_address', $account->shipping_address) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card form-section tone-violet">
                    <div class="form-section-head form-section-head-compact">
                        <span class="form-section-icon form-section-icon-sm"><i class="ti ti-tag"></i></span>
                        <h5 class="form-section-title">Ownership &amp; segment</h5>
                    </div>
                    <div class="form-section-body">
                        <label class="form-label" for="owner_employee_id">Account owner</label>
                        <select class="form-select @error('owner_employee_id') is-invalid @enderror" id="owner_employee_id" name="owner_employee_id">
                            <option value="">— Unassigned —</option>
                            @foreach ($employees as $id => $name)
                                <option value="{{ $id }}" @selected((string) old('owner_employee_id', $account->owner_employee_id) === (string) $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('owner_employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror

                        <label class="form-label mt-3" for="customer_segment_id">Customer segment</label>
                        <select class="form-select @error('customer_segment_id') is-invalid @enderror" id="customer_segment_id" name="customer_segment_id">
                            <option value="">— None —</option>
                            @foreach ($segments as $id => $name)
                                <option value="{{ $id }}" @selected((string) old('customer_segment_id', $account->customer_segment_id) === (string) $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('customer_segment_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                @if ($isEditing)
                    <div class="card form-section tone-teal">
                        <div class="form-section-head form-section-head-compact">
                            <span class="form-section-icon form-section-icon-sm"><i class="ti ti-users"></i></span>
                            <h5 class="form-section-title">Shared with</h5>
                        </div>
                        <div class="form-section-body">
                            @forelse ($shares as $share)
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="font-13">{{ $share->employee?->name ?? $share->team?->name }} <span class="tag tone-slate font-11">{{ \App\Models\Crm\AccountShare::ACCESS_LEVELS[$share->access_level] ?? $share->access_level }}</span></span>
                                    <form action="{{ route('admin.crm.accounts.unshare', [$account, $share]) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-icon btn-icon-danger" style="width:22px;height:22px;" title="Remove"><i class="ti ti-x font-12"></i></button>
                                    </form>
                                </div>
                            @empty
                                <p class="font-13 text-muted mb-2">Not shared beyond the owner yet.</p>
                            @endforelse
                            <hr class="my-2">
                            <form action="{{ route('admin.crm.accounts.share', $account) }}" method="POST">
                                @csrf
                                <select name="employee_id" class="form-select form-select-sm mb-2">
                                    <option value="">Share with employee…</option>
                                    @foreach ($employees as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                                <select name="team_id" class="form-select form-select-sm mb-2">
                                    <option value="">…or with a team</option>
                                    @foreach ($teams as $team)
                                        <option value="{{ $team->id }}">{{ $team->name }}</option>
                                    @endforeach
                                </select>
                                <select name="access_level" class="form-select form-select-sm mb-2">
                                    @foreach (\App\Models\Crm\AccountShare::ACCESS_LEVELS as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-light btn-sm w-100">Share</button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="record-footer">
            <a href="{{ $cancelUrl }}" class="btn btn-light px-4">Cancel</a>
            <button type="submit" class="btn btn-de-primary text-light px-4">
                <i class="ti ti-check font-14 me-1"></i> {{ $isEditing ? 'Save changes' : 'Create account' }}
            </button>
        </div>
    </form>
@endsection
