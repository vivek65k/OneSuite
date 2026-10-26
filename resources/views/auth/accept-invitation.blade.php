@extends('layouts.guest')

@section('title', 'Set your password')

@section('content')
    <div class="auth-form-head">
        <h2 class="mb-1">Welcome{{ $invitedUser->company ? ' to '.$invitedUser->company->name : '' }}</h2>
        <p class="text-muted">Set a password for {{ $invitedUser->email }} to activate your account.</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ url()->full() }}">
        @csrf

        <div class="form-group mb-3">
            <label class="form-label" for="password">Password</label>
            <div class="input-icon-group">
                <i class="ti ti-lock input-icon"></i>
                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="At least 8 characters" required autofocus>
            </div>
            @error('password')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group mb-3">
            <label class="form-label" for="password_confirmation">Confirm password</label>
            <div class="input-icon-group">
                <i class="ti ti-lock input-icon"></i>
                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Re-enter password" required>
            </div>
        </div>

        <div class="d-grid">
            <button class="btn btn-de-primary auth-submit-btn text-light" type="submit">
                Set password &amp; sign in <i class="ti ti-arrow-right ms-1"></i>
            </button>
        </div>
    </form>
@endsection
