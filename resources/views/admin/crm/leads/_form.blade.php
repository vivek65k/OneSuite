@php
    use Illuminate\Support\Str;

    $isEditing = $lead->exists;
    $currentStatus = old('status', $lead->status ?: 'new');
    $statusOptions = [
        'new' => ['tone' => 'info', 'hint' => 'Just came in, not yet worked'],
        'contacted' => ['tone' => 'warning', 'hint' => 'Reached out, awaiting response'],
        'qualified' => ['tone' => 'success', 'hint' => 'Good fit, ready to convert'],
        'unqualified' => ['tone' => 'danger', 'hint' => 'Not a fit right now'],
        'converted' => ['tone' => 'success', 'hint' => 'Already became a contact'],
    ];

    $displayName = old('name', $lead->name);
    $initials = collect(preg_split('/\s+/', trim((string) $displayName)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
    $cancelUrl = route('admin.crm.leads.index');
@endphp

<div class="record-header">
    <a href="{{ $cancelUrl }}" class="btn-icon record-back" title="Back"><i class="ti ti-arrow-left font-18"></i></a>
    <span class="list-avatar list-avatar-pink record-avatar" id="record-avatar" aria-hidden="true">
        @if ($initials)
            {{ $initials }}
        @else
            <i class="ti ti-target font-18"></i>
        @endif
    </span>
    <div class="record-header-text">
        <div class="record-header-title" id="record-title">{{ $displayName ?: 'New lead' }}</div>
        <div class="record-header-meta">
            @if ($isEditing)
                <span class="status-dot status-dot-{{ $statusOptions[$lead->status]['tone'] ?? 'muted' }}">{{ \App\Models\Crm\Lead::STATUSES[$lead->status] ?? $lead->status }}</span>
                @if ($lead->source)
                    <span>via {{ $lead->source->name }}</span>
                @endif
            @else
                Fill in the details below - only Name is required.
            @endif
        </div>
    </div>
    <div class="record-header-actions">
        <a href="{{ $cancelUrl }}" class="btn btn-light btn-sm px-3">Cancel</a>
        <button type="submit" class="btn btn-de-primary btn-sm text-light px-3">
            <i class="ti ti-check font-14 me-1"></i> {{ $isEditing ? 'Save changes' : 'Create lead' }}
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
                <span class="form-section-icon"><i class="ti ti-target"></i></span>
                <div>
                    <h5 class="form-section-title">Lead</h5>
                    <p class="form-section-sub">Who they are, where they came from, and what to do next.</p>
                </div>
            </div>
            <div class="form-section-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="name">Full name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ $displayName }}" placeholder="First and last name" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="company_name">Company</label>
                        <input type="text" class="form-control @error('company_name') is-invalid @enderror" id="company_name" name="company_name" value="{{ old('company_name', $lead->company_name) }}" placeholder="e.g. Acme Inc.">
                        @error('company_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="email">Email</label>
                        <div class="field-icon">
                            <i class="ti ti-mail"></i>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $lead->email) }}" placeholder="name@company.com">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="phone">Phone</label>
                        <div class="field-icon">
                            <i class="ti ti-phone"></i>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $lead->phone) }}" placeholder="+91 98765 43210">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="lead_source_id">Source</label>
                        <select class="form-select @error('lead_source_id') is-invalid @enderror" id="lead_source_id" name="lead_source_id">
                            <option value="">— None —</option>
                            @foreach ($sources as $id => $name)
                                <option value="{{ $id }}" @selected((string) old('lead_source_id', $lead->lead_source_id) === (string) $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('lead_source_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="assigned_to_employee_id">Assigned to</label>
                        <select class="form-select @error('assigned_to_employee_id') is-invalid @enderror" id="assigned_to_employee_id" name="assigned_to_employee_id">
                            <option value="">— Unassigned —</option>
                            @foreach ($employees as $id => $name)
                                <option value="{{ $id }}" @selected((string) old('assigned_to_employee_id', $lead->assigned_to_employee_id) === (string) $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('assigned_to_employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="branch_id">Branch</label>
                        <select class="form-select @error('branch_id') is-invalid @enderror" id="branch_id" name="branch_id">
                            <option value="">— None —</option>
                            @foreach ($branches as $id => $name)
                                <option value="{{ $id }}" @selected((string) old('branch_id', $lead->branch_id) === (string) $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="notes">Notes</label>
                        <textarea name="notes" id="notes" rows="3" class="form-control @error('notes') is-invalid @enderror" placeholder="Call notes, requirements, next steps…">{{ old('notes', $lead->notes) }}</textarea>
                        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="record-sidebar">
            <div class="card form-section tone-violet">
                <div class="form-section-head form-section-head-compact">
                    <span class="form-section-icon form-section-icon-sm"><i class="ti ti-activity"></i></span>
                    <h5 class="form-section-title">Status <span class="text-danger">*</span></h5>
                </div>
                <div class="form-section-body">
                    <div class="status-options" role="radiogroup" aria-label="Lead status">
                        @foreach (\App\Models\Crm\Lead::STATUSES as $key => $label)
                            <label class="status-option">
                                <input type="radio" name="status" value="{{ $key }}" @checked($currentStatus === $key) required>
                                <span class="status-option-body">
                                    <span class="status-dot status-dot-{{ $statusOptions[$key]['tone'] ?? 'muted' }}">{{ $label }}</span>
                                    <span class="status-option-hint">{{ $statusOptions[$key]['hint'] ?? '' }}</span>
                                </span>
                                <i class="ti ti-circle-check status-option-check"></i>
                            </label>
                        @endforeach
                    </div>
                    @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror

                    <hr class="my-3">

                    <label class="form-label" for="score">Score</label>
                    <div class="d-flex align-items-center gap-2">
                        <input type="range" min="0" max="100" name="score" id="score" value="{{ old('score', $lead->score ?: 0) }}" class="form-range" oninput="document.getElementById('score-value').textContent = this.value">
                        <span class="fw-semibold" id="score-value" style="width: 2.5rem">{{ old('score', $lead->score ?: 0) }}</span>
                    </div>
                    @error('score')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror

                    @if ($isEditing)
                        <hr class="my-3">
                        <dl class="record-meta mb-0">
                            <dt>Created</dt>
                            <dd>{{ $lead->created_at?->format('M j, Y g:i A') ?? '—' }}</dd>
                            <dt>Last updated</dt>
                            <dd>{{ $lead->updated_at?->diffForHumans() ?? '—' }}</dd>
                        </dl>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="record-footer">
    <a href="{{ $cancelUrl }}" class="btn btn-light px-4">Cancel</a>
    <button type="submit" class="btn btn-de-primary text-light px-4">
        <i class="ti ti-check font-14 me-1"></i> {{ $isEditing ? 'Save changes' : 'Create lead' }}
    </button>
</div>

@push('scripts')
    <script>
        (function () {
            var nameInput = document.getElementById('name');
            var title = document.getElementById('record-title');
            var avatar = document.getElementById('record-avatar');
            if (!nameInput || !title || !avatar) return;

            nameInput.addEventListener('input', function () {
                var name = nameInput.value.trim();
                title.textContent = name || @json($isEditing ? $lead->name : 'New lead');
                var initials = name.split(/\s+/).filter(Boolean).slice(0, 2).map(function (part) {
                    return part.charAt(0).toUpperCase();
                }).join('');
                avatar.innerHTML = initials || '<i class="ti ti-target font-18"></i>';
            });
        })();
    </script>
@endpush
