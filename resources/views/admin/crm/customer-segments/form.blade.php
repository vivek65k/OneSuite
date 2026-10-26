@extends('layouts.admin')

@php
    $isEditing = $segment->exists;
@endphp

@section('title', $isEditing ? 'Edit Segment' : 'Add Segment')

@section('content')
    <x-page-title :title="$isEditing ? 'Edit Segment' : 'Add Segment'" :breadcrumbs="['Customer Segments' => route('admin.crm.customer-segments.index')]" />

    <div class="card form-section tone-indigo" style="max-width: 560px;">
        <div class="form-section-head">
            <span class="form-section-icon"><i class="ti ti-tag"></i></span>
            <div>
                <h5 class="form-section-title">{{ $isEditing ? 'Edit segment' : 'New segment' }}</h5>
                <p class="form-section-sub">Group accounts and contacts for targeted reporting - e.g. "Enterprise", "SMB", "VIP".</p>
            </div>
        </div>
        <div class="form-section-body">
            <form method="POST" action="{{ $isEditing ? route('admin.crm.customer-segments.update', $segment) : route('admin.crm.customer-segments.store') }}">
                @csrf
                @if ($isEditing) @method('PUT') @endif

                <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $segment->name) }}" required autofocus>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror

                <label class="form-label mt-3" for="description">Description</label>
                <textarea name="description" id="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description', $segment->description) }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror

                <div class="mt-3">
                    <button type="submit" class="btn btn-de-primary text-light px-4">{{ $isEditing ? 'Save changes' : 'Create segment' }}</button>
                    <a href="{{ route('admin.crm.customer-segments.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
