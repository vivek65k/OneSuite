@php
    $isEditing = $position->exists;
    $currency = \App\Models\Company::current()->currency;

    // Matches legacy "full_time" to the form's "Full-time" so editing keeps it.
    ['options' => $employmentTypes, 'selected' => $selectedType] = \App\Support\EmploymentType::formOptions(old('employment_type', $position->employment_type));

    $currentStatus = old('status', $position->status ?: 'draft');
    $statusOptions = [
        'draft' => ['tone' => 'muted', 'hint' => 'Not visible to applicants yet'],
        'open' => ['tone' => 'success', 'hint' => 'Accepting applications'],
        'on_hold' => ['tone' => 'warning', 'hint' => 'Paused for now'],
        'closed' => ['tone' => 'danger', 'hint' => 'Filled or cancelled'],
    ];
    $cancelUrl = route('admin.job-positions.index');
@endphp

<div class="record-header">
    <a href="{{ $cancelUrl }}" class="btn-icon record-back" title="Back"><i class="ti ti-arrow-left font-18"></i></a>
    <span class="list-avatar list-avatar-square record-avatar tone-violet" aria-hidden="true"><i class="ti ti-briefcase font-20"></i></span>
    <div class="record-header-text">
        <div class="record-header-title" id="record-title">{{ old('title', $position->title) ?: 'New job position' }}</div>
        <div class="record-header-meta">
            @if ($isEditing)
                <span class="status-dot status-dot-{{ $statusOptions[$position->status]['tone'] ?? 'muted' }}">{{ Str::headline($position->status) }}</span>
                <span>&middot; Posted {{ $position->created_at?->diffForHumans() }}</span>
            @else
                Only fields marked <span class="text-danger">*</span> are required.
            @endif
        </div>
    </div>
    <div class="record-header-actions">
        <a href="{{ $cancelUrl }}" class="btn btn-light btn-sm px-3">Cancel</a>
        <button type="submit" class="btn btn-de-primary btn-sm text-light px-3">
            <i class="ti ti-check font-14 me-1"></i> {{ $isEditing ? 'Save changes' : 'Create position' }}
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
        <div class="card form-section tone-indigo">
            <div class="form-section-head">
                <span class="form-section-icon"><i class="ti ti-briefcase"></i></span>
                <div>
                    <h5 class="form-section-title">Position details</h5>
                    <p class="form-section-sub">What the role is and where it sits in the organization.</p>
                </div>
            </div>
            <div class="form-section-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="title">Job title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $position->title) }}" placeholder="e.g. Senior Software Engineer" required>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="department_id">Department</label>
                        <select class="form-select @error('department_id') is-invalid @enderror" id="department_id" name="department_id">
                            <option value="">None</option>
                            @foreach ($departments as $id => $name)
                                <option value="{{ $id }}" @selected((int) old('department_id', $position->department_id) === $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="branch_id">Branch</label>
                        <select class="form-select @error('branch_id') is-invalid @enderror" id="branch_id" name="branch_id">
                            <option value="">None</option>
                            @foreach ($branches as $id => $name)
                                <option value="{{ $id }}" @selected((int) old('branch_id', $position->branch_id) === $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="employment_type">Employment type</label>
                        <select class="form-select @error('employment_type') is-invalid @enderror" id="employment_type" name="employment_type">
                            <option value="">Select</option>
                            @foreach ($employmentTypes as $type)
                                <option value="{{ $type }}" @selected($selectedType === $type)>{{ Str::headline($type) }}</option>
                            @endforeach
                        </select>
                        @error('employment_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card form-section tone-teal">
            <div class="form-section-head">
                <span class="form-section-icon"><i class="ti ti-cash"></i></span>
                <div>
                    <h5 class="form-section-title">Compensation</h5>
                    <p class="form-section-sub">Salary band for this role - leave blank to keep it undisclosed.</p>
                </div>
            </div>
            <div class="form-section-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="min_salary">Minimum salary</label>
                        <div class="input-group has-validation">
                            <span class="input-group-text input-group-text-tone">{{ $currency }}</span>
                            <input type="number" step="0.01" min="0" class="form-control @error('min_salary') is-invalid @enderror" id="min_salary" name="min_salary" value="{{ old('min_salary', $position->min_salary) }}" placeholder="0.00">
                            @error('min_salary')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="max_salary">Maximum salary</label>
                        <div class="input-group has-validation">
                            <span class="input-group-text input-group-text-tone">{{ $currency }}</span>
                            <input type="number" step="0.01" min="0" class="form-control @error('max_salary') is-invalid @enderror" id="max_salary" name="max_salary" value="{{ old('max_salary', $position->max_salary) }}" placeholder="0.00">
                            @error('max_salary')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card form-section tone-pink">
            <div class="form-section-head">
                <span class="form-section-icon"><i class="ti ti-file-text"></i></span>
                <div>
                    <h5 class="form-section-title">Job description</h5>
                    <p class="form-section-sub">Responsibilities, requirements and anything candidates should know.</p>
                </div>
            </div>
            <div class="form-section-body">
                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="7" maxlength="2000" placeholder="Describe the role…" aria-label="Job description">{{ old('description', $position->description) }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-hint justify-content-end"><span id="description-count">{{ mb_strlen((string) old('description', $position->description)) }}</span>/2000</div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card form-section tone-violet">
            <div class="form-section-head form-section-head-compact">
                <span class="form-section-icon form-section-icon-sm"><i class="ti ti-activity"></i></span>
                <h5 class="form-section-title">Status <span class="text-danger">*</span></h5>
            </div>
            <div class="form-section-body">
                <div class="status-options" role="radiogroup" aria-label="Position status">
                    @foreach (\App\Models\JobPosition::STATUSES as $status)
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

        <div class="card form-section tone-amber">
            <div class="form-section-head form-section-head-compact">
                <span class="form-section-icon form-section-icon-sm"><i class="ti ti-user-plus"></i></span>
                <h5 class="form-section-title">Headcount <span class="text-danger">*</span></h5>
            </div>
            <div class="form-section-body">
                <div class="stepper">
                    <button type="button" class="btn-icon" data-step="-1" aria-label="Decrease headcount"><i class="ti ti-minus"></i></button>
                    <input type="number" min="1" class="form-control @error('headcount') is-invalid @enderror" id="headcount" name="headcount" value="{{ old('headcount', $position->headcount ?: 1) }}" required aria-label="Headcount">
                    <button type="button" class="btn-icon" data-step="1" aria-label="Increase headcount"><i class="ti ti-plus"></i></button>
                </div>
                @error('headcount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                <div class="form-hint"><i class="ti ti-info-circle"></i> How many people you plan to hire for this position.</div>
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
                        <dt>Applicants</dt>
                        <dd>{{ $position->candidates()->count() }}</dd>
                        <dt>Created</dt>
                        <dd>{{ $position->created_at?->format('M j, Y') ?? '—' }}</dd>
                        <dt>Last updated</dt>
                        <dd>{{ $position->updated_at?->diffForHumans() ?? '—' }}</dd>
                    </dl>
                </div>
            </div>
        @endif
    </div>
</div>

<div class="record-footer">
    <a href="{{ $cancelUrl }}" class="btn btn-light px-4">Cancel</a>
    <button type="submit" class="btn btn-de-primary text-light px-4">
        <i class="ti ti-check font-14 me-1"></i> {{ $isEditing ? 'Save changes' : 'Create position' }}
    </button>
</div>

@push('scripts')
    <script>
        (function () {
            var titleInput = document.getElementById('title');
            var title = document.getElementById('record-title');
            if (titleInput && title) {
                titleInput.addEventListener('input', function () {
                    title.textContent = titleInput.value.trim() || @json($isEditing ? $position->title : 'New job position');
                });
            }

            var headcount = document.getElementById('headcount');
            document.querySelectorAll('.stepper [data-step]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var next = (parseInt(headcount.value, 10) || 1) + parseInt(button.dataset.step, 10);
                    headcount.value = Math.max(1, next);
                });
            });

            var description = document.getElementById('description');
            var counter = document.getElementById('description-count');
            if (description && counter) {
                description.addEventListener('input', function () {
                    counter.textContent = description.value.length;
                });
            }
        })();
    </script>
@endpush
