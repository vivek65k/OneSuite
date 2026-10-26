@extends('layouts.admin')

@php
    $isEditing = $competitor->exists;
@endphp

@section('title', $isEditing ? 'Edit Competitor' : 'Add Competitor')

@section('content')
    <x-page-title :title="$isEditing ? 'Edit Competitor' : 'Add Competitor'" :breadcrumbs="['Competitors' => route('admin.crm.competitors.index')]" />

    <div class="card form-section tone-indigo" style="max-width: 640px;">
        <div class="form-section-head">
            <span class="form-section-icon"><i class="ti ti-shield"></i></span>
            <div>
                <h5 class="form-section-title">{{ $isEditing ? 'Edit competitor' : 'New competitor' }}</h5>
                <p class="form-section-sub">Track who you're up against, so win/loss patterns surface over time.</p>
            </div>
        </div>
        <div class="form-section-body">
            <form method="POST" action="{{ $isEditing ? route('admin.crm.competitors.update', $competitor) : route('admin.crm.competitors.store') }}">
                @csrf
                @if ($isEditing) @method('PUT') @endif

                <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $competitor->name) }}" required autofocus>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror

                <label class="form-label mt-3" for="website">Website</label>
                <input type="text" class="form-control @error('website') is-invalid @enderror" id="website" name="website" value="{{ old('website', $competitor->website) }}" placeholder="https://example.com">
                @error('website')<div class="invalid-feedback">{{ $message }}</div>@enderror

                <label class="form-label mt-3" for="strengths">Strengths</label>
                <textarea name="strengths" id="strengths" rows="2" class="form-control @error('strengths') is-invalid @enderror">{{ old('strengths', $competitor->strengths) }}</textarea>
                @error('strengths')<div class="invalid-feedback">{{ $message }}</div>@enderror

                <label class="form-label mt-3" for="weaknesses">Weaknesses</label>
                <textarea name="weaknesses" id="weaknesses" rows="2" class="form-control @error('weaknesses') is-invalid @enderror">{{ old('weaknesses', $competitor->weaknesses) }}</textarea>
                @error('weaknesses')<div class="invalid-feedback">{{ $message }}</div>@enderror

                <div class="mt-3">
                    <button type="submit" class="btn btn-de-primary text-light px-4">{{ $isEditing ? 'Save changes' : 'Create competitor' }}</button>
                    <a href="{{ route('admin.crm.competitors.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
