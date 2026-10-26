@php
    $isEditing = $offer->exists;
    $selectedCandidate = (int) old('candidate_id', $offer->candidate_id);
    $currentStatus = old('status', $offer->status ?: 'draft');
    $currentCurrency = strtoupper((string) old('currency', $offer->currency ?: \App\Models\Company::current()->currency));

    // "rejected" on an offer means the candidate turned it down.
    $statusOptions = [
        'draft' => ['label' => 'Draft', 'tone' => 'muted', 'hint' => 'Being prepared, not sent'],
        'sent' => ['label' => 'Sent', 'tone' => 'info', 'hint' => 'With the candidate'],
        'accepted' => ['label' => 'Accepted', 'tone' => 'success', 'hint' => 'Candidate said yes'],
        'rejected' => ['label' => 'Declined', 'tone' => 'danger', 'hint' => 'Candidate turned it down'],
        'withdrawn' => ['label' => 'Withdrawn', 'tone' => 'muted', 'hint' => 'Pulled back by us'],
    ];
    $stageTones = ['applied' => 'slate', 'screening' => 'sky', 'interview' => 'violet', 'offer' => 'amber', 'hired' => 'success', 'rejected' => 'danger'];

    $currencies = collect([$currentCurrency, 'INR', 'USD', 'EUR', 'GBP', 'AED', 'SGD', 'AUD', 'CAD'])->filter()->unique()->values();
    $cancelUrl = route('admin.offer-letters.index');
@endphp

