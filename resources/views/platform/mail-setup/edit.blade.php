@extends('layouts.platform')

@section('title', 'Mail Setup')

@section('content')
    <x-page-title title="Mail Setup" />

    <p class="text-muted font-13">
        Register one OAuth app per provider here. Each client then clicks <strong>Connect</strong> in their own Settings &gt; Mail Setup and signs in -
        they never enter a host, port, username or password, and never see these credentials. Clients on any other mail provider can still connect with SMTP; that needs no setup here.
    </p>

    @foreach ($providers as $key => $provider)
        @php
            $bag = $errors->getBag($key);
            $hasSecret = filled($provider->clientSecret());
            // old() is shared by every form on the page - only refill the one that was submitted.
            $submitted = $bag->any();
        @endphp

        <form method="POST" action="{{ route('platform.mail-setup.update', $key) }}">
            @csrf
            @method('PUT')

            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h4 class="card-title mb-0"><i class="{{ $provider->icon() }} me-1"></i> {{ $provider->label() }} OAuth2</h4>
                    @if ($provider->isConfigured())
                        <span class="badge badge-soft-success">Configured</span>
                    @else
                        <span class="badge badge-soft-warning">Not configured</span>
                    @endif
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="{{ $key }}-client-id">{{ $key === 'microsoft' ? 'Application (client) ID' : 'Client ID' }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @if ($bag->has('client_id')) is-invalid @endif" id="{{ $key }}-client-id" name="client_id" value="{{ $submitted ? old('client_id') : $provider->clientId() }}" autocomplete="off" required>
                            @if ($bag->has('client_id'))<div class="invalid-feedback">{{ $bag->first('client_id') }}</div>@endif
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="{{ $key }}-client-secret">{{ $key === 'microsoft' ? 'Client secret value' : 'Client secret' }} @unless ($hasSecret)<span class="text-danger">*</span>@endunless</label>
                            <input type="password" class="form-control @if ($bag->has('client_secret')) is-invalid @endif" id="{{ $key }}-client-secret" name="client_secret" placeholder="{{ $hasSecret ? 'Saved - leave blank to keep it' : '' }}" autocomplete="new-password" @required(! $hasSecret)>
                            <small class="form-text text-muted">Stored encrypted and never shown again.</small>
                            @if ($bag->has('client_secret'))<div class="invalid-feedback">{{ $bag->first('client_secret') }}</div>@endif
                        </div>
                    </div>

                    <label class="form-label" for="{{ $key }}-redirect-uri">Redirect URI</label>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control font-13" id="{{ $key }}-redirect-uri" value="{{ $provider->redirectUri() }}" readonly onclick="this.select()">
                        <button class="btn btn-light" type="button" data-copy-target="{{ $key }}-redirect-uri"><i class="ti ti-copy font-14 me-1"></i> Copy</button>
                    </div>

                    <div class="fw-semibold font-13 mb-1">Where to get these</div>
                    <ol class="text-muted font-13 ps-3 mb-3">
                        @if ($key === 'google')
                            <li>In the <a href="https://console.cloud.google.com/" target="_blank" rel="noopener">Google Cloud console</a>, create (or pick) a project and enable the <strong>Gmail API</strong>.</li>
                            <li>Set up the <strong>OAuth consent screen</strong> and add the scope <code>https://www.googleapis.com/auth/gmail.send</code>.</li>
                            <li>Under <strong>Credentials</strong>, create an <strong>OAuth client ID</strong> of type <strong>Web application</strong> and add the redirect URI above.</li>
                        @elseif ($key === 'microsoft')
                            <li>In the <a href="https://portal.azure.com/" target="_blank" rel="noopener">Azure portal</a>, open <strong>Microsoft Entra ID &gt; App registrations &gt; New registration</strong>.</li>
                            <li>For supported account types choose <strong>Accounts in any organizational directory and personal Microsoft accounts</strong> (multi-tenant) - each client is a different organisation.</li>
                            <li>Add the redirect URI above as a <strong>Web</strong> platform.</li>
                            <li>Under <strong>API permissions</strong>, add Microsoft Graph delegated permissions <code>Mail.Send</code>, <code>User.Read</code> and <code>offline_access</code>.</li>
                            <li>Under <strong>Certificates &amp; secrets</strong>, create a client secret and copy its <strong>Value</strong> (not the Secret ID). Note its expiry date - sending stops when it expires.</li>
                        @endif
                    </ol>

                    <button type="submit" class="btn btn-de-primary text-light px-4">Save {{ $provider->label() }}</button>
                </div>
            </div>
        </form>
    @endforeach

    <div class="card">
        <div class="card-header">
            <h4 class="card-title">Connected Clients</h4>
        </div>
        <div class="card-body">
            @if ($accounts->isEmpty())
                <p class="text-muted font-13 mb-0">No client has connected a mail account yet.</p>
            @else
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Client</th>
                                <th>Provider</th>
                                <th>Sends from</th>
                                <th>Connected</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($accounts as $account)
                                <tr>
                                    <td>{{ $account->company?->name }}</td>
                                    <td><i class="{{ $account->providerIcon() }} me-1"></i> {{ $account->providerLabel() }}</td>
                                    <td>{{ $account->email }}</td>
                                    <td>{{ $account->updated_at->format('d M Y') }}</td>
                                    <td>
                                        @if ($account->needs_reconnect)
                                            <span class="badge badge-soft-danger">Needs reconnect</span>
                                        @else
                                            <span class="badge badge-soft-success">Connected</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-copy-target]').forEach(function (button) {
            button.addEventListener('click', function () {
                var field = document.getElementById(button.dataset.copyTarget);
                field.select();
                navigator.clipboard.writeText(field.value).then(function () {
                    var original = button.innerHTML;
                    button.innerHTML = '<i class="ti ti-check font-14 me-1"></i> Copied';
                    setTimeout(function () { button.innerHTML = original; }, 1500);
                });
            });
        });
    </script>
@endpush
