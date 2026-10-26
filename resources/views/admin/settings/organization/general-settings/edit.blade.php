@extends('layouts.admin')

@section('title', 'General Settings')

@section('content')
    <x-page-title title="General Settings" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.general-settings.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Branding</h4>
            </div>
            <div class="card-body">
                <div class="form-group mb-3">
                    @if ($company->logo)
                        <img src="{{ asset('storage/'.$company->logo) }}" alt="" class="thumb-lg rounded me-2">
                    @endif
                    <label class="btn btn-de-primary btn-sm text-light">
                        Change Logo <input type="file" name="logo" accept="image/*" hidden>
                    </label>
                    @error('logo')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="name">App name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $company->name) }}" required>
                        <small class="form-text text-muted">Shown in the sidebar and browser tab title.</small>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="footer_text">Footer text</label>
                        <input type="text" class="form-control @error('footer_text') is-invalid @enderror" id="footer_text" name="footer_text" value="{{ old('footer_text', $company->footer_text) }}" placeholder="&copy; {{ date('Y') }} {{ $company->name }}">
                        <small class="form-text text-muted">Shown at the bottom of every admin page.</small>
                        @error('footer_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Login page</h4>
            </div>
            <div class="card-body">
                <div class="form-group row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="login_title">Title</label>
                        <input type="text" class="form-control @error('login_title') is-invalid @enderror" id="login_title" name="login_title" value="{{ old('login_title', $company->login_title) }}" placeholder="{{ $company->name }}">
                        @error('login_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="login_subtitle">Subtitle</label>
                        <input type="text" class="form-control @error('login_subtitle') is-invalid @enderror" id="login_subtitle" name="login_subtitle" value="{{ old('login_subtitle', $company->login_subtitle) }}" placeholder="Sign in to continue.">
                        @error('login_subtitle')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-4">
            <button type="submit" class="btn btn-de-primary text-light px-4">Save</button>
        </div>
    </form>
@endsection
