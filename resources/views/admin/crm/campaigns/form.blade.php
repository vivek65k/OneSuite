@extends('layouts.admin')

@php
    $isEditing = $campaign->exists;
@endphp

@section('title', $isEditing ? 'Edit Campaign' : 'New Campaign')

@section('content')
    <x-page-title :title="$isEditing ? 'Edit Campaign' : 'New Campaign'" :breadcrumbs="['Campaigns' => route('admin.crm.campaigns.index')]" />

    <div class="card form-section tone-indigo" style="max-width: 760px;">
        <div class="form-section-head">
            <span class="form-section-icon"><i class="ti ti-speakerphone"></i></span>
            <div>
                <h5 class="form-section-title">{{ $isEditing ? 'Edit campaign' : 'New campaign' }}</h5>
                <p class="form-section-sub">Set up the campaign details - add members and send once it's ready.</p>
            </div>
        </div>
        <div class="form-section-body">
            <form method="POST" action="{{ $isEditing ? route('admin.crm.campaigns.update', $campaign) : route('admin.crm.campaigns.store') }}">
                @csrf
                @if ($isEditing) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $campaign->name) }}" required autofocus>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Type <span class="text-danger">*</span></label>
                        <select name="type" class="form-select">
                            @foreach ($types = \App\Models\Crm\Campaign::TYPES as $key => $label)
                                <option value="{{ $key }}" @selected(old('type', $campaign->type) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            @foreach (\App\Models\Crm\Campaign::STATUSES as $key => $label)
                                <option value="{{ $key }}" @selected(old('status', $campaign->status ?? 'draft') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Subject / Headline</label>
                        <input type="text" class="form-control" name="subject" value="{{ old('subject', $campaign->subject) }}" placeholder="Email subject line or SMS headline">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" class="form-control" name="start_date" value="{{ old('start_date', $campaign->start_date?->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">End Date</label>
                        <input type="date" class="form-control @error('end_date') is-invalid @enderror" name="end_date" value="{{ old('end_date', $campaign->end_date?->format('Y-m-d')) }}">
                        @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Message Body</label>
                        <textarea class="form-control" name="message_body" rows="4" placeholder="Email / SMS / WhatsApp message content">{{ old('message_body', $campaign->message_body) }}</textarea>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Budget</label>
                        <input type="number" step="0.01" min="0" class="form-control" name="budget" value="{{ old('budget', $campaign->budget) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Actual Cost</label>
                        <input type="number" step="0.01" min="0" class="form-control" name="actual_cost" value="{{ old('actual_cost', $campaign->actual_cost) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Owner</label>
                        <select name="owner_employee_id" class="form-select">
                            <option value="">Unassigned</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}" @selected(old('owner_employee_id', $campaign->owner_employee_id) == $employee->id)>{{ $employee->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="2">{{ old('description', $campaign->description) }}</textarea>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-de-primary text-light px-4">{{ $isEditing ? 'Save changes' : 'Create campaign' }}</button>
                    <a href="{{ $isEditing ? route('admin.crm.campaigns.show', $campaign) : route('admin.crm.campaigns.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
