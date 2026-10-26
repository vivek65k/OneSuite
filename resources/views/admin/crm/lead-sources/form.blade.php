@extends('layouts.admin')

@php
    $isEditing = $source->exists;
@endphp

@section('title', $isEditing ? 'Edit Lead Source' : 'Add Lead Source')

@section('content')
    <x-page-title :title="$isEditing ? 'Edit Lead Source' : 'Add Lead Source'" :breadcrumbs="['Lead Sources' => route('admin.crm.lead-sources.index')]" />

    <div class="card form-section tone-indigo" style="max-width: 560px;">
        <div class="form-section-head">
            <span class="form-section-icon"><i class="ti ti-route"></i></span>
            <div>
                <h5 class="form-section-title">{{ $isEditing ? 'Edit source' : 'New source' }}</h5>
                <p class="form-section-sub">Where leads say they heard about you - used for reporting and filtering.</p>
            </div>
        </div>
        <div class="form-section-body">
            <form method="POST" action="{{ $isEditing ? route('admin.crm.lead-sources.update', $source) : route('admin.crm.lead-sources.store') }}">
                @csrf
                @if ($isEditing) @method('PUT') @endif

                <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $source->name) }}" placeholder="e.g. Website, Referral, LinkedIn" required autofocus>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror

                <div class="mt-3">
                    <button type="submit" class="btn btn-de-primary text-light px-4">{{ $isEditing ? 'Save changes' : 'Create source' }}</button>
                    <a href="{{ route('admin.crm.lead-sources.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
