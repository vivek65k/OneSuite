@php
    $isEditing = $interview->exists;
    $currentStatus = old('status', $interview->status ?: 'scheduled');
    $statusOptions = [
        'scheduled' => ['tone' => 'info', 'hint' => 'Confirmed with the candidate'],
        'rescheduled' => ['tone' => 'warning', 'hint' => 'Moved to a new time'],
        'completed' => ['tone' => 'success', 'hint' => 'Interview took place'],
        'cancelled' => ['tone' => 'muted', 'hint' => 'Will not take place'],
    ];
    $stageTones = ['applied' => 'slate', 'screening' => 'sky', 'interview' => 'violet', 'offer' => 'amber', 'hired' => 'success', 'rejected' => 'danger'];

    $roundSuggestions = ['HR screening', 'Technical round 1', 'Technical round 2', 'Assignment review', 'Manager round', 'Culture fit', 'Final round'];

    $scheduledValue = old('scheduled_at', optional($interview->scheduled_at)->format('Y-m-d\TH:i'));
    $selectedCandidate = (int) old('candidate_id', $interview->candidate_id);
    $cancelUrl = route('admin.interviews.index');
@endphp

<div class="record-header">
    <a href="{{ $cancelUrl }}" class="btn-icon record-back" title="Back"><i class="ti ti-arrow-left font-18"></i></a>
    <span class="list-avatar list-avatar-square record-avatar tone-pink" aria-hidden="true"><i class="ti ti-calendar-event font-20"></i></span>
    <div class="record-header-text">
        <div class="record-header-title" id="record-title">
            {{ $isEditing ? $interview->round.' · '.($interview->candidate?->name ?? 'Candidate') : 'Schedule an interview' }}
        </div>
        <div class="record-header-meta">
            @if ($isEditing)
                <span class="status-dot status-dot-{{ $statusOptions[$interview->status]['tone'] ?? 'muted' }}">{{ Str::headline($interview->status) }}</span>
                <span><i class="ti ti-clock"></i> {{ $interview->scheduled_at?->format('D, M j · g:i A') }} ({{ $interview->scheduled_at?->diffForHumans() }})</span>
            @else
                Pick a candidate, a round and a time - everything else is optional.
            @endif
        </div>
    </div>
    <div class="record-header-actions">
        <a href="{{ $cancelUrl }}" class="btn btn-light btn-sm px-3">Cancel</a>
        <button type="submit" class="btn btn-de-primary btn-sm text-light px-3">
            <i class="ti ti-check font-14 me-1"></i> {{ $isEditing ? 'Save changes' : 'Schedule' }}
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
                <span class="form-section-icon"><i class="ti ti-users"></i></span>
                <div>
                    <h5 class="form-section-title">Candidate &amp; round</h5>
                    <p class="form-section-sub">Who is being interviewed, and for which round.</p>
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
                        @if ($candidates->isEmpty())
                            <div class="form-hint"><i class="ti ti-info-circle"></i> No candidates yet - <a href="{{ route('admin.candidates.create') }}">add one first</a>.</div>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="round">Round <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('round') is-invalid @enderror" id="round" name="round" value="{{ old('round', $interview->round) }}" list="round-suggestions" placeholder="e.g. Technical round 1" required>
                        <datalist id="round-suggestions">
                            @foreach ($roundSuggestions as $suggestion)
                                <option value="{{ $suggestion }}"></option>
                            @endforeach
                        </datalist>
                        @error('round')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="quick-chips" data-target="round">
                            @foreach (array_slice($roundSuggestions, 0, 5) as $suggestion)
                                <button type="button" class="quick-chip" data-value="{{ $suggestion }}">{{ $suggestion }}</button>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="candidate-peek" id="candidate-peek" @if (! $selectedCandidate) hidden @endif>
                            <i class="ti ti-id"></i>
                            <span>Applied for <strong data-peek="job">—</strong></span>
                            <span class="tag" data-peek="stage"></span>
                            <span class="text-muted" data-peek="email"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card form-section tone-pink">
            <div class="form-section-head">
                <span class="form-section-icon"><i class="ti ti-calendar-time"></i></span>
                <div>
                    <h5 class="form-section-title">Schedule</h5>
                    <p class="form-section-sub">When and where the interview happens.</p>
                </div>
            </div>
            <div class="form-section-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="scheduled_at">Date &amp; time <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control @error('scheduled_at') is-invalid @enderror" id="scheduled_at" name="scheduled_at" value="{{ $scheduledValue }}" required>
                        @error('scheduled_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="quick-chips" id="time-chips">
                            <button type="button" class="quick-chip" data-day="0" data-time="15:00">Today 3 PM</button>
                            <button type="button" class="quick-chip" data-day="1" data-time="10:00">Tomorrow 10 AM</button>
                            <button type="button" class="quick-chip" data-day="1" data-time="14:00">Tomorrow 2 PM</button>
                            <button type="button" class="quick-chip" data-day="next-monday" data-time="11:00">Next Mon 11 AM</button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="location">Location or meeting link</label>
                        <div class="field-icon">
                            <i class="ti ti-map-pin" id="location-icon"></i>
                            <input type="text" class="form-control @error('location') is-invalid @enderror" id="location" name="location" value="{{ old('location', $interview->location) }}" placeholder="Room 4B, or https://meet.google.com/…">
                            @error('location')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-hint" id="location-hint"><i class="ti ti-info-circle"></i> Paste a video link and a <strong>Join</strong> button appears on the schedule.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card form-section tone-teal">
            <div class="form-section-head">
                <span class="form-section-icon"><i class="ti ti-user-check"></i></span>
                <div>
                    <h5 class="form-section-title">Interviewer</h5>
                    <p class="form-section-sub">Who will conduct this round.</p>
                </div>
            </div>
            <div class="form-section-body">
                <label class="form-label" for="interviewer_name">Interviewer name</label>
                <input type="text" class="form-control @error('interviewer_name') is-invalid @enderror" id="interviewer_name" name="interviewer_name" value="{{ old('interviewer_name', $interview->interviewer_name) }}" list="interviewer-suggestions" placeholder="e.g. Priya Sharma (Engineering Manager)">
                <datalist id="interviewer-suggestions">
                    @foreach ($interviewers ?? [] as $name)
                        <option value="{{ $name }}"></option>
                    @endforeach
                </datalist>
                @error('interviewer_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                <div class="status-options" role="radiogroup" aria-label="Interview status">
                    @foreach (\App\Models\Interview::STATUSES as $status)
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

        @if ($isEditing)
            <div class="card form-section tone-slate">
                <div class="form-section-head form-section-head-compact">
                    <span class="form-section-icon form-section-icon-sm"><i class="ti ti-info-square"></i></span>
                    <h5 class="form-section-title">Record info</h5>
                </div>
                <div class="form-section-body">
                    <dl class="record-meta">
                        <dt>Feedback</dt>
                        <dd>{{ $interview->feedback()->count() }}</dd>
                        <dt>Created</dt>
                        <dd>{{ $interview->created_at?->format('M j, Y') ?? '—' }}</dd>
                        <dt>Last updated</dt>
                        <dd>{{ $interview->updated_at?->diffForHumans() ?? '—' }}</dd>
                    </dl>
                </div>
            </div>
        @endif
    </div>
</div>

<div class="record-footer">
    <a href="{{ $cancelUrl }}" class="btn btn-light px-4">Cancel</a>
    <button type="submit" class="btn btn-de-primary text-light px-4">
        <i class="ti ti-check font-14 me-1"></i> {{ $isEditing ? 'Save changes' : 'Schedule' }}
    </button>
</div>

@push('scripts')
    <script>
        (function () {
            var details = @json($candidateDetails ?? []);
            var stageTones = @json($stageTones);

            // Candidate "peek": applied-for, stage and email for the selected candidate.
            var select = document.getElementById('candidate_id');
            var peek = document.getElementById('candidate-peek');
            function updatePeek() {
                var info = details[select.value];
                peek.hidden = !info;
                if (!info) return;
                peek.querySelector('[data-peek="job"]').textContent = info.job || 'General application';
                var stage = peek.querySelector('[data-peek="stage"]');
                stage.className = 'tag tone-' + (stageTones[info.stage] || 'slate');
                stage.textContent = info.stage.charAt(0).toUpperCase() + info.stage.slice(1);
                peek.querySelector('[data-peek="email"]').textContent = info.email || '';
            }
            if (select && peek) {
                select.addEventListener('change', updatePeek);
                updatePeek();
            }

            // Round quick-picks fill the round field.
            document.querySelectorAll('.quick-chips[data-target] .quick-chip').forEach(function (chip) {
                chip.addEventListener('click', function () {
                    var input = document.getElementById(chip.parentElement.dataset.target);
                    input.value = chip.dataset.value;
                    input.dispatchEvent(new Event('input'));
                });
            });

            // Time quick-picks fill the datetime-local field (local time).
            var when = document.getElementById('scheduled_at');
            function pad(n) { return String(n).padStart(2, '0'); }
            document.querySelectorAll('#time-chips .quick-chip').forEach(function (chip) {
                chip.addEventListener('click', function () {
                    var date = new Date();
                    if (chip.dataset.day === 'next-monday') {
                        date.setDate(date.getDate() + ((8 - date.getDay()) % 7 || 7));
                    } else {
                        date.setDate(date.getDate() + parseInt(chip.dataset.day, 10));
                    }
                    when.value = date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate()) + 'T' + chip.dataset.time;
                });
            });

            // Location icon switches to a camera for meeting links.
            var locationInput = document.getElementById('location');
            var icon = document.getElementById('location-icon');
            function updateLocationIcon() {
                var isLink = /^https?:\/\//i.test(locationInput.value.trim());
                icon.className = 'ti ' + (isLink ? 'ti-video' : 'ti-map-pin');
            }
            if (locationInput && icon) {
                locationInput.addEventListener('input', updateLocationIcon);
                updateLocationIcon();
            }
        })();
    </script>
@endpush
