@extends('layouts.admin')

@php
    $isEditing = $article->exists;
@endphp

@section('title', $isEditing ? 'Edit Article' : 'New Article')

@section('content')
    <x-page-title :title="$isEditing ? 'Edit Article' : 'New Article'" :breadcrumbs="['Knowledge Base' => route('admin.crm.knowledge-base.index')]" />

    <div class="card form-section tone-indigo" style="max-width: 760px;">
        <div class="form-section-head">
            <span class="form-section-icon"><i class="ti ti-book"></i></span>
            <div>
                <h5 class="form-section-title">{{ $isEditing ? 'Edit article' : 'New article' }}</h5>
                <p class="form-section-sub">Published articles help agents and customers self-serve answers.</p>
            </div>
        </div>
        <div class="form-section-body">
            <form method="POST" action="{{ $isEditing ? route('admin.crm.knowledge-base.update', $article) : route('admin.crm.knowledge-base.store') }}">
                @csrf
                @if ($isEditing) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" name="title" value="{{ old('title', $article->title) }}" required autofocus>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Category</label>
                        <input type="text" class="form-control" name="category" value="{{ old('category', $article->category) }}" placeholder="Billing, Setup, FAQ...">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Body</label>
                        <textarea class="form-control" name="body" rows="10">{{ old('body', $article->body) }}</textarea>
                    </div>
                    <div class="col-md-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" name="is_published" id="is_published" value="1" @checked(old('is_published', $article->is_published)))>
                            <label class="form-check-label" for="is_published">Published</label>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-de-primary text-light px-4">{{ $isEditing ? 'Save changes' : 'Create article' }}</button>
                    <a href="{{ route('admin.crm.knowledge-base.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
