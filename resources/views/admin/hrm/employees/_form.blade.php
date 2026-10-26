@php
    $isEditing = $employee->exists;
    // Matches legacy "full_time" to the form's "Full-time" so editing keeps it.
    ['options' => $employmentTypes, 'selected' => $selectedType] = \App\Support\EmploymentType::formOptions(old('employment_type', $employee->employment_type));

    $currentStatus = old('status', $employee->status ?: 'active');
    $statusOptions = [
        'active' => ['tone' => 'success', 'hint' => 'Currently working'],
        'on_leave' => ['tone' => 'warning', 'hint' => 'Temporarily away'],
        'inactive' => ['tone' => 'muted', 'hint' => 'Not currently working'],
        'terminated' => ['tone' => 'danger', 'hint' => 'No longer employed'],
    ];

    $displayName = old('name', $employee->name);
    $initials = collect(preg_split('/\s+/', trim((string) $displayName)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
    $cancelUrl = $isEditing ? route('admin.employees.show', $employee) : route('admin.employees.index');
@endphp

{{-- Sticky record header: identity on the left, primary actions always in reach --}}
<div class="record-header">
    <a href="{{ $cancelUrl }}" class="btn-icon record-back" title="Back"><i class="ti ti-arrow-left font-18"></i></a>
    @if ($employee->avatar)
        <img src="{{ asset('storage/'.$employee->avatar) }}" alt="" class="list-avatar record-avatar">
    @else
        <span class="list-avatar list-avatar-teal record-avatar" id="record-avatar" aria-hidden="true">
            @if ($initials)
                {{ $initials }}
            @else
                <i class="ti ti-user font-18"></i>
            @endif
        </span>
    @endif
    <div class="record-header-text">
        <div class="record-header-title" id="record-title">{{ $displayName ?: 'New employee' }}</div>
        <div class="record-header-meta">
            @if ($isEditing)
                <span class="list-code">{{ $employee->employee_code }}</span>
                <span class="status-dot status-dot-{{ $statusOptions[$employee->status]['tone'] ?? 'muted' }}">{{ Str::headline($employee->status) }}</span>
            @else
                Fill in the details below - only fields marked <span class="text-danger">*</span> are required.
            @endif
        </div>
    </div>
    <div class="record-header-actions">
        <a href="{{ $cancelUrl }}" class="btn btn-light btn-sm px-3">Cancel</a>
        <button type="submit" class="btn btn-de-primary btn-sm text-light px-3">
            <i class="ti ti-check font-14 me-1"></i> {{ $isEditing ? 'Save changes' : 'Create employee' }}
        </button>
    </div>
</div>

@if ($errors->any())
    <div class="alert alert-danger">
        <div class="fw-semibold mb-1">Please fix {{ $errors->count() === 1 ? 'this issue' : 'these '.$errors->count().' issues' }} before saving:</div>
        <ul class="mb-0 ps-3 font-13">
            @foreach ($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-3">
    <div class="col-lg-8">
        {{-- Personal --}}
        <div class="card form-section tone-indigo">
            <div class="form-section-head">
                <span class="form-section-icon"><i class="ti ti-id"></i></span>
                <div>
                    <h5 class="form-section-title">Personal details</h5>
                    <p class="form-section-sub">Who this employee is and how to reach them.</p>
                </div>
            </div>
            <div class="form-section-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="employee_code">Employee code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('employee_code') is-invalid @enderror" id="employee_code" name="employee_code" value="{{ old('employee_code', $employee->employee_code) }}" placeholder="e.g. EMP-0042" required>
                        @error('employee_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" for="name">Full name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $employee->name) }}" placeholder="First and last name" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="email">Work email</label>
                        <div class="field-icon">
                            <i class="ti ti-mail"></i>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $employee->email) }}" placeholder="name@company.com">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="phone">Phone</label>
                        <div class="field-icon">
                            <i class="ti ti-phone"></i>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $employee->phone) }}" placeholder="+91 98765 43210">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="gender">Gender</label>
                        <select class="form-select @error('gender') is-invalid @enderror" id="gender" name="gender">
                            <option value="">Select</option>
                            <option value="male" @selected(old('gender', $employee->gender) === 'male')>Male</option>
                            <option value="female" @selected(old('gender', $employee->gender) === 'female')>Female</option>
                            <option value="other" @selected(old('gender', $employee->gender) === 'other')>Other</option>
                        </select>
                        @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="date_of_birth">Date of birth</label>
                        <input type="date" class="form-control @error('date_of_birth') is-invalid @enderror" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth', optional($employee->date_of_birth)->format('Y-m-d')) }}">
                        @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Employment --}}
        <div class="card form-section tone-teal">
            <div class="form-section-head">
                <span class="form-section-icon"><i class="ti ti-briefcase"></i></span>
                <div>
                    <h5 class="form-section-title">Employment</h5>
                    <p class="form-section-sub">Role, placement in the organization and reporting line.</p>
                </div>
            </div>
            <div class="form-section-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="designation">Designation</label>
                        <input type="text" class="form-control @error('designation') is-invalid @enderror" id="designation" name="designation" value="{{ old('designation', $employee->designation) }}" placeholder="e.g. Sales Executive">
                        @error('designation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="employment_type">Employment type</label>
                        <select class="form-select @error('employment_type') is-invalid @enderror" id="employment_type" name="employment_type">
                            <option value="">Select</option>
                            @foreach ($employmentTypes as $type)
                                <option value="{{ $type }}" @selected($selectedType === $type)>{{ Str::headline($type) }}</option>
                            @endforeach
                        </select>
                        @error('employment_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="department_id">Department</label>
                        <select class="form-select @error('department_id') is-invalid @enderror" id="department_id" name="department_id">
                            <option value="">None</option>
                            @foreach ($departments as $id => $name)
                                <option value="{{ $id }}" @selected((int) old('department_id', $employee->department_id) === $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="branch_id">Branch</label>
                        <select class="form-select @error('branch_id') is-invalid @enderror" id="branch_id" name="branch_id">
                            <option value="">None</option>
                            @foreach ($branches as $id => $name)
                                <option value="{{ $id }}" @selected((int) old('branch_id', $employee->branch_id) === $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="joining_date">Joining date</label>
                        <input type="date" class="form-control @error('joining_date') is-invalid @enderror" id="joining_date" name="joining_date" value="{{ old('joining_date', optional($employee->joining_date)->format('Y-m-d')) }}">
                        @error('joining_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="manager_id">Reports to</label>
                        <select class="form-select @error('manager_id') is-invalid @enderror" id="manager_id" name="manager_id">
                            <option value="">No manager</option>
                            @foreach ($managers as $id => $name)
                                <option value="{{ $id }}" @selected((int) old('manager_id', $employee->manager_id) === $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('manager_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Address --}}
        <div class="card form-section tone-pink">
            <div class="form-section-head">
                <span class="form-section-icon"><i class="ti ti-map-pin"></i></span>
                <div>
                    <h5 class="form-section-title">Address</h5>
                    <p class="form-section-sub">Residential address, used on letters and payslips.</p>
                </div>
            </div>
            <div class="form-section-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="address">Street address</label>
                        <input type="text" class="form-control @error('address') is-invalid @enderror" id="address" name="address" value="{{ old('address', $employee->address) }}" placeholder="House / street / area">
                        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="city">City</label>
                        <input type="text" class="form-control @error('city') is-invalid @enderror" id="city" name="city" value="{{ old('city', $employee->city) }}">
                        @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="state">State</label>
                        <input type="text" class="form-control @error('state') is-invalid @enderror" id="state" name="state" value="{{ old('state', $employee->state) }}">
                        @error('state')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="country">Country</label>
                        <input type="text" class="form-control @error('country') is-invalid @enderror" id="country" name="country" value="{{ old('country', $employee->country) }}">
                        @error('country')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="postal_code">Postal code</label>
                        <input type="text" class="form-control @error('postal_code') is-invalid @enderror" id="postal_code" name="postal_code" value="{{ old('postal_code', $employee->postal_code) }}">
                        @error('postal_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Sidebar: status, access, record info --}}
    <div class="col-lg-4">
        <div class="record-sidebar">
            <div class="card form-section tone-violet">
                <div class="form-section-head form-section-head-compact">
                    <span class="form-section-icon form-section-icon-sm"><i class="ti ti-activity"></i></span>
                    <h5 class="form-section-title">Status <span class="text-danger">*</span></h5>
                </div>
                <div class="form-section-body">
                    <div class="status-options" role="radiogroup" aria-label="Employee status">
                        @foreach (\App\Models\Employee::STATUSES as $status)
                            <label class="status-option">
                                <input type="radio" name="status" value="{{ $status }}" @checked($currentStatus === $status) required>
                                <span class="status-option-body">
                                    <span class="status-dot status-dot-{{ $statusOptions[$status]['tone'] ?? 'muted' }}">{{ Str::headline($status) }}</span>
                                    <span class="status-option-hint">{{ $statusOptions[$status]['hint'] ?? '' }}</span>
                                </span>
                                <i class="ti ti-circle-check status-option-check"></i>
                            </label>
                        @endforeach
                    </div>
                    @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="card form-section tone-sky">
                <div class="form-section-head form-section-head-compact">
                    <span class="form-section-icon form-section-icon-sm"><i class="ti ti-key"></i></span>
                    <h5 class="form-section-title">Self-service access</h5>
                </div>
                <div class="form-section-body">
                    <label class="form-label" for="user_id">Linked login</label>
                    <select class="form-select @error('user_id') is-invalid @enderror" id="user_id" name="user_id">
                        <option value="">Not linked</option>
                        @foreach ($users as $id => $name)
                            <option value="{{ $id }}" @selected((int) old('user_id', $employee->user_id) === $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-hint"><i class="ti ti-info-circle"></i> Lets this login use Employee Self Service for their own leave, attendance and payslips.</div>
                </div>
            </div>

            @if ($isEditing)
                <div class="card form-section tone-slate">
                    <div class="form-section-head form-section-head-compact">
                        <span class="form-section-icon form-section-icon-sm"><i class="ti ti-info-square"></i></span>
                        <h5 class="form-section-title">Record info</h5>
                    </div>
                    <div class="form-section-body">
                        <dl class="record-meta">
                            <dt>Created</dt>
                            <dd>{{ $employee->created_at?->format('M j, Y g:i A') ?? '—' }}</dd>
                            <dt>Last updated</dt>
                            <dd>{{ $employee->updated_at?->diffForHumans() ?? '—' }}</dd>
                        </dl>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="record-footer">
    <a href="{{ $cancelUrl }}" class="btn btn-light px-4">Cancel</a>
    <button type="submit" class="btn btn-de-primary text-light px-4">
        <i class="ti ti-check font-14 me-1"></i> {{ $isEditing ? 'Save changes' : 'Create employee' }}
    </button>
</div>

@unless ($employee->avatar)
    @push('scripts')
        <script>
            // Live header preview: title + initials follow the name field.
            (function () {
                var nameInput = document.getElementById('name');
                var title = document.getElementById('record-title');
                var avatar = document.getElementById('record-avatar');
                if (!nameInput || !title || !avatar) return;

                nameInput.addEventListener('input', function () {
                    var name = nameInput.value.trim();
                    title.textContent = name || @json($isEditing ? $employee->name : 'New employee');
                    var initials = name.split(/\s+/).filter(Boolean).slice(0, 2).map(function (part) {
                        return part.charAt(0).toUpperCase();
                    }).join('');
                    avatar.innerHTML = initials || '<i class="ti ti-user font-18"></i>';
                });
            })();
        </script>
    @endpush
@endunless
