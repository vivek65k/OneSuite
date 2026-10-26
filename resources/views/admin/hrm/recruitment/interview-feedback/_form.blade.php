@php
    $isEditing = $feedbackEntry->exists;
    $selectedInterview = (int) old('interview_id', $feedbackEntry->interview_id);
    $currentRating = (int) old('rating', $feedbackEntry->rating);
    $currentRecommendation = old('recommendation', $feedbackEntry->recommendation ?: 'maybe');

    $recommendationOptions = [
        'hire' => ['label' => 'Hire', 'tone' => 'success', 'icon' => 'ti-thumb-up', 'hint' => 'Strong fit - move forward'],
        'maybe' => ['label' => 'Maybe', 'tone' => 'amber', 'icon' => 'ti-help', 'hint' => 'Mixed signals - needs another look'],
        'no_hire' => ['label' => 'No hire', 'tone' => 'danger', 'icon' => 'ti-thumb-down', 'hint' => 'Not the right fit'],
    ];
    $ratingLabels = [1 => 'Poor', 2 => 'Below average', 3 => 'Average', 4 => 'Good', 5 => 'Excellent'];
    $commentPrompts = ['Strengths:', 'Concerns:', 'Technical depth:', 'Communication:', 'Culture fit:'];
    $cancelUrl = route('admin.interview-feedback.index');
@endphp

