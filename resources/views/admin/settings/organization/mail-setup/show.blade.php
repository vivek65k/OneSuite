@extends('layouts.admin')

@section('title', 'Mail Setup')

@section('content')
    @php
        $smtpErrors = $errors->getBag('smtp');
        $smtp = $account?->isSmtp() ? ($account->settings ?? []) : [];
        // Shown straight away when it's what they use, or when their save just failed.
        $smtpOpen = $account?->isSmtp() || $smtpErrors->any();
        $reconnectProvider = $account && ! $account->isSmtp() ? ($providers[$account->provider] ?? null) : null;
    @endphp

    <x-page-title title="Mail Setup" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>{{ session('error') }}</div></div>
    @endif

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h4 class="card-title mb-0">Sending Account</h4>
            @if ($account && $account->needs_reconnect)
                <span class="badge badge-soft-danger">Needs reconnect</span>
            @elseif ($account)
                <span class="badge badge-soft-success">Connected</span>
            @else
                <span class="badge badge-soft-secondary">Not connected</span>
            @endif
        </div>
        <div class="card-body">
            @if ($account)
                <div class="d-flex align-items-center mb-3">
                    <span class="avatar-box avatar-md rounded bg-soft-primary me-3 flex-shrink-0">
                        <span class="avatar-title bg-transparent"><i class="{{ $account->providerIcon() }} font-20 text-primary"></i></span>
                    </span>
                    <div>
                        <div class="fw-semibold text-body">{{ $account->email }}</div>
                        <div class="text-muted font-12">
                            {{ $account->providerLabel() }} &middot; connected {{ $account->updated_at->format('d M Y') }}@if ($account->connectedBy) by {{ $account->connectedBy->name }}@endif
                        </div>
                    </div>
                </div>

                @if ($account->needs_reconnect)
                    <p class="text-danger font-13 mb-3">{{ $account->providerLabel() }} no longer accepts this connection (access was removed, or the account password changed). Emails are not being sent from this account until you connect it again.</p>
                @else
                    <p class="text-muted font-13 mb-3">Emails from {{ $company->name }} - invitations and notifications - are sent from this account.</p>
                @endif

                <div class="d-flex flex-wrap gap-2">
                    @unless ($account->needs_reconnect)
                        <form method="POST" action="{{ route('admin.mail-setup.test') }}">
                            @csrf
                            <button type="submit" class="btn btn-de-primary text-light"><i class="ti ti-send font-14 me-1"></i> Send test email</button>
                        </form>
                    @endunless
                    @if ($reconnectProvider && $account->needs_reconnect)
                        <form method="POST" action="{{ route('admin.mail-setup.connect', $account->provider) }}">
                            @csrf
                            <button type="submit" class="btn btn-de-primary text-light"><i class="ti ti-refresh font-14 me-1"></i> Reconnect</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('admin.mail-setup.disconnect') }}" onsubmit="return confirm('Disconnect this mail account? Emails will stop being sent from it.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-de-danger text-light"><i class="ti ti-plug-off font-14 me-1"></i> Disconnect</button>
                    </form>
                </div>
            @else
                <p class="text-muted font-13 mb-0">No mail account is connected yet. Choose how {{ $company->name }} sends email below.</p>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h4 class="card-title">{{ $account ? 'Switch Account' : 'Connect an Account' }}</h4>
        </div>
        <div class="card-body">
            @if ($providers)
                <p class="text-muted font-13 mb-3">
                    Sign in with the account your emails should come from. There is no server, port or password to enter -
                    you only allow {{ config('app.name') }} to <strong>send email on your behalf</strong>. We can never read your mailbox.
                </p>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    @foreach ($providers as $key => $provider)
                        <form method="POST" action="{{ route('admin.mail-setup.connect', $key) }}">
                            @csrf
                            <button type="submit" class="btn btn-de-primary text-light px-3"><i class="{{ $provider->icon() }} font-14 me-1"></i> Connect {{ $provider->label() }} account</button>
                        </form>
                    @endforeach
                </div>
                <div class="text-muted font-12">
                    @isset($providers['google'])<div><strong>Google</strong> - Gmail and Google Workspace addresses.</div>@endisset
                    @isset($providers['microsoft'])<div><strong>Microsoft</strong> - Microsoft 365, Outlook.com and Hotmail addresses.</div>@endisset
                </div>
                <hr>
            @endif

            <a class="fw-semibold font-13 text-body text-decoration-none" data-bs-toggle="collapse" href="#smtp-form" role="button" aria-expanded="{{ $smtpOpen ? 'true' : 'false' }}" aria-controls="smtp-form">
                <i class="ti ti-server me-1"></i> Using another mail provider? Connect with SMTP <i class="mdi mdi-chevron-down"></i>
            </a>

            <div class="collapse {{ $smtpOpen ? 'show' : '' }} mt-3" id="smtp-form">
                <p class="text-muted font-13 mb-3">For Zoho, Yahoo, your hosting company's mail or any other provider. Your provider's help pages list these as "SMTP settings". We check the login before saving.</p>

                <form method="POST" action="{{ route('admin.mail-setup.smtp') }}">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="smtp-email">Send from (email address) <span class="text-danger">*</span></label>
                            <input type="email" class="form-control @if ($smtpErrors->has('email')) is-invalid @endif" id="smtp-email" name="email" value="{{ old('email', $account?->isSmtp() ? $account->email : '') }}" placeholder="hr@yourcompany.com" required>
                            @if ($smtpErrors->has('email'))<div class="invalid-feedback">{{ $smtpErrors->first('email') }}</div>@endif
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label" for="smtp-host">SMTP server <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @if ($smtpErrors->has('host')) is-invalid @endif" id="smtp-host" name="host" value="{{ old('host', $smtp['host'] ?? '') }}" placeholder="smtp.yourprovider.com" autocomplete="off" required>
                            @if ($smtpErrors->has('host'))<div class="invalid-feedback">{{ $smtpErrors->first('host') }}</div>@endif
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label" for="smtp-port">Port <span class="text-danger">*</span></label>
                            <select class="form-select @if ($smtpErrors->has('port')) is-invalid @endif" id="smtp-port" name="port" required>
                                @foreach ([587 => '587 (recommended)', 465 => '465 (SSL)', 2525 => '2525', 25 => '25'] as $port => $label)
                                    <option value="{{ $port }}" @selected((int) old('port', $smtp['port'] ?? 587) === $port)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @if ($smtpErrors->has('port'))<div class="invalid-feedback">{{ $smtpErrors->first('port') }}</div>@endif
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="smtp-username">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @if ($smtpErrors->has('username')) is-invalid @endif" id="smtp-username" name="username" value="{{ old('username', $smtp['username'] ?? '') }}" placeholder="Usually the full email address" autocomplete="off" required>
                            @if ($smtpErrors->has('username'))<div class="invalid-feedback">{{ $smtpErrors->first('username') }}</div>@endif
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="smtp-password">Password @unless ($smtp)<span class="text-danger">*</span>@endunless</label>
                            <input type="password" class="form-control @if ($smtpErrors->has('password')) is-invalid @endif" id="smtp-password" name="password" placeholder="{{ $smtp ? 'Saved - leave blank to keep it' : '' }}" autocomplete="new-password" @required(! $smtp)>
                            <small class="form-text text-muted">Stored encrypted. If your provider offers an "app password", use that rather than your normal one.</small>
                            @if ($smtpErrors->has('password'))<div class="invalid-feedback">{{ $smtpErrors->first('password') }}</div>@endif
                        </div>
                    </div>

                    <button type="submit" class="btn btn-de-primary text-light px-4">{{ $smtp ? 'Save' : 'Connect' }}</button>
                </form>
            </div>
        </div>
    </div>
@endsection
