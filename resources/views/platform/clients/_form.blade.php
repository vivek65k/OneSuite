@php
    $isEdit = $client->exists;
    $allAppKeys = array_keys($apps);
    $selectedAppKeys = old('app_access', $isEdit ? ($client->app_access ?? $allAppKeys) : $allAppKeys);
    $limitMode = old('user_limit_mode', $client->max_users === null ? 'unlimited' : 'limited');
    $activeUsers = $isEdit ? $client->activeUserCount() : 0;
@endphp

<div class="card">
    <div class="card-header">
        <h4 class="card-title">Client</h4>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label" for="name">Company name <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $client->name) }}" required autofocus>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" for="email">Contact email</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $client->email) }}">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" for="phone">Contact phone</label>
                <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone', $client->phone) }}">
            </div>
        </div>
    </div>
</div>

@unless ($isEdit)
    <div class="card">
        <div class="card-header">
            <h4 class="card-title">First Admin User</h4>
        </div>
        <div class="card-body">
            <p class="text-muted font-13 mb-3">This account signs in as this client's own super admin - they'll manage their own users and roles from here on. We'll email them a secure link to set their own password; nobody here needs to choose or know it.</p>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="admin_name">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('admin_name') is-invalid @enderror" id="admin_name" name="admin_name" value="{{ old('admin_name') }}" required>
                    @error('admin_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="admin_email">Email <span class="text-danger">*</span></label>
                    <input type="email" class="form-control @error('admin_email') is-invalid @enderror" id="admin_email" name="admin_email" value="{{ old('admin_email') }}" required>
                    @error('admin_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>
@endunless

<div class="card">
    <div class="card-header">
        <h4 class="card-title">App Access</h4>
    </div>
    <div class="card-body">
        <p class="text-muted font-13 mb-3">Which OneSuite apps this client has been given. Every user at this client is further limited by this, no matter their own individual access.</p>

        <div class="row">
            @foreach ($apps as $key => $app)
                <div class="col-sm-6 col-lg-4 mb-3">
                    <label class="app-access-tile d-flex align-items-start" for="app-access-{{ $key }}" data-app-access-tile>
                        <input class="form-check-input mt-1 me-2" type="checkbox" name="app_access[]" value="{{ $key }}" id="app-access-{{ $key }}" data-app-access-checkbox @checked(in_array($key, $selectedAppKeys, true))>
                        <span class="avatar-box avatar-sm tile-icon rounded me-2 flex-shrink-0" data-tile-color="{{ $key }}">
                            <span class="avatar-title bg-transparent"><i class="{{ $app['icon'] }} font-16"></i></span>
                        </span>
                        <span class="flex-grow-1">
                            <span class="d-block fw-semibold font-13 text-body">
                                {{ $app['label'] }}
                                @unless ($app['built'])
                                    <span class="badge bg-soft-secondary font-10 ms-1">Coming soon</span>
                                @endunless
                            </span>
                            <span class="d-block text-muted font-11">{{ $app['description'] }}</span>
                        </span>
                    </label>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h4 class="card-title">User Limit</h4>
    </div>
    <div class="card-body">
        <p class="text-muted font-13 mb-3">
            How many active logins this client may have. Deactivated users don't count, so the client can free a seat by deactivating someone who left.
            @if ($isEdit)
                <br>Currently <strong>{{ $activeUsers }}</strong> active {{ Str::plural('user', $activeUsers) }}.
            @endif
        </p>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="limit-option" for="limit-unlimited" data-limit-option>
                    <input class="form-check-input" type="radio" name="user_limit_mode" id="limit-unlimited" value="unlimited" data-limit-mode @checked($limitMode === 'unlimited')>
                    <span class="limit-option-icon"><i class="ti ti-infinity"></i></span>
                    <span>
                        <span class="d-block fw-semibold font-13 text-body">No limit</span>
                        <span class="d-block text-muted font-12">The client can add as many users as they like.</span>
                    </span>
                </label>
            </div>
            <div class="col-md-6">
                <label class="limit-option" for="limit-limited" data-limit-option>
                    <input class="form-check-input" type="radio" name="user_limit_mode" id="limit-limited" value="limited" data-limit-mode @checked($limitMode === 'limited')>
                    <span class="limit-option-icon"><i class="ti ti-users"></i></span>
                    <span class="flex-grow-1">
                        <span class="d-block fw-semibold font-13 text-body">Limited</span>
                        <span class="d-flex align-items-center gap-2 mt-1">
                            <input type="number" min="{{ max(1, $activeUsers) }}" max="100000" class="form-control form-control-sm limit-input @error('max_users') is-invalid @enderror" name="max_users" id="max_users" value="{{ old('max_users', $client->max_users) }}" placeholder="e.g. 10" data-limit-input>
                            <span class="text-muted font-12 text-nowrap">active users</span>
                        </span>
                    </span>
                </label>
            </div>
        </div>
        @error('max_users')<div class="text-danger font-12 mt-2">{{ $message }}</div>@enderror
    </div>
</div>

<div class="mb-4">
    <button type="submit" class="btn btn-de-primary text-light px-4">Save</button>
    <a href="{{ route('platform.clients.index') }}" class="btn btn-de-danger text-light px-4">Cancel</a>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-app-access-checkbox]').forEach(function (checkbox) {
                function sync() {
                    checkbox.closest('[data-app-access-tile]').classList.toggle('is-checked', checkbox.checked);
                }
                checkbox.addEventListener('change', sync);
                sync();
            });

            var limitModes = document.querySelectorAll('[data-limit-mode]');
            var limitInput = document.querySelector('[data-limit-input]');

            function syncLimit(focus) {
                var limited = document.getElementById('limit-limited').checked;
                limitModes.forEach(function (radio) {
                    radio.closest('[data-limit-option]').classList.toggle('is-checked', radio.checked);
                });
                limitInput.disabled = !limited;
                limitInput.required = limited;
                if (limited && focus) limitInput.focus();
            }

            limitModes.forEach(function (radio) {
                radio.addEventListener('change', function () { syncLimit(true); });
            });
            // Typing a number means "Limited" - select it for them.
            limitInput.addEventListener('focus', function () {
                if (!document.getElementById('limit-limited').checked) {
                    document.getElementById('limit-limited').checked = true;
                    syncLimit(false);
                }
            });
            syncLimit(false);
        });
    </script>
@endpush
