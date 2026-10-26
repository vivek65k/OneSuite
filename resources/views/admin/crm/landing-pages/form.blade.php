@extends('layouts.admin')

@php
    $isEditing = $page->exists;
@endphp

@section('title', $isEditing ? 'Edit Landing Page' : 'New Landing Page')

@section('content')
    <x-page-title :title="$isEditing ? 'Edit Landing Page' : 'New Landing Page'" :breadcrumbs="['Landing Pages' => route('admin.crm.landing-pages.index')]" />

    <div class="card form-section tone-indigo" style="max-width: 760px;">
        <div class="form-section-head">
            <span class="form-section-icon"><i class="ti ti-browser"></i></span>
            <div>
                <h5 class="form-section-title">{{ $isEditing ? 'Edit page' : 'New page' }}</h5>
                <p class="form-section-sub">Publish to get a public link you can share in campaigns and ads.</p>
            </div>
        </div>
        <div class="form-section-body">
            <form method="POST" action="{{ $isEditing ? route('admin.crm.landing-pages.update', $page) : route('admin.crm.landing-pages.store') }}">
                @csrf
                @if ($isEditing) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-7">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $page->name) }}" required autofocus>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            @foreach (\App\Models\Crm\LandingPage::STATUSES as $key => $label)
                                <option value="{{ $key }}" @selected(old('status', $page->status ?? 'draft') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Campaign</label>
                        <select name="campaign_id" class="form-select">
                            <option value="">None</option>
                            @foreach ($campaigns as $campaign)
                                <option value="{{ $campaign->id }}" @selected(old('campaign_id', $page->campaign_id) == $campaign->id)>{{ $campaign->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Headline</label>
                        <input type="text" class="form-control" name="headline" value="{{ old('headline', $page->headline) }}" placeholder="The big promise visitors see first">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Content</label>
                        <textarea class="form-control" name="content" rows="8" placeholder="Page body (plain text or HTML)">{{ old('content', $page->content) }}</textarea>
                    </div>
                    @if ($isEditing && $page->status === 'published')
                        <div class="col-md-12">
                            <div class="alert alert-info mb-0">Live at: <a href="{{ route('crm.public.landing-pages.show', $page->slug) }}" target="_blank">{{ route('crm.public.landing-pages.show', $page->slug) }}</a></div>
                        </div>
                    @endif
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-de-primary text-light px-4">{{ $isEditing ? 'Save changes' : 'Create page' }}</button>
                    <a href="{{ route('admin.crm.landing-pages.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
