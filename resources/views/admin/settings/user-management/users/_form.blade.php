@php
    $isEdit = $user->exists;
    $allAppKeys = array_keys($apps);
    $selectedAppKeys = array_values(array_intersect(old('app_access', $isEdit ? ($user->app_access ?? $allAppKeys) : $allAppKeys), $allAppKeys));
    $selectedDefaultApp = old('default_app', $isEdit ? $user->default_app : null) ?: \App\Support\AppLauncher::preferredKey($selectedAppKeys);
    $selectedEmployeeId = (int) old('employee_id', $isEdit ? $user->employee?->id : null);
@endphp

@error('user_limit')
    <div class="alert alert-danger">{{ $message }}</div>
@enderror

<div class="card">
    <div class="card-header">
        <h4 class="card-title">Employee</h4>
    </div>
    <div class="card-body">
        <div class="row align-items-end">
            <div class="col-md-6 mb-2">
                <label class="form-label" for="employee_id">This login is for</label>
                <select class="form-select @error('employee_id') is-invalid @enderror" id="employee_id" name="employee_id" data-employee-picker @if ($isEdit) data-keep-email @endif>
                    <option value="">Not an employee (or link later)</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}" @selected($selectedEmployeeId === $employee->id)
                            data-employee="{{ json_encode([
                                'name' => $employee->name,
                                'email' => $employee->email,
                                'employee_code' => $employee->employee_code,
                                'phone' => $employee->phone,
                                'gender' => $employee->gender,
                                'date_of_birth' => $employee->date_of_birth?->format('Y-m-d'),
                                'designation' => $employee->designation,
                                'department' => $employee->department?->name,
                                'joining_date' => $employee->joining_date?->format('Y-m-d'),
                                'address' => $employee->address,
                                'city' => $employee->city,
                                'state' => $employee->state,
                                'country' => $employee->country,
                                'postal_code' => $employee->postal_code,
                                'branch_id' => $employee->branch_id,
                            ]) }}">{{ $employee->name }}{{ $employee->employee_code ? ' ('.$employee->employee_code.')' : '' }}{{ $employee->designation ? ' - '.$employee->designation : '' }}</option>
                    @endforeach
                </select>
                @error('employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 mb-2">
                <p class="text-success font-13 mb-2 d-none" data-employee-filled><i class="ti ti-circle-check me-1"></i><span></span></p>
            </div>
        </div>
        <small class="form-text text-muted">
            Choose an employee and their name, {{ $isEdit ? '' : 'email, ' }}contact, employment and address details fill in below - you can still change any of them.
            @if ($isEdit) The login email is not changed, so they keep signing in the same way. @endif
            The login is linked to that employee's record, which is what gives them their My and Team screens.
            Only employees who don't have a login yet are listed.
        </small>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h4 class="card-title">Account</h4>
    </div>
    <div class="card-body">
        <div class="form-group mb-3">
            @if ($user->avatar)
                <img src="{{ asset('storage/'.$user->avatar) }}" alt="" class="thumb-lg rounded-circle me-2">
            @endif
            <label class="btn btn-de-primary btn-sm text-light">
                Change Avatar <input type="file" name="avatar" accept="image/*" hidden>
            </label>
            @error('avatar')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group row">
            <div class="col-md-4 mb-3">
                <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" for="email">Email <span class="text-danger">*</span></label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            @if ($isEdit)
                <div class="col-md-4 mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password">
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="form-text text-muted">Leave blank to keep the current password.</small>
                </div>
            @else
                <div class="col-md-4 mb-3">
                    <label class="form-label">Password</label>
                    <p class="form-control-plaintext text-muted font-13 mb-0">We'll email them a secure link to set their own password.</p>
                </div>
            @endif
        </div>

        <div class="form-group row">
            <div class="col-md-3 mb-3">
                <div class="form-check form-switch form-switch-success mt-4">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $user->exists ? $user->is_active : true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
                <small class="form-text text-muted">Inactive users cannot sign in.</small>
            </div>
            <div class="col-md-3 mb-3">
                <div class="form-check form-switch form-switch-success mt-4">
                    <input class="form-check-input" type="checkbox" id="is_super_admin" name="is_super_admin" value="1" {{ old('is_super_admin', $user->is_super_admin) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_super_admin">Super admin</label>
                </div>
                <small class="form-text text-muted">Bypasses all permission checks.</small>
            </div>
            <div class="col-md-6 mb-3" id="roles-field">
                <label class="form-label" for="roles">Roles</label>
                <select id="roles" name="roles[]" multiple>
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}" @selected(in_array($role->id, old('roles', $user->exists ? $user->roles->pluck('id')->all() : [])))>{{ $role->name }}</option>
                    @endforeach
                </select>
                <small class="form-text text-muted">Controls which screens and actions this user can access.</small>
            </div>
        </div>

        <div class="form-group row">
            <div class="col-md-6 mb-3">
                <label class="form-label" for="branches">Branches</label>
                <select id="branches" name="branches[]" multiple>
                    @foreach ($branches as $id => $name)
                        <option value="{{ $id }}" @selected(in_array($id, old('branches', $user->exists ? $user->branches->pluck('id')->all() : [])))>{{ $name }}</option>
                    @endforeach
                </select>
                <small class="form-text text-muted">Restricts this user to only the selected branch(es). Leave empty for company-wide access.</small>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h4 class="card-title">App Access</h4>
    </div>
    <div class="card-body">
        <p class="text-muted font-13 mb-3">Which OneSuite apps this user can open, and which one they land on right after signing in.</p>

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

        <div class="col-md-6">
            <label class="form-label" for="default_app">Default landing app</label>
            <select class="form-select" id="default_app" name="default_app">
                @foreach ($apps as $key => $app)
                    <option value="{{ $key }}" @selected($selectedDefaultApp === $key)>{{ $app['label'] }}</option>
                @endforeach
            </select>
            <small class="form-text text-muted">Where this user lands right after signing in - only apps checked above are offered.</small>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h4 class="card-title">Profile</h4>
    </div>
    <div class="card-body">
        <div class="form-group row">
            <div class="col-md-3 mb-3">
                <label class="form-label" for="employee_code">Employee code</label>
                <input type="text" class="form-control @error('employee_code') is-invalid @enderror" id="employee_code" name="employee_code" value="{{ old('employee_code', $user->employee_code) }}">
                @error('employee_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label" for="phone">Phone</label>
                <input type="tel" class="form-control" id="phone" name="phone" value="{{ old('phone', $user->phone) }}">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label" for="gender">Gender</label>
                <select class="form-select" id="gender" name="gender">
                    <option value="">Select an option</option>
                    <option value="male" @selected(old('gender', $user->gender) === 'male')>Male</option>
                    <option value="female" @selected(old('gender', $user->gender) === 'female')>Female</option>
                    <option value="other" @selected(old('gender', $user->gender) === 'other')>Other</option>
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label" for="date_of_birth">Date of birth</label>
                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth', optional($user->date_of_birth)->format('Y-m-d')) }}">
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h4 class="card-title">Employment</h4>
    </div>
    <div class="card-body">
        <div class="form-group row">
            <div class="col-md-4 mb-3">
                <label class="form-label" for="designation">Designation</label>
                <input type="text" class="form-control" id="designation" name="designation" value="{{ old('designation', $user->designation) }}">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" for="department">Department</label>
                <input type="text" class="form-control" id="department" name="department" value="{{ old('department', $user->department) }}">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" for="joining_date">Joining date</label>
                <input type="date" class="form-control" id="joining_date" name="joining_date" value="{{ old('joining_date', optional($user->joining_date)->format('Y-m-d')) }}">
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h4 class="card-title">Address</h4>
    </div>
    <div class="card-body">
        <div class="form-group row">
            <div class="col-12 mb-3">
                <label class="form-label" for="address">Address</label>
                <input type="text" class="form-control" id="address" name="address" value="{{ old('address', $user->address) }}">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label" for="city">City</label>
                <input type="text" class="form-control" id="city" name="city" value="{{ old('city', $user->city) }}">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label" for="state">State</label>
                <input type="text" class="form-control" id="state" name="state" value="{{ old('state', $user->state) }}">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label" for="country">Country</label>
                <input type="text" class="form-control" id="country" name="country" value="{{ old('country', $user->country) }}">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label" for="postal_code">Postal code</label>
                <input type="text" class="form-control" id="postal_code" name="postal_code" value="{{ old('postal_code', $user->postal_code) }}">
            </div>
        </div>
    </div>
</div>

<div class="mb-4">
    <button type="submit" class="btn btn-de-primary text-light px-4">Save</button>
    <a href="{{ route('admin.users.index') }}" class="btn btn-de-danger text-light px-4">Cancel</a>
</div>

@push('styles')
    <link href="{{ asset('vendor/metrica/assets/libs/mobius1-selectr/selectr.min.css') }}" rel="stylesheet" type="text/css" />
@endpush

@push('scripts')
    <script src="{{ asset('vendor/metrica/assets/libs/mobius1-selectr/selectr.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            new Selectr(document.getElementById('roles'), { multiple: true, tagOutputSeparator: ', ' });
            var branchPicker = new Selectr(document.getElementById('branches'), { multiple: true, tagOutputSeparator: ', ' });

            // Choosing an employee copies their record into the form. Only on
            // an actual change - never on page load, so opening an existing
            // user doesn't overwrite what was saved for them.
            var employeePicker = document.querySelector('[data-employee-picker]');
            var employeeFilled = document.querySelector('[data-employee-filled]');
            var employeeFields = ['name', 'email', 'employee_code', 'phone', 'gender', 'date_of_birth', 'designation', 'department', 'joining_date', 'address', 'city', 'state', 'country', 'postal_code'];

            if (employeePicker) {
                employeePicker.addEventListener('change', function () {
                    var option = employeePicker.options[employeePicker.selectedIndex];
                    var details = option && option.dataset.employee ? JSON.parse(option.dataset.employee) : null;

                    employeeFilled.classList.add('d-none');

                    if (!details) {
                        return;
                    }

                    // An existing login keeps its sign-in email: changing it here
                    // would silently change how that person logs in.
                    var keepEmail = employeePicker.hasAttribute('data-keep-email');

                    employeeFields.forEach(function (field) {
                        var input = document.getElementById(field);

                        if (input && !(keepEmail && field === 'email')) {
                            input.value = details[field] === null || details[field] === undefined ? '' : details[field];
                        }
                    });

                    // Their branch, when it is one this admin may assign.
                    var branchOption = details.branch_id ? document.querySelector('#branches option[value="' + details.branch_id + '"]') : null;

                    if (branchOption) {
                        branchPicker.clear();
                        branchPicker.setValue(String(details.branch_id));
                    }

                    employeeFilled.querySelector('span').textContent = 'Details filled in from ' + details.name + "'s employee record."
                        + (keepEmail ? ' The login email was left as it is.' : '');
                    employeeFilled.classList.remove('d-none');
                });
            }

            var superAdminCheckbox = document.getElementById('is_super_admin');
            var rolesField = document.getElementById('roles-field');

            function toggleRolesField() {
                rolesField.classList.toggle('d-none', superAdminCheckbox.checked);
            }

            superAdminCheckbox.addEventListener('change', toggleRolesField);
            toggleRolesField();

            var appCheckboxes = Array.from(document.querySelectorAll('[data-app-access-checkbox]'));
            var defaultAppSelect = document.getElementById('default_app');

            function tileFor(checkbox) {
                return checkbox.closest('[data-app-access-tile]');
            }

            function syncAppAccessState() {
                appCheckboxes.forEach(function (checkbox) {
                    tileFor(checkbox).classList.toggle('is-checked', checkbox.checked);
                });

                Array.from(defaultAppSelect.options).forEach(function (option) {
                    var checkbox = appCheckboxes.find(function (cb) { return cb.value === option.value; });
                    var checked = checkbox ? checkbox.checked : false;
                    option.disabled = !checked;
                    option.hidden = !checked;
                });

                var currentOption = defaultAppSelect.options[defaultAppSelect.selectedIndex];

                if (!currentOption || currentOption.disabled) {
                    // Same "HRM first" preference as AppLauncher::preferredKey()
                    // server-side - keeps the auto-picked default consistent
                    // with what actually happens after this user logs in.
                    var homeOrder = ['hrm', 'settings', 'crm', 'inventory', 'sales'];
                    var enabledValues = Array.from(defaultAppSelect.options).filter(function (o) { return !o.disabled; }).map(function (o) { return o.value; });
                    var preferred = homeOrder.find(function (key) { return enabledValues.indexOf(key) !== -1; }) || enabledValues[0];

                    if (preferred) {
                        defaultAppSelect.value = preferred;
                    }
                }
            }

            appCheckboxes.forEach(function (checkbox) {
                checkbox.addEventListener('change', syncAppAccessState);
            });

            syncAppAccessState();
        });
    </script>
@endpush
