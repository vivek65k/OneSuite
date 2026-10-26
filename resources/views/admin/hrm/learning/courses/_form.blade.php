@php
    use App\Models\Course;

    $mode = old('delivery_mode', $course->delivery_mode ?: 'online');
    $isActive = (bool) old('is_active', $course->exists ? $course->is_active : true);
    $modeMeta = [
        'online' => ['ti-device-laptop', 'sky', 'Video call or e-learning, from anywhere.'],
        'offline' => ['ti-building', 'amber', 'In a room, face to face.'],
        'hybrid' => ['ti-switch-horizontal', 'violet', 'Part online, part in person.'],
    ];
    $duration = old('duration_hours', $course->duration_hours !== null ? rtrim(rtrim(number_format((float) $course->duration_hours, 2, '.', ''), '0'), '.') : '');
@endphp

@if ($errors->any())
    <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
@endif

<div class="row g-3">
    <div class="col-xl-8">
        <div class="card form-section tone-indigo mb-0">
            <div class="form-section-head">
                <span class="form-section-icon"><i class="ti ti-book"></i></span>
                <div class="flex-grow-1">
                    <h5 class="form-section-title">{{ $course->exists ? 'Edit course' : 'New course' }}</h5>
                    <p class="form-section-sub">What people will be trained on. Dates, trainers and attendees are set later, when you schedule a session.</p>
                </div>
            </div>
            <div class="form-section-body">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label" for="title">Course name</label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $course->title) }}" maxlength="255" placeholder="e.g. Workplace fire safety" required autofocus>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label" for="description">What it covers <span class="text-muted fw-normal">(optional)</span></label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" maxlength="2000" placeholder="Who it is for and what they will be able to do afterwards">{{ old('description', $course->description) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="category">Category <span class="text-muted fw-normal">(optional)</span></label>
                        <input type="text" class="form-control @error('category') is-invalid @enderror" id="category" name="category" value="{{ old('category', $course->category) }}" maxlength="120" list="course-categories" placeholder="e.g. Compliance">
                        <datalist id="course-categories">
                            @foreach ($categories as $category)
                                <option value="{{ $category }}">
                            @endforeach
                        </datalist>
                        <div class="quick-chips">
                            @foreach ($categories->take(4)->merge(['Technical', 'Soft skills', 'Compliance', 'Leadership', 'Induction'])->unique()->take(5) as $suggestion)
                                <button type="button" class="quick-chip" data-category="{{ $suggestion }}">{{ $suggestion }}</button>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="duration_hours">Duration in hours <span class="text-muted fw-normal">(optional)</span></label>
                        <input type="number" step="0.25" min="0.25" max="999" class="form-control @error('duration_hours') is-invalid @enderror" id="duration_hours" name="duration_hours" value="{{ $duration }}" placeholder="e.g. 4">
                        <div class="quick-chips">
                            @foreach ([1 => '1 hour', 2 => '2 hours', 4 => 'Half day', 8 => 'Full day', 16 => '2 days'] as $hours => $label)
                                <button type="button" class="quick-chip" data-hours="{{ $hours }}">{{ $label }}</button>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">How it is delivered</label>
                        <div class="rel-pick mode-pick">
                            @foreach (Course::DELIVERY_MODES as $value => $label)
                                <label class="rel-option tone-{{ $modeMeta[$value][1] ?? 'slate' }}">
                                    <input type="radio" name="delivery_mode" value="{{ $value }}" @checked($mode === $value) required>
                                    <i class="ti {{ $modeMeta[$value][0] ?? 'ti-book' }}"></i>
                                    <span class="rel-option-title">{{ $label }}</span>
                                    <span class="rel-option-hint">{{ $modeMeta[$value][2] ?? '' }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="toggle-cards">
                            <label class="toggle-card tone-success">
                                <input type="checkbox" name="is_active" value="1" id="is_active" @checked($isActive)>
                                <span class="toggle-card-icon"><i class="ti ti-circle-check"></i></span>
                                <span class="toggle-card-body">
                                    <span class="toggle-card-title">Active</span>
                                    <span class="toggle-card-hint">Off = kept for the record, but not offered for new sessions.</span>
                                </span>
                                <span class="switch-btn" aria-hidden="true"></span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-de-primary text-light px-4"><i class="ti ti-check font-14 me-1"></i> {{ $course->exists ? 'Save changes' : 'Add course' }}</button>
                    <a href="{{ route('admin.courses.index') }}" class="btn btn-light px-4">Cancel</a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card side-list tone-teal mb-0">
            <div class="side-list-head">
                <span class="side-list-icon"><i class="ti ti-eye"></i></span>
                <span class="fw-semibold">How it will look</span>
            </div>
            <div class="card-body">
                <div class="course-card tone-indigo" id="course-preview">
                    <div class="course-card-head">
                        <span class="course-card-icon"><i class="ti ti-book"></i></span>
                        <div class="flex-grow-1 min-w-0">
                            <div class="course-card-title" id="preview-title">Course name</div>
                            <div class="course-card-tags">
                                <span class="tag tone-indigo" id="preview-category" hidden></span>
                                <span class="tag tone-slate" id="preview-mode"></span>
                                <span class="tag tone-slate" id="preview-duration" hidden></span>
                                <span class="tag tone-slate" id="preview-off" hidden>Switched off</span>
                            </div>
                        </div>
                    </div>
                    <p class="course-card-text mb-0" id="preview-text">No description yet.</p>
                </div>
                @isset($usage)
                    <div class="run-lines">
                        <div><span>Sessions</span><strong>{{ $usage['sessions'] ?? 0 }}</strong></div>
                        <div><span>People enrolled</span><strong>{{ $usage['enrolled'] ?? 0 }}</strong></div>
                        <div><span>Completed</span><strong>{{ $usage['completed'] ?? 0 }}</strong></div>
                    </div>
                    @if (($usage['sessions'] ?? 0) > 0)
                        <div class="run-note tone-indigo mt-3"><i class="ti ti-info-circle"></i><span>Changes here show on this course's existing sessions and records too.</span></div>
                    @endif
                @endisset
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        (function () {
            var el = function (id) { return document.getElementById(id); };
            var title = el('title'), description = el('description'), category = el('category'), hours = el('duration_hours'), active = el('is_active');
            if (!title) return;
            var modes = @json(Course::DELIVERY_MODES);

            function render() {
                var mode = (document.querySelector('input[name="delivery_mode"]:checked') || {}).value;
                var h = parseFloat(hours.value);
                el('preview-title').textContent = title.value.trim() || 'Course name';
                el('preview-text').textContent = description.value.trim() ? description.value.trim().slice(0, 140) : 'No description yet.';
                el('preview-category').hidden = !category.value.trim();
                el('preview-category').textContent = category.value.trim();
                el('preview-mode').textContent = modes[mode] || '';
                el('preview-duration').hidden = !(h > 0);
                el('preview-duration').textContent = h > 0 ? (Math.round(h * 100) / 100) + (h === 1 ? ' hour' : ' hours') : '';
                el('preview-off').hidden = active.checked;
                el('course-preview').classList.toggle('is-off', !active.checked);
            }

            [title, description, category, hours].forEach(function (input) { input.addEventListener('input', render); });
            active.addEventListener('change', render);
            document.querySelectorAll('input[name="delivery_mode"]').forEach(function (r) { r.addEventListener('change', render); });
            document.querySelectorAll('[data-category]').forEach(function (chip) {
                chip.addEventListener('click', function () { category.value = chip.dataset.category; render(); });
            });
            document.querySelectorAll('[data-hours]').forEach(function (chip) {
                chip.addEventListener('click', function () { hours.value = chip.dataset.hours; render(); });
            });
            render();
        })();
    </script>
@endpush
