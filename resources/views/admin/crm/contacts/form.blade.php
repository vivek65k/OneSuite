@extends('layouts.admin')

@php
    $isEditing = $contact->exists;
    $cancelUrl = route('admin.crm.contacts.index');
@endphp

@section('title', $isEditing ? 'Edit Contact' : 'Add Contact')

@section('content')
    <x-page-title :title="$isEditing ? 'Edit Contact' : 'Add Contact'" :breadcrumbs="['Contacts' => $cancelUrl]" />

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

    <form method="POST" action="{{ $isEditing ? route('admin.crm.contacts.update', $contact) : route('admin.crm.contacts.store') }}">
        @csrf
        @if ($isEditing) @method('PUT') @endif

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card form-section tone-teal">
                    <div class="form-section-head">
                        <span class="form-section-icon"><i class="ti ti-user"></i></span>
                        <div>
                            <h5 class="form-section-title">Contact</h5>
                            <p class="form-section-sub">Who they are and how to reach them.</p>
                        </div>
                    </div>
                    <div class="form-section-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="name">Full name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $contact->name) }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="job_title">Job title</label>
                                <input type="text" class="form-control @error('job_title') is-invalid @enderror" id="job_title" name="job_title" value="{{ old('job_title', $contact->job_title) }}" placeholder="e.g. Procurement Manager">
                                @error('job_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="email">Email</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $contact->email) }}">
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="phone">Phone</label>
                                <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $contact->phone) }}">
                                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="account_id">Account</label>
                                <select class="form-select @error('account_id') is-invalid @enderror" id="account_id" name="account_id">
                                    <option value="">— None —</option>
                                    @foreach ($accounts as $id => $name)
                                        <option value="{{ $id }}" @selected((string) old('account_id', $contact->account_id) === (string) $id)>{{ $name }}</option>
                                    @endforeach
                                </select>
                                @error('account_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="company_name">Company (if no account)</label>
                                <input type="text" class="form-control @error('company_name') is-invalid @enderror" id="company_name" name="company_name" value="{{ old('company_name', $contact->company_name) }}">
                                @error('company_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                        <label class="form-label" for="owner_employee_id">Contact owner</label>
                        <select class="form-select @error('owner_employee_id') is-invalid @enderror" id="owner_employee_id" name="owner_employee_id">
                            <option value="">— Unassigned —</option>
                            @foreach ($employees as $id => $name)
                                <option value="{{ $id }}" @selected((string) old('owner_employee_id', $contact->owner_employee_id) === (string) $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('owner_employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror

                        <label class="form-label mt-3" for="customer_segment_id">Customer segment</label>
                        <select class="form-select @error('customer_segment_id') is-invalid @enderror" id="customer_segment_id" name="customer_segment_id">
                            <option value="">— None —</option>
                            @foreach ($segments as $id => $name)
                                <option value="{{ $id }}" @selected((string) old('customer_segment_id', $contact->customer_segment_id) === (string) $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('customer_segment_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="record-footer">
            <a href="{{ $cancelUrl }}" class="btn btn-light px-4">Cancel</a>
            <button type="submit" class="btn btn-de-primary text-light px-4">
                <i class="ti ti-check font-14 me-1"></i> {{ $isEditing ? 'Save changes' : 'Create contact' }}
            </button>
        </div>
    </form>
@endsection
