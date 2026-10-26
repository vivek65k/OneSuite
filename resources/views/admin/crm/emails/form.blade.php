@extends('layouts.admin')

@php
    $isEditing = $email->exists;
    $cancelUrl = route('admin.crm.emails.index');
@endphp

@section('title', $isEditing ? 'Edit Email' : 'Log Email')

@section('content')
    <x-page-title :title="$isEditing ? 'Edit Email' : 'Log Email'" :breadcrumbs="['Emails' => $cancelUrl]" />

    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <div class="card form-section tone-indigo" style="max-width: 720px;">
        <div class="form-section-head">
            <span class="form-section-icon"><i class="ti ti-mail"></i></span>
            <div><h5 class="form-section-title">{{ $isEditing ? 'Edit email' : 'Log an email' }}</h5></div>
        </div>
        <div class="form-section-body">
            <form method="POST" action="{{ $isEditing ? route('admin.crm.emails.update', $email) : route('admin.crm.emails.store') }}">
                @csrf
                @if ($isEditing) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="subject">Subject <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="subject" name="subject" value="{{ old('subject', $email->subject) }}" required autofocus>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="due_at">Date &amp; time <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control" id="due_at" name="due_at" value="{{ old('due_at', optional($email->due_at)->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i')) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="direction">Direction <span class="text-danger">*</span></label>
                        <select class="form-select" id="direction" name="direction" required>
                            <option value="outbound" @selected(old('direction', $email->direction ?? 'outbound') === 'outbound')>Sent</option>
                            <option value="inbound" @selected(old('direction', $email->direction) === 'inbound')>Received</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="relatable">Related to</label>
                        <select class="form-select" id="relatable" name="relatable">
                            <option value="">— None —</option>
                            @foreach ($relatable as $key => $label)
                                <option value="{{ $key }}" @selected(old('relatable', $currentRelatable ?? null) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="owner_employee_id">Owner</label>
                        <select class="form-select" id="owner_employee_id" name="owner_employee_id">
                            <option value="">— Unassigned —</option>
                            @foreach ($employees as $id => $name)
                                <option value="{{ $id }}" @selected((string) old('owner_employee_id', $email->owner_employee_id) === (string) $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="description">Body</label>
                        <textarea name="description" id="description" rows="6" class="form-control">{{ old('description', $email->description) }}</textarea>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-de-primary text-light px-4">{{ $isEditing ? 'Save changes' : 'Log email' }}</button>
                    <a href="{{ $cancelUrl }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
