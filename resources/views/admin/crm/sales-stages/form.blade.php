@extends('layouts.admin')

@php
    $isEditing = $stage->exists;
@endphp

@section('title', $isEditing ? 'Edit Stage' : 'Add Stage')

@section('content')
    <x-page-title :title="$isEditing ? 'Edit Stage' : 'Add Stage'" :breadcrumbs="['Sales Stages' => route('admin.crm.sales-stages.index')]" />

    <div class="card form-section tone-indigo" style="max-width: 560px;">
        <div class="form-section-head">
            <span class="form-section-icon"><i class="ti ti-stack-2"></i></span>
            <div>
                <h5 class="form-section-title">{{ $isEditing ? 'Edit stage' : 'New stage' }}</h5>
                <p class="form-section-sub">A step in your sales process, e.g. "Qualification", "Proposal Sent".</p>
            </div>
        </div>
        <div class="form-section-body">
            <form method="POST" action="{{ $isEditing ? route('admin.crm.sales-stages.update', $stage) : route('admin.crm.sales-stages.store') }}">
                @csrf
                @if ($isEditing) @method('PUT') @endif

                <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $stage->name) }}" required autofocus>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror

                <label class="form-label mt-3" for="win_probability">Default win probability (%)</label>
                <input type="number" min="0" max="100" class="form-control @error('win_probability') is-invalid @enderror" id="win_probability" name="win_probability" value="{{ old('win_probability', $stage->win_probability ?? 10) }}" required>
                @error('win_probability')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-hint"><i class="ti ti-info-circle"></i> Used to weight the pipeline/forecast when an opportunity doesn't override it.</div>

                <div class="form-check mt-3">
                    <input type="checkbox" class="form-check-input" id="is_won" name="is_won" value="1" @checked(old('is_won', $stage->is_won))>
                    <label class="form-check-label" for="is_won">This is the "Won" stage</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="is_lost" name="is_lost" value="1" @checked(old('is_lost', $stage->is_lost))>
                    <label class="form-check-label" for="is_lost">This is the "Lost" stage</label>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-de-primary text-light px-4">{{ $isEditing ? 'Save changes' : 'Create stage' }}</button>
                    <a href="{{ route('admin.crm.sales-stages.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
