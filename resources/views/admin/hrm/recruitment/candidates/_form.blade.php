@php
    $isEditing = $candidate->exists;
    $currentStage = old('stage', $candidate->stage ?: 'applied');
    $stageOptions = [
        'applied' => ['tone' => 'slate', 'icon' => 'ti-inbox', 'hint' => 'Application received'],
        'screening' => ['tone' => 'sky', 'icon' => 'ti-file-search', 'hint' => 'Resume / phone screen'],
        'interview' => ['tone' => 'violet', 'icon' => 'ti-message-circle-2', 'hint' => 'In interview rounds'],
        'offer' => ['tone' => 'amber', 'icon' => 'ti-file-certificate', 'hint' => 'Offer extended'],
        'hired' => ['tone' => 'success', 'icon' => 'ti-award', 'hint' => 'Accepted and joining'],
        'rejected' => ['tone' => 'danger', 'icon' => 'ti-circle-x', 'hint' => 'Not moving forward'],
    ];

    $sourceSuggestions = collect(['LinkedIn', 'Referral', 'Careers page', 'Job portal', 'Walk-in', 'Recruitment agency'])
        ->merge($sources ?? [])
        ->unique(fn ($source) => Str::lower($source))
        ->values();

    $displayName = old('name', $candidate->name);
    $initials = collect(preg_split('/\s+/', trim((string) $displayName)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
    $cancelUrl = route('admin.candidates.index');
@endphp

<div class="record-header">
    <a href="{{ $cancelUrl }}" class="btn-icon record-back" title="Back"><i class="ti ti-arrow-left font-18"></i></a>
    <span class="list-avatar list-avatar-violet record-avatar" id="record-avatar" aria-hidden="true">
        @if ($initials)
            {{ $initials }}
        @else
            <i class="ti ti-users font-18"></i>
        @endif
    </span>
    <div class="record-header-text">
        <div class="record-header-title" id="record-title">{{ $displayName ?: 'New candidate' }}</div>
        <div class="record-header-meta">
            @if ($isEditing)
                <span class="tag tone-{{ $stageOptions[$candidate->stage]['tone'] ?? 'slate' }}">{{ Str::headline($candidate->stage) }}</span>
                @if ($candidate->jobPosition)
                    <span><i class="ti ti-briefcase"></i> {{ $candidate->jobPosition->title }}</span>
                @endif
            @else
                Only fields marked <span class="text-danger">*</span> are required.
            @endif
        </div>
    </div>
    <div class="record-header-actions">
        <a href="{{ $cancelUrl }}" class="btn btn-light btn-sm px-3">Cancel</a>
        <button type="submit" class="btn btn-de-primary btn-sm text-light px-3">
            <i class="ti ti-check font-14 me-1"></i> {{ $isEditing ? 'Save changes' : 'Add candidate' }}
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
                <span class="form-section-icon"><i class="ti ti-id"></i></span>
                <div>
                    <h5 class="form-section-title">Candidate details</h5>
                    <p class="form-section-sub">Who applied and how to reach them.</p>
                </div>
            </div>
            <div class="form-section-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="name">Full name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $candidate->name) }}" placeholder="First and last name" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="email">Email</label>
                        <div class="field-icon">
                            <i class="ti ti-mail"></i>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $candidate->email) }}" placeholder="name@example.com">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="phone">Phone</label>
                        <div class="field-icon">
                            <i class="ti ti-phone"></i>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $candidate->phone) }}" placeholder="+91 98765 43210">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card form-section tone-teal">
            <div class="form-section-head">
                <span class="form-section-icon"><i class="ti ti-briefcase"></i></span>
                <div>
                    <h5 class="form-section-title">Application</h5>
                    <p class="form-section-sub">The role they applied for and where they came from.</p>
                </div>
            </div>
            <div class="form-section-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="job_position_id">Applied for</label>
                        <select class="form-select @error('job_position_id') is-invalid @enderror" id="job_position_id" name="job_position_id">
                            <option value="">General application</option>
                            @foreach ($jobPositions as $id => $title)
                                <option value="{{ $id }}" @selected((int) old('job_position_id', $candidate->job_position_id) === $id)>{{ $title }}</option>
                            @endforeach
                        </select>
                        @error('job_position_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="source">Source</label>
                        <input type="text" class="form-control @error('source') is-invalid @enderror" id="source" name="source" value="{{ old('source', $candidate->source) }}" list="candidate-sources" placeholder="e.g. LinkedIn">
                        <datalist id="candidate-sources">
                            @foreach ($sourceSuggestions as $source)
                                <option value="{{ $source }}"></option>
                            @endforeach
                        </datalist>
                        @error('source')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="applied_date">Applied on</label>
                        <input type="date" class="form-control @error('applied_date') is-invalid @enderror" id="applied_date" name="applied_date" value="{{ old('applied_date', optional($candidate->applied_date)->format('Y-m-d') ?? ($isEditing ? '' : now()->format('Y-m-d'))) }}">
                        @error('applied_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card form-section tone-amber">
            <div class="form-section-head">
                <span class="form-section-icon"><i class="ti ti-file-text"></i></span>
                <div>
                    <h5 class="form-section-title">Resume</h5>
                    <p class="form-section-sub">PDF or Word document, up to 10 MB.</p>
                </div>
            </div>
            <div class="form-section-body">
                @if ($candidate->resume_path)
                    <div class="file-current">
                        <span class="file-current-icon"><i class="ti ti-file-text"></i></span>
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-semibold text-truncate">{{ basename($candidate->resume_path) }}</div>
                            <div class="text-muted font-12">Current resume - upload a new file below to replace it.</div>
                        </div>
                        <a href="{{ asset('storage/'.$candidate->resume_path) }}" target="_blank" rel="noopener" class="btn btn-light btn-sm"><i class="ti ti-external-link font-14 me-1"></i> Open</a>
                    </div>
                @endif
                <label class="file-drop @error('resume') is-invalid @enderror" for="resume">
                    <i class="ti ti-cloud-upload"></i>
                    <span class="file-drop-title" id="resume-label">{{ $candidate->resume_path ? 'Choose a new file to replace it' : 'Click to choose a resume' }}</span>
                    <span class="file-drop-sub">or drag and drop it here</span>
                    <input type="file" id="resume" name="resume" accept=".pdf,.doc,.docx,.rtf,.txt">
                </label>
                @error('resume')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="card form-section tone-pink">
            <div class="form-section-head">
                <span class="form-section-icon"><i class="ti ti-notes"></i></span>
                <div>
                    <h5 class="form-section-title">Notes</h5>
                    <p class="form-section-sub">Impressions, salary expectations, notice period…</p>
                </div>
            </div>
            <div class="form-section-body">
                <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="5" maxlength="2000" placeholder="Add notes about this candidate" aria-label="Notes">{{ old('notes', $candidate->notes) }}</textarea>
                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-hint justify-content-end"><span id="notes-count">{{ mb_strlen((string) old('notes', $candidate->notes)) }}</span>/2000</div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card form-section tone-violet">
            <div class="form-section-head form-section-head-compact">
                <span class="form-section-icon form-section-icon-sm"><i class="ti ti-chart-arrows"></i></span>
                <h5 class="form-section-title">Hiring stage <span class="text-danger">*</span></h5>
            </div>
            <div class="form-section-body">
                <div class="stage-stepper" role="radiogroup" aria-label="Hiring stage">
                    @foreach (\App\Models\Candidate::STAGES as $stage)
                        @php $option = $stageOptions[$stage] ?? ['tone' => 'slate', 'icon' => 'ti-point', 'hint' => '']; @endphp
                        <label class="stage-step tone-{{ $option['tone'] }} {{ $stage === 'rejected' ? 'stage-step-detached' : '' }}">
                            <input type="radio" name="stage" value="{{ $stage }}" @checked($currentStage === $stage) required>
                            <span class="stage-step-icon"><i class="ti {{ $option['icon'] }}"></i></span>
                            <span class="stage-step-body">
                                <span class="stage-step-title">{{ Str::headline($stage) }}</span>
                                <span class="stage-step-hint">{{ $option['hint'] }}</span>
                            </span>
                            <i class="ti ti-circle-check stage-step-check"></i>
                        </label>
                    @endforeach
                </div>
                @error('stage')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
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
                        <dt>Interviews</dt>
                        <dd>{{ $candidate->interviews()->count() }}</dd>
                        <dt>Added</dt>
                        <dd>{{ $candidate->created_at?->format('M j, Y') ?? '—' }}</dd>
                        <dt>Last updated</dt>
                        <dd>{{ $candidate->updated_at?->diffForHumans() ?? '—' }}</dd>
                    </dl>
                </div>
            </div>
        @endif
    </div>
</div>

<div class="record-footer">
    <a href="{{ $cancelUrl }}" class="btn btn-light px-4">Cancel</a>
    <button type="submit" class="btn btn-de-primary text-light px-4">
        <i class="ti ti-check font-14 me-1"></i> {{ $isEditing ? 'Save changes' : 'Add candidate' }}
    </button>
</div>

@push('scripts')
    <script>
        (function () {
            var nameInput = document.getElementById('name');
            var title = document.getElementById('record-title');
            var avatar = document.getElementById('record-avatar');
            if (nameInput && title && avatar) {
                nameInput.addEventListener('input', function () {
                    var name = nameInput.value.trim();
                    title.textContent = name || @json($isEditing ? $candidate->name : 'New candidate');
                    var initials = name.split(/\s+/).filter(Boolean).slice(0, 2).map(function (part) {
                        return part.charAt(0).toUpperCase();
                    }).join('');
                    avatar.innerHTML = initials || '<i class="ti ti-users font-18"></i>';
                });
            }

            var resume = document.getElementById('resume');
            var resumeLabel = document.getElementById('resume-label');
            if (resume && resumeLabel) {
                resume.addEventListener('change', function () {
                    if (resume.files.length) {
                        resumeLabel.textContent = resume.files[0].name;
                        resume.closest('.file-drop').classList.add('has-file');
                    }
                });
            }

            var notes = document.getElementById('notes');
            var counter = document.getElementById('notes-count');
            if (notes && counter) {
                notes.addEventListener('input', function () {
                    counter.textContent = notes.value.length;
                });
            }
        })();
    </script>
@endpush