<div class="record-header">
    <a href="{{ $cancelUrl }}" class="btn-icon record-back" title="Back"><i class="ti ti-arrow-left font-18"></i></a>
    <span class="list-avatar list-avatar-square record-avatar tone-amber" aria-hidden="true"><i class="ti ti-star font-20"></i></span>
    <div class="record-header-text">
        <div class="record-header-title" id="record-title">
            @if ($isEditing && $feedbackEntry->interview)
                Feedback · {{ $feedbackEntry->interview->candidate?->name }} ({{ $feedbackEntry->interview->round }})
            @else
                Interview scorecard
            @endif
        </div>
        <div class="record-header-meta">
            @if ($isEditing)
                <span class="verdict-pill tone-{{ $recommendationOptions[$feedbackEntry->recommendation]['tone'] ?? 'slate' }}">
                    <i class="ti {{ $recommendationOptions[$feedbackEntry->recommendation]['icon'] ?? 'ti-point' }}"></i>
                    {{ $recommendationOptions[$feedbackEntry->recommendation]['label'] ?? Str::headline($feedbackEntry->recommendation) }}
                </span>
                <span>by {{ $feedbackEntry->interviewer_name }} · {{ $feedbackEntry->created_at?->diffForHumans() }}</span>
            @else
                Rate the candidate and give your hiring recommendation.
            @endif
        </div>
    </div>
    <div class="record-header-actions">
        <a href="{{ $cancelUrl }}" class="btn btn-light btn-sm px-3">Cancel</a>
        <button type="submit" class="btn btn-de-primary btn-sm text-light px-3">
            <i class="ti ti-check font-14 me-1"></i> {{ $isEditing ? 'Save changes' : 'Submit feedback' }}
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
                <span class="form-section-icon"><i class="ti ti-calendar-event"></i></span>
                <div>
                    <h5 class="form-section-title">Interview</h5>
                    <p class="form-section-sub">Which interview this feedback is for, and who gave it.</p>
                </div>
            </div>
            <div class="form-section-body">
                <div class="row g-3">
                    <div class="col-md-7">
                        <label class="form-label" for="interview_id">Interview <span class="text-danger">*</span></label>
                        <select class="form-select @error('interview_id') is-invalid @enderror" id="interview_id" name="interview_id" required>
                            <option value="">Select an interview</option>
                            @foreach ($interviews as $id => $label)
                                <option value="{{ $id }}" @selected($selectedInterview === $id)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('interview_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-5">
                        <label class="form-label" for="interviewer_name">Interviewer <span class="text-danger">*</span></label>
                        <div class="field-icon">
                            <i class="ti ti-user-check"></i>
                            <input type="text" class="form-control @error('interviewer_name') is-invalid @enderror" id="interviewer_name" name="interviewer_name" value="{{ old('interviewer_name', $feedbackEntry->interviewer_name) }}" placeholder="Who conducted it" required>
                            @error('interviewer_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="candidate-peek" id="interview-peek" hidden>
                            <i class="ti ti-user"></i>
                            <strong data-peek="candidate"></strong>
                            <span class="text-muted" data-peek="job"></span>
                            <span class="tag tone-violet" data-peek="round"></span>
                            <span class="text-muted"><i class="ti ti-clock"></i> <span data-peek="when"></span></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card form-section tone-amber">
            <div class="form-section-head">
                <span class="form-section-icon"><i class="ti ti-star"></i></span>
                <div>
                    <h5 class="form-section-title">Overall rating</h5>
                    <p class="form-section-sub">Your overall impression of the candidate in this round.</p>
                </div>
            </div>
            <div class="form-section-body">
                <div class="star-picker" role="radiogroup" aria-label="Overall rating">
                    {{-- Rendered 5 to 1 so the CSS ~ sibling selector can light up every lower star on hover --}}
                    @for ($i = 5; $i >= 1; $i--)
                        <input type="radio" id="rating-{{ $i }}" name="rating" value="{{ $i }}" @checked($currentRating === $i)>
                        <label for="rating-{{ $i }}" title="{{ $ratingLabels[$i] }}"><i class="ti ti-star"></i></label>
                    @endfor
                </div>
                <div class="star-picker-meta">
                    <span class="star-picker-label" id="rating-label">{{ $currentRating ? $currentRating.' / 5 · '.$ratingLabels[$currentRating] : 'Not rated yet' }}</span>
                    <button type="button" class="btn btn-link btn-sm p-0 text-muted" id="rating-clear" @if (! $currentRating) hidden @endif>Clear rating</button>
                    <input type="radio" name="rating" value="" id="rating-none" class="d-none" @checked(! $currentRating)>
                </div>
                @error('rating')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="card form-section tone-pink">
            <div class="form-section-head">
                <span class="form-section-icon"><i class="ti ti-message-circle"></i></span>
                <div>
                    <h5 class="form-section-title">Comments</h5>
                    <p class="form-section-sub">Evidence behind your rating - specific examples help the hiring panel most.</p>
                </div>
            </div>
            <div class="form-section-body">
                <div class="quick-chips mt-0 mb-2" id="comment-prompts">
                    @foreach ($commentPrompts as $prompt)
                        <button type="button" class="quick-chip" data-value="{{ $prompt }}"><i class="ti ti-plus"></i> {{ rtrim($prompt, ':') }}</button>
                    @endforeach
                </div>
                <textarea class="form-control @error('comments') is-invalid @enderror" id="comments" name="comments" rows="7" maxlength="2000" placeholder="What stood out, positively or negatively?" aria-label="Comments">{{ old('comments', $feedbackEntry->comments) }}</textarea>
                @error('comments')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-hint justify-content-end"><span id="comments-count">{{ mb_strlen((string) old('comments', $feedbackEntry->comments)) }}</span>/2000</div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card form-section tone-violet">
            <div class="form-section-head form-section-head-compact">
                <span class="form-section-icon form-section-icon-sm"><i class="ti ti-scale"></i></span>
                <h5 class="form-section-title">Recommendation <span class="text-danger">*</span></h5>
            </div>
            <div class="form-section-body">
                <div class="verdict-options" role="radiogroup" aria-label="Recommendation">
                    @foreach (\App\Models\InterviewFeedback::RECOMMENDATIONS as $recommendation)
                        @php $option = $recommendationOptions[$recommendation] ?? ['label' => Str::headline($recommendation), 'tone' => 'slate', 'icon' => 'ti-point', 'hint' => '']; @endphp
                        <label class="verdict-option tone-{{ $option['tone'] }}">
                            <input type="radio" name="recommendation" value="{{ $recommendation }}" @checked($currentRecommendation === $recommendation) required>
                            <span class="verdict-option-icon"><i class="ti {{ $option['icon'] }}"></i></span>
                            <span class="verdict-option-body">
                                <span class="verdict-option-title">{{ $option['label'] }}</span>
                                <span class="verdict-option-hint">{{ $option['hint'] }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('recommendation')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="card form-section tone-sky">
            <div class="form-section-head form-section-head-compact">
                <span class="form-section-icon form-section-icon-sm"><i class="ti ti-ruler-2"></i></span>
                <h5 class="form-section-title">Rating guide</h5>
            </div>
            <div class="form-section-body">
                <ul class="rating-guide">
                    @foreach (array_reverse($ratingLabels, true) as $stars => $label)
                        <li>
                            <span class="star-rating">
                                @for ($i = 1; $i <= 5; $i++)
                                    <i class="ti ti-star {{ $i <= $stars ? 'is-on' : '' }}"></i>
                                @endfor
                            </span>
                            <span>{{ $label }}</span>
                        </li>
                    @endforeach
                </ul>
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
                        <dt>Submitted</dt>
                        <dd>{{ $feedbackEntry->created_at?->format('M j, Y') ?? '—' }}</dd>
                        <dt>Last updated</dt>
                        <dd>{{ $feedbackEntry->updated_at?->diffForHumans() ?? '—' }}</dd>
                    </dl>
                </div>
            </div>
        @endif
    </div>
</div>

<div class="record-footer">
    <a href="{{ $cancelUrl }}" class="btn btn-light px-4">Cancel</a>
    <button type="submit" class="btn btn-de-primary text-light px-4">
        <i class="ti ti-check font-14 me-1"></i> {{ $isEditing ? 'Save changes' : 'Submit feedback' }}
    </button>
</div>

@push('scripts')
    <script>
        (function () {
            var details = @json($interviewDetails ?? []);
            var ratingLabels = @json($ratingLabels);

            // Interview peek + interviewer prefill from the chosen interview.
            var select = document.getElementById('interview_id');
            var peek = document.getElementById('interview-peek');
            var interviewer = document.getElementById('interviewer_name');
            function updatePeek(prefill) {
                var info = details[select.value];
                peek.hidden = !info;
                if (!info) return;
                peek.querySelector('[data-peek="candidate"]').textContent = info.candidate || 'Candidate';
                peek.querySelector('[data-peek="job"]').textContent = info.job ? '· ' + info.job : '';
                peek.querySelector('[data-peek="round"]').textContent = info.round || '';
                peek.querySelector('[data-peek="when"]').textContent = info.when || '';
                if (prefill && info.interviewer && !interviewer.value.trim()) {
                    interviewer.value = info.interviewer;
                }
            }
            if (select && peek) {
                select.addEventListener('change', function () { updatePeek(true); });
                updatePeek(true);
            }

            // Star picker label + clear.
            var label = document.getElementById('rating-label');
            var clear = document.getElementById('rating-clear');
            document.querySelectorAll('.star-picker input').forEach(function (input) {
                input.addEventListener('change', function () {
                    label.textContent = input.value + ' / 5 · ' + ratingLabels[input.value];
                    clear.hidden = false;
                });
            });
            clear.addEventListener('click', function () {
                document.getElementById('rating-none').checked = true;
                label.textContent = 'Not rated yet';
                clear.hidden = true;
            });

            // Comment prompts append a heading line.
            var comments = document.getElementById('comments');
            var counter = document.getElementById('comments-count');
            document.querySelectorAll('#comment-prompts .quick-chip').forEach(function (chip) {
                chip.addEventListener('click', function () {
                    var prefix = comments.value && !comments.value.endsWith('\n') ? '\n' : '';
                    comments.value += prefix + chip.dataset.value + ' ';
                    comments.focus();
                    counter.textContent = comments.value.length;
                });
            });
            comments.addEventListener('input', function () { counter.textContent = comments.value.length; });
        })();
    </script>
@endpush
