@extends('layouts.admin')

@php
    $isEditing = $meeting->exists;
    $cancelUrl = route('admin.crm.meetings.index');
@endphp

@section('title', $isEditing ? 'Edit Meeting' : 'Schedule Meeting')

@section('content')
    <x-page-title :title="$isEditing ? 'Edit Meeting' : 'Schedule Meeting'" :breadcrumbs="['Meetings' => $cancelUrl]" />

    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <div class="card form-section tone-indigo" style="max-width: 720px;">
        <div class="form-section-head">
            <span class="form-section-icon"><i class="ti ti-video"></i></span>
            <div><h5 class="form-section-title">{{ $isEditing ? 'Edit meeting' : 'New meeting' }}</h5></div>
        </div>
        <div class="form-section-body">
            <form method="POST" action="{{ $isEditing ? route('admin.crm.meetings.update', $meeting) : route('admin.crm.meetings.store') }}">
                @csrf
                @if ($isEditing) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="subject">Subject <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="subject" name="subject" value="{{ old('subject', $meeting->subject) }}" required autofocus>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="start_at">Start <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control" id="start_at" name="start_at" value="{{ old('start_at', optional($meeting->start_at)->format('Y-m-d\TH:i')) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="end_at">End</label>
                        <input type="datetime-local" class="form-control" id="end_at" name="end_at" value="{{ old('end_at', optional($meeting->end_at)->format('Y-m-d\TH:i')) }}">
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
                                <option value="{{ $id }}" @selected((string) old('owner_employee_id', $meeting->owner_employee_id) === (string) $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="description">Agenda / Notes</label>
                        <textarea name="description" id="description" rows="3" class="form-control">{{ old('description', $meeting->description) }}</textarea>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-de-primary text-light px-4">{{ $isEditing ? 'Save changes' : 'Schedule meeting' }}</button>
                    <a href="{{ $cancelUrl }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