<div class="record-header">
    <a href="{{ $cancelUrl }}" class="btn-icon record-back" title="Back"><i class="ti ti-arrow-left font-18"></i></a>
    <span class="list-avatar list-avatar-square record-avatar tone-success" aria-hidden="true"><i class="ti ti-file-certificate font-20"></i></span>
    <div class="record-header-text">
        <div class="record-header-title">
            {{ $isEditing ? 'Offer · '.($offer->candidate?->name ?? 'Candidate') : 'New offer letter' }}
        </div>
        <div class="record-header-meta">
            @if ($isEditing)
                <span class="status-dot status-dot-{{ $statusOptions[$offer->status]['tone'] ?? 'muted' }}">{{ $statusOptions[$offer->status]['label'] ?? Str::headline($offer->status) }}</span>
                @if ($offer->jobPosition)
                    <span><i class="ti ti-briefcase"></i> {{ $offer->jobPosition->title }}</span>
                @endif
            @else
                Pick the candidate - the position and salary band fill in from their application.
            @endif
        </div>
    </div>
    <div class="record-header-actions">
        <a href="{{ $cancelUrl }}" class="btn btn-light btn-sm px-3">Cancel</a>
        <button type="submit" class="btn btn-de-primary btn-sm text-light px-3">
            <i class="ti ti-check font-14 me-1"></i> {{ $isEditing ? 'Save changes' : 'Create offer' }}
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
                    <h5 class="form-section-title">Offer to</h5>
                    <p class="form-section-sub">The candidate receiving this offer, and the role.</p>
                </div>
            </div>
            <div class="form-section-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="candidate_id">Candidate <span class="text-danger">*</span></label>
                        <select class="form-select @error('candidate_id') is-invalid @enderror" id="candidate_id" name="candidate_id" required>
                            <option value="">Select a candidate</option>
                            @foreach ($candidates as $id => $name)
                                <option value="{{ $id }}" @selected($selectedCandidate === $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('candidate_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="job_position_id">Position</label>
                        <select class="form-select @error('job_position_id') is-invalid @enderror" id="job_position_id" name="job_position_id">
                            <option value="">None</option>
                            @foreach ($jobPositions as $id => $title)
                                <option value="{{ $id }}" @selected((int) old('job_position_id', $offer->job_position_id) === $id)>{{ $title }}</option>
                            @endforeach
                        </select>
                        @error('job_position_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <div class="candidate-peek" id="candidate-peek" hidden>
                            <i class="ti ti-user"></i>
                            <span>Applied for <strong data-peek="job"></strong></span>
                            <span class="tag" data-peek="stage"></span>
                            <span class="text-muted" data-peek="email"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card form-section tone-teal">
            <div class="form-section-head">
                <span class="form-section-icon"><i class="ti ti-cash"></i></span>
                <div>
                    <h5 class="form-section-title">Compensation</h5>
                    <p class="form-section-sub">The salary you're offering.</p>
                </div>
            </div>
            <div class="form-section-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label" for="salary_offered">Salary offered</label>
                        <div class="input-group has-validation">
                            <select class="form-select input-group-currency" id="currency" name="currency" aria-label="Currency">
                                @foreach ($currencies as $code)
                                    <option value="{{ $code }}" @selected($currentCurrency === $code)>{{ $code }}</option>
                                @endforeach
                            </select>
                            <input type="number" step="0.01" min="0" class="form-control @error('salary_offered') is-invalid @enderror" id="salary_offered" name="salary_offered" value="{{ old('salary_offered', $offer->salary_offered) }}" placeholder="0.00">
                            @error('salary_offered')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        @error('currency')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        <div class="form-hint" id="salary-band" hidden><i class="ti ti-info-circle"></i> <span></span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card form-section tone-pink">
            <div class="form-section-head">
                <span class="form-section-icon"><i class="ti ti-calendar-event"></i></span>
                <div>
                    <h5 class="form-section-title">Dates</h5>
                    <p class="form-section-sub">When the offer goes out, and when they start.</p>
                </div>
            </div>
            <div class="form-section-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="offer_date">Offer date</label>
                        <input type="date" class="form-control @error('offer_date') is-invalid @enderror" id="offer_date" name="offer_date" value="{{ old('offer_date', optional($offer->offer_date)->format('Y-m-d') ?? ($isEditing ? '' : now()->format('Y-m-d'))) }}">
                        @error('offer_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="joining_date">Joining date</label>
                        <input type="date" class="form-control @error('joining_date') is-invalid @enderror" id="joining_date" name="joining_date" value="{{ old('joining_date', optional($offer->joining_date)->format('Y-m-d')) }}">
                        @error('joining_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="quick-chips" id="joining-chips">
                            <button type="button" class="quick-chip" data-days="15">+15 days</button>
                            <button type="button" class="quick-chip" data-days="30">+30 days</button>
                            <button type="button" class="quick-chip" data-days="60">+60 days</button>
                            <button type="button" class="quick-chip" data-days="90">+90 days</button>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-hint" id="notice-gap" hidden><i class="ti ti-clock"></i> <span></span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card form-section tone-amber">
            <div class="form-section-head">
                <span class="form-section-icon"><i class="ti ti-notes"></i></span>
                <div>
                    <h5 class="form-section-title">Notes</h5>
                    <p class="form-section-sub">Benefits, conditions, negotiation history…</p>
                </div>
            </div>
            <div class="form-section-body">
                <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="5" maxlength="2000" placeholder="Anything else about this offer" aria-label="Notes">{{ old('notes', $offer->notes) }}</textarea>
                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-hint justify-content-end"><span id="notes-count">{{ mb_strlen((string) old('notes', $offer->notes)) }}</span>/2000</div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="offer-summary">
            <div class="offer-summary-label">Offer summary</div>
            <div class="offer-summary-name" id="sum-candidate">{{ $offer->candidate?->name ?? 'Choose a candidate' }}</div>
            <div class="offer-summary-role" id="sum-position">{{ $offer->jobPosition?->title ?? 'No position selected' }}</div>
            <div class="offer-summary-salary" id="sum-salary">—</div>
            <div class="offer-summary-row"><i class="ti ti-calendar-event"></i> Joining <strong id="sum-joining">not set</strong></div>
        </div>

        <div class="card form-section tone-violet">
            <div class="form-section-head form-section-head-compact">
                <span class="form-section-icon form-section-icon-sm"><i class="ti ti-activity"></i></span>
                <h5 class="form-section-title">Status <span class="text-danger">*</span></h5>
            </div>
            <div class="form-section-body">
                <div class="status-options" role="radiogroup" aria-label="Offer status">
                    @foreach (\App\Models\OfferLetter::STATUSES as $status)
                        @php $option = $statusOptions[$status] ?? ['label' => Str::headline($status), 'tone' => 'muted', 'hint' => '']; @endphp
                        <label class="status-option">
                            <input type="radio" name="status" value="{{ $status }}" @checked($currentStatus === $status) required>
                            <span class="status-option-body">
                                <span class="status-dot status-dot-{{ $option['tone'] }}">{{ $option['label'] }}</span>
                                <span class="status-option-hint">{{ $option['hint'] }}</span>
                            </span>
                            <i class="ti ti-circle-check status-option-check"></i>
                        </label>
                    @endforeach
                </div>
                @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
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
                        <dd>{{ $offer->created_at?->format('M j, Y') ?? '—' }}</dd>
                        <dt>Last updated</dt>
                        <dd>{{ $offer->updated_at?->diffForHumans() ?? '—' }}</dd>
                    </dl>
                </div>
            </div>
        @endif
    </div>
</div>

<div class="record-footer">
    <a href="{{ $cancelUrl }}" class="btn btn-light px-4">Cancel</a>
    <button type="submit" class="btn btn-de-primary text-light px-4">
        <i class="ti ti-check font-14 me-1"></i> {{ $isEditing ? 'Save changes' : 'Create offer' }}
    </button>
</div>

@push('scripts')
    <script>
        (function () {
            var details = @json($candidateDetails ?? []);
            var stageTones = @json($stageTones);
            var $ = function (id) { return document.getElementById(id); };

            var candidate = $('candidate_id'), position = $('job_position_id'), salary = $('salary_offered'),
                currency = $('currency'), offerDate = $('offer_date'), joining = $('joining_date'),
                peek = $('candidate-peek'), band = $('salary-band'), gap = $('notice-gap');

            function fmt(n) {
                return Number(n).toLocaleString(undefined, { maximumFractionDigits: 2 });
            }
            function fmtDate(value) {
                if (!value) return null;
                var d = new Date(value + 'T00:00:00');
                return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
            }

            function updateCandidate(prefill) {
                var info = details[candidate.value];
                peek.hidden = !info;
                if (info) {
                    peek.querySelector('[data-peek="job"]').textContent = info.job || 'General application';
                    var stage = peek.querySelector('[data-peek="stage"]');
                    stage.className = 'tag tone-' + (stageTones[info.stage] || 'slate');
                    stage.textContent = info.stage.charAt(0).toUpperCase() + info.stage.slice(1);
                    peek.querySelector('[data-peek="email"]').textContent = info.email || '';
                    if (prefill && info.job_position_id && !position.value) {
                        position.value = String(info.job_position_id);
                    }
                }
                // Salary band hint from the applied-for position.
                if (info && (info.min_salary || info.max_salary)) {
                    band.hidden = false;
                    band.querySelector('span').textContent = 'Position band: ' + currency.value + ' '
                        + (info.min_salary ? fmt(info.min_salary) : '—') + ' – ' + (info.max_salary ? fmt(info.max_salary) : '—');
                } else {
                    band.hidden = true;
                }
                updateSummary();
            }

            function updateSummary() {
                $('sum-candidate').textContent = candidate.value ? candidate.options[candidate.selectedIndex].text : 'Choose a candidate';
                $('sum-position').textContent = position.value ? position.options[position.selectedIndex].text : 'No position selected';
                $('sum-salary').textContent = salary.value ? currency.value + ' ' + fmt(salary.value) : '—';
                $('sum-joining').textContent = fmtDate(joining.value) || 'not set';

                if (offerDate.value && joining.value) {
                    var days = Math.round((new Date(joining.value) - new Date(offerDate.value)) / 86400000);
                    gap.hidden = false;
                    gap.querySelector('span').textContent = days >= 0
                        ? days + ' days between offer and joining'
                        : 'Joining date is before the offer date';
                    gap.classList.toggle('text-danger', days < 0);
                } else {
                    gap.hidden = true;
                }
            }

            candidate.addEventListener('change', function () { updateCandidate(true); });
            [position, salary, currency, offerDate, joining].forEach(function (el) {
                el.addEventListener('input', updateSummary);
                el.addEventListener('change', updateSummary);
            });
            updateCandidate(false);

            // Joining quick-picks: offer date (or today) + N days.
            function pad(n) { return String(n).padStart(2, '0'); }
            document.querySelectorAll('#joining-chips .quick-chip').forEach(function (chip) {
                chip.addEventListener('click', function () {
                    var base = offerDate.value ? new Date(offerDate.value + 'T00:00:00') : new Date();
                    base.setDate(base.getDate() + parseInt(chip.dataset.days, 10));
                    joining.value = base.getFullYear() + '-' + pad(base.getMonth() + 1) + '-' + pad(base.getDate());
                    updateSummary();
                });
            });

            var notes = $('notes'), counter = $('notes-count');
            notes.addEventListener('input', function () { counter.textContent = notes.value.length; });
        })();
    </script>
@endpush
