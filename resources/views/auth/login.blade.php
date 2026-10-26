@extends('layouts.guest')

@section('title', 'Sign in')

@section('content')
    <div class="auth-form-head">
        <h2 class="mb-1">Welcome back</h2>
        <p class="text-muted">Sign in to your account to continue.</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('login.store') }}">
        @csrf

        <div class="form-group mb-3">
            <label class="form-label" for="email">Email</label>
            <div class="input-icon-group">
                <i class="ti ti-mail input-icon"></i>
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" placeholder="you@company.com" required autofocus>
            </div>
            @error('email')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group mb-3">
            <label class="form-label" for="password">Password</label>
            <div class="input-icon-group">
                <i class="ti ti-lock input-icon"></i>
                <input type="password" class="form-control" name="password" id="password" placeholder="Enter password" required>
            </div>
        </div>

        <div class="form-group mb-3">
            <div class="form-check form-switch form-switch-success">
                <input class="form-check-input" type="checkbox" id="remember" name="remember">
                <label class="form-check-label" for="remember">Remember me</label>
            </div>
        </div>

        <div class="d-grid">
            <button class="btn btn-de-primary auth-submit-btn text-light" type="submit">
                Sign in <i class="ti ti-arrow-right ms-1"></i>
            </button>
        </div>
    </form>
@endsection
