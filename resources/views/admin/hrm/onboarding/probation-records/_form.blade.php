@php
    $isEditing = $record->exists;
    $currentStatus = old('status', $record->status ?: 'on_probation');
    $statusMeta = \App\Support\Onboarding::PROBATION_META;
    $statusHints = [
        'on_probation' => 'Probation is running',
        'extended' => 'More time before a decision',
        'confirmed' => 'Passed - now a permanent employee',
        'terminated' => 'Did not pass probation',
    ];
    $cancelUrl = route('admin.probation-records.index');
@endphp

<div class="record-header">
    <a href="{{ $cancelUrl }}" class="btn-icon record-back" title="Back"><i class="ti ti-arrow-left font-18"></i></a>
    <span class="list-avatar list-avatar-square record-avatar tone-lime" aria-hidden="true"><i class="ti ti-shield-check font-20"></i></span>
    <div class="record-header-text">
        <div class="record-header-title">{{ $isEditing ? 'Probation · '.($record->employee?->name ?? 'Employee') : 'New probation record' }}</div>
        <div class="record-header-meta">
            @if ($isEditing)
                <span class="tag tone-{{ $statusMeta[$record->status]['tone'] ?? 'slate' }}">{{ \App\Models\ProbationRecord::STATUSES[$record->status] ?? $record->status }}</span>
                <span>{{ $record->start_date?->format('M j, Y') }} → {{ \App\Support\Onboarding::probationDueDate($record)?->format('M j, Y') }}</span>
            @else
                Pick the employee - the start date fills in from their joining date.
            @endif
        </div>
    </div>
    <div class="record-header-actions">
        <a href="{{ $cancelUrl }}" class="btn btn-light btn-sm px-3">Cancel</a>
        <button type="submit" class="btn btn-de-primary btn-sm text-light px-3">
            <i class="ti ti-check font-14 me-1"></i> {{ $isEditing ? 'Save changes' : 'Add record' }}
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
                <span class="form-section-icon"><i class="ti ti-user"></i></span>
                <div>
                    <h5 class="form-section-title">Employee &amp; period</h5>
                    <p class="form-section-sub">Who is on probation, and for how long.</p>
                </div>
            </div>
            <div class="form-section-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="employee_id">Employee <span class="text-danger">*</span></label>
                        <select class="form-select @error('employee_id') is-invalid @enderror" id="employee_id" name="employee_id" required>
                            <option value="">Select employee</option>
                            @foreach ($employees as $id => $name)
                                <option value="{{ $id }}" @selected((int) old('employee_id', $record->employee_id) === $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="start_date">Start date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('start_date') is-invalid @enderror" id="start_date" name="start_date" value="{{ old('start_date', optional($record->start_date)->format('Y-m-d')) }}" required>
                        @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="end_date">Review date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('end_date') is-invalid @enderror" id="end_date" name="end_date" value="{{ old('end_date', optional($record->end_date)->format('Y-m-d')) }}" required>
                        @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="quick-chips" id="duration-chips">
                            <button type="button" class="quick-chip" data-months="3">3 months</button>
                            <button type="button" class="quick-chip" data-months="6">6 months</button>
                            <button type="button" class="quick-chip" data-months="12">1 year</button>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-hint" id="period-length" hidden><i class="ti ti-calendar-stats"></i> <span></span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card form-section tone-amber" id="extend" @if ($currentStatus !== 'extended' && ! old('extended_to', $record->extended_to)) hidden @endif>
            <div class="form-section-head">
                <span class="form-section-icon"><i class="ti ti-calendar-stats"></i></span>
                <div>
                    <h5 class="form-section-title">Extension</h5>
                    <p class="form-section-sub">The new review date when probation is extended.</p>
                </div>
            </div>
            <div class="form-section-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="extended_to">Extended to</label>
                        <input type="date" class="form-control @error('extended_to') is-invalid @enderror" id="extended_to" name="extended_to" value="{{ old('extended_to', optional($record->extended_to)->format('Y-m-d')) }}">
                        @error('extended_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="quick-chips" id="extend-chips">
                            <button type="button" class="quick-chip" data-months="1">+1 month</button>
                            <button type="button" class="quick-chip" data-months="2">+2 months</button>
                            <button type="button" class="quick-chip" data-months="3">+3 months</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card form-section tone-pink">
            <div class="form-section-head">
                <span class="form-section-icon"><i class="ti ti-notes"></i></span>
                <div>
                    <h5 class="form-section-title">Remarks</h5>
                    <p class="form-section-sub">Performance notes, reasons for extension or the final decision.</p>
                </div>
            </div>
            <div class="form-section-body">
                <textarea class="form-control @error('remarks') is-invalid @enderror" id="remarks" name="remarks" rows="4" maxlength="1000" placeholder="Add remarks" aria-label="Remarks">{{ old('remarks', $record->remarks) }}</textarea>
                @error('remarks')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-hint justify-content-end"><span id="remarks-count">{{ mb_strlen((string) old('remarks', $record->remarks)) }}</span>/1000</div>
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
                <div class="status-options" role="radiogroup" aria-label="Probation status">
                    @foreach (\App\Models\ProbationRecord::STATUSES as $value => $label)
                        <label class="status-option">
                            <input type="radio" name="status" value="{{ $value }}" @checked($currentStatus === $value) required>
                            <span class="status-option-body">
                                <span class="status-dot status-dot-{{ $statusMeta[$value]['dot'] ?? 'muted' }}">{{ $label }}</span>
                                <span class="status-option-hint">{{ $statusHints[$value] ?? '' }}</span>
                            </span>
                            <i class="ti ti-circle-check status-option-check"></i>
                        </label>
                    @endforeach
                </div>
                @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                <div class="form-hint"><i class="ti ti-info-circle"></i> Confirming or terminating records you as the reviewer.</div>
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
                        @if ($record->reviewed_at)
                            <dt>Reviewed</dt>
                            <dd>{{ $record->reviewed_at->format('M j, Y') }}{{ $record->reviewedBy ? ' · '.$record->reviewedBy->name : '' }}</dd>
                        @endif
                        <dt>Created</dt>
                        <dd>{{ $record->created_at?->format('M j, Y') ?? '—' }}</dd>
                        <dt>Last updated</dt>
                        <dd>{{ $record->updated_at?->diffForHumans() ?? '—' }}</dd>
                    </dl>
                </div>
            </div>
        @endif
    </div>
</div>

<div class="record-footer">
    <a href="{{ $cancelUrl }}" class="btn btn-light px-4">Cancel</a>
    <button type="submit" class="btn btn-de-primary text-light px-4">
        <i class="ti ti-check font-14 me-1"></i> {{ $isEditing ? 'Save changes' : 'Add record' }}
    </button>
</div>

@push('scripts')
    <script>
        (function () {
            var joiningDates = @json($joiningDates ?? []);
            var $ = function (id) { return document.getElementById(id); };
            var employee = $('employee_id'), start = $('start_date'), end = $('end_date'),
                extendCard = $('extend'), extendedTo = $('extended_to'), lengthHint = $('period-length');

            function pad(n) { return String(n).padStart(2, '0'); }
            function iso(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
            function addMonths(value, months) {
                var d = value ? new Date(value + 'T00:00:00') : new Date();
                d.setMonth(d.getMonth() + months);
                return iso(d);
            }

            function updateLength() {
                if (!start.value || !end.value) { lengthHint.hidden = true; return; }
                var days = Math.round((new Date(end.value) - new Date(start.value)) / 86400000);
                lengthHint.hidden = false;
                lengthHint.classList.toggle('text-danger', days < 0);
                lengthHint.querySelector('span').textContent = days < 0
                    ? 'Review date is before the start date'
                    : days + ' days (' + (days / 30.44).toFixed(1) + ' months) of probation';
            }

            // Start from the employee's joining date when the field is empty.
            employee.addEventListener('change', function () {
                if (!start.value && joiningDates[employee.value]) {
                    start.value = joiningDates[employee.value];
                    updateLength();
                }
            });

            document.querySelectorAll('#duration-chips .quick-chip').forEach(function (chip) {
                chip.addEventListener('click', function () {
                    end.value = addMonths(start.value, parseInt(chip.dataset.months, 10));
                    updateLength();
                });
            });
            document.querySelectorAll('#extend-chips .quick-chip').forEach(function (chip) {
                chip.addEventListener('click', function () {
                    extendedTo.value = addMonths(end.value, parseInt(chip.dataset.months, 10));
                });
            });

            // Show the extension card only for "Extended" (or deep link #extend).
            function syncExtend() {
                var checked = document.querySelector('input[name="status"]:checked');
                extendCard.hidden = !(checked && checked.value === 'extended') && !extendedTo.value;
            }
            document.querySelectorAll('input[name="status"]').forEach(function (radio) {
                radio.addEventListener('change', syncExtend);
            });
            if (location.hash === '#extend') {
                var extendedRadio = document.querySelector('input[name="status"][value="extended"]');
                if (extendedRadio) { extendedRadio.checked = true; }
                syncExtend();
                extendCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            [start, end].forEach(function (el) { el.addEventListener('change', updateLength); });
            updateLength();

            var remarks = $('remarks'), counter = $('remarks-count');
            remarks.addEventListener('input', function () { counter.textContent = remarks.value.length; });
        })();
    </script>
@endpush
