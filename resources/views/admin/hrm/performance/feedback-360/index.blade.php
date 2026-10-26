@extends('layouts.admin')

@section('title', '360 Feedback')

@php
    use App\Models\Feedback360;
    use Illuminate\Support\Str;

    $num = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
    $pageUrl = fn (array $overrides = []) => route('admin.feedback-360.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $relTone = ['self' => 'slate', 'manager' => 'indigo', 'peer' => 'teal', 'subordinate' => 'violet'];
    $relIcon = ['self' => 'ti-user', 'manager' => 'ti-user-check', 'peer' => 'ti-users', 'subordinate' => 'ti-sitemap'];
    $relHint = ['self' => 'Their own view', 'manager' => 'Who they report to', 'peer' => 'Works alongside them', 'subordinate' => 'Reports to them'];
    $tabs = ['' => ['label' => 'All feedback', 'tone' => 'indigo', 'count' => $counts['all']]];
    foreach (Feedback360::RELATIONSHIPS as $value => $label) {
        $tabs[$value] = ['label' => $label, 'tone' => $relTone[$value] ?? 'slate', 'count' => $counts[$value] ?? 0];
    }
    $filterValues = ['employee_id' => $employees[$state['employee_id']] ?? null, 'cycle_id' => $cycles[$state['cycle_id']] ?? null, 'search' => $state['search']];
    $activeFilters = collect(['employee_id' => 'About', 'cycle_id' => 'Cycle', 'search' => 'Search'])
        ->filter(fn ($label, $key) => filled($state[$key]))
        ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => $filterValues[$key] ?? 'Unknown']);
    $stars = fn ($rating) => str_repeat('★', (int) $rating).str_repeat('☆', max(0, 5 - (int) $rating));
    $oldRelationship = old('relationship', 'peer');
    $oldRating = (int) old('rating', 0);
@endphp

@section('content')
    <x-page-title title="360 Feedback" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Feedback given" :value="$kpis['total']" icon="ti-messages" tone="indigo" :hint="$kpis['total'] ? 'From '.$kpis['reviewers'].' '.Str::plural('reviewer', $kpis['reviewers']) : 'Nothing recorded yet'" />
        <x-kpi-card label="Average rating" :value="$kpis['average'] !== null ? $num($kpis['average']).' / 5' : '—'" icon="ti-star" tone="amber" :hint="$kpis['average'] !== null ? 'Across all feedback' : null" />
        <x-kpi-card label="Open appraisals covered" :value="$kpis['open'] ? $kpis['covered'].' / '.$kpis['open'] : '—'" icon="ti-clipboard-check" :tone="$kpis['open'] && $kpis['covered'] === $kpis['open'] ? 'success' : 'teal'" :hint="$kpis['open'] ? 'Have at least one piece of feedback' : 'No appraisal is open'" />
        <x-kpi-card label="Waiting for feedback" :value="$kpis['waiting']" icon="ti-clock" :tone="$kpis['waiting'] ? 'amber' : 'success'" :hint="$kpis['waiting'] ? 'Open appraisals with none yet' : ($kpis['open'] ? 'Every open appraisal has some' : null)" />
    </div>

    @if ($canCreate)
        <div class="row g-3 mb-3">
            <div class="col-xl-7">
                <form method="POST" action="{{ route('admin.feedback-360.store') }}" class="card form-section tone-indigo mb-0 h-100" id="fb-form">
                    @csrf
                    <div class="form-section-head">
                        <span class="form-section-icon"><i class="ti ti-messages"></i></span>
                        <div class="flex-grow-1">
                            <h5 class="form-section-title">Record feedback</h5>
                            <p class="form-section-sub">Capture what a manager, peer or team member says about someone, for their open appraisal.</p>
                        </div>
                    </div>
                    <div class="form-section-body">
                        @if ($openAppraisals->isEmpty())
                            <div class="run-note tone-amber"><i class="ti ti-alert-triangle"></i><span>No appraisal is open. Feedback is recorded against an open appraisal, so start one first. @can('viewAny', \App\Models\Appraisal::class)<a href="{{ route('admin.appraisals.index') }}">Go to Appraisals</a>@endcan</span></div>
                        @else
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="fb-appraisal">Feedback about</label>
                                    <select name="appraisal_id" id="fb-appraisal" class="form-select @error('appraisal_id') is-invalid @enderror" required>
                                        <option value="">Select person</option>
                                        @foreach ($openAppraisals as $appraisal)
                                            <option value="{{ $appraisal->id }}" @selected((string) old('appraisal_id') === (string) $appraisal->id)>{{ $appraisal->employee->name }} — {{ $appraisal->appraisalCycle->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="fb-reviewer">Given by</label>
                                    <select name="reviewer_employee_id" id="fb-reviewer" class="form-select @error('reviewer_employee_id') is-invalid @enderror" required>
                                        <option value="">Select reviewer</option>
                                        @foreach ($reviewers as $person)
                                            <option value="{{ $person->id }}" @selected((string) old('reviewer_employee_id') === (string) $person->id)>{{ $person->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">How they work together <span class="text-muted fw-normal" id="fb-rel-note"></span></label>
                                    <div class="rel-pick">
                                        @foreach (Feedback360::RELATIONSHIPS as $value => $label)
                                            <label class="rel-option tone-{{ $relTone[$value] ?? 'slate' }}">
                                                <input type="radio" name="relationship" value="{{ $value }}" @checked($oldRelationship === $value) required>
                                                <i class="ti {{ $relIcon[$value] ?? 'ti-user' }}"></i>
                                                <span class="rel-option-title">{{ $label }}</span>
                                                <span class="rel-option-hint">{{ $relHint[$value] ?? '' }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label d-block">Rating <span class="text-muted fw-normal" id="fb-rating-label"></span></label>
                                    <div class="star-pick @error('rating') is-invalid @enderror" id="fb-stars" role="radiogroup" aria-label="Rating out of 5">
                                        @foreach ([5, 4, 3, 2, 1] as $value)
                                            <input type="radio" name="rating" id="fb-star-{{ $value }}" value="{{ $value }}" @checked($oldRating === $value) required>
                                            <label for="fb-star-{{ $value }}" title="{{ $value }} of 5">★</label>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label" for="fb-comments">Comments <span class="text-muted fw-normal">(optional)</span></label>
                                    <textarea name="comments" id="fb-comments" rows="2" maxlength="2000" class="form-control" placeholder="What they do well, and what they could do better">{{ old('comments') }}</textarea>
                                </div>
                            </div>
                            <div class="run-note tone-amber mt-3" id="fb-warn" hidden><i class="ti ti-alert-triangle"></i><span></span></div>
                            <div class="mt-3">
                                <button type="submit" class="btn btn-de-primary text-light px-4"><i class="ti ti-check font-14 me-1"></i> Record feedback</button>
                            </div>
                        @endif
                    </div>
                </form>
            </div>
            <div class="col-xl-5">
                <div class="card side-list tone-teal mb-0 h-100">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-chart-donut"></i></span>
                        <span class="fw-semibold">Feedback so far</span>
                        <span class="side-list-count" id="fb-so-far-count" hidden></span>
                    </div>
                    <div class="card-body">
                        <div class="empty-state py-4" id="fb-so-far-empty">
                            <div class="empty-state-icon"><i class="ti ti-user"></i></div>
                            <div class="fw-semibold">Pick who the feedback is about</div>
                            <div class="font-13 text-muted">You'll see what has already been given for their appraisal.</div>
                        </div>
                        <div id="fb-so-far" hidden>
                            <div class="rev-compare">
                                <div>
                                    <span class="rev-compare-label">Average so far</span>
                                    <span class="rev-compare-value" id="fb-avg">—</span>
                                    <span class="rev-compare-meta" id="fb-avg-meta"></span>
                                </div>
                                <i class="ti ti-arrow-right"></i>
                                <div>
                                    <span class="rev-compare-label">With this one</span>
                                    <span class="rev-compare-value" id="fb-new-avg">—</span>
                                    <span class="rev-compare-meta" id="fb-new-meta">Pick a rating</span>
                                </div>
                            </div>
                            <div class="run-lines" id="fb-by-rel"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.feedback-360.index') }}">
            @if ($state['tab'] !== '')
                <input type="hidden" name="tab" value="{{ $state['tab'] }}">
            @endif
            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Relationship">
                    @foreach ($tabs as $value => $tab)
                        <a href="{{ $pageUrl(['tab' => $value ?: null]) }}" class="list-tab tone-{{ $tab['tone'] }} {{ $state['tab'] === $value ? 'active' : '' }}">
                            {{ $tab['label'] }}
                            <span class="list-tab-count">{{ $tab['count'] }}</span>
                        </a>
                    @endforeach
                </nav>
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] }}" placeholder="Search either person" aria-label="Search feedback">
                    </div>
                    @if ($cycles->count() > 1)
                        <select name="cycle_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Cycle">
                            <option value="">All cycles</option>
                            @foreach ($cycles as $id => $name)
                                <option value="{{ $id }}" @selected($state['cycle_id'] === $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    @elseif ($state['cycle_id'])
                        <input type="hidden" name="cycle_id" value="{{ $state['cycle_id'] }}">
                    @endif
                    <select name="employee_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Feedback about">
                        <option value="">About anyone</option>
                        @foreach ($employees as $id => $name)
                            <option value="{{ $id }}" @selected($state['employee_id'] === $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>

        @if ($activeFilters->isNotEmpty())
            <div class="list-subbar">
                <div class="list-chips">
                    @foreach ($activeFilters as $filter)
                        <a href="{{ $pageUrl([$filter['key'] => null]) }}" class="filter-chip" title="Remove filter">{{ $filter['label'] }}: <strong>{{ $filter['value'] }}</strong> <i class="ti ti-x"></i></a>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="table-responsive table-responsive-sticky">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>About</th>
                        <th>Given by</th>
                        <th>Rating</th>
                        <th>Comments</th>
                        <th>When</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($feedback as $item)
                        @php
                            $about = $item->appraisal?->employee;
                            $locked = $item->appraisal?->status === 'completed';
                            $tone = $relTone[$item->relationship] ?? 'slate';
                        @endphp
                        <tr>
                            <td>
                                <div class="list-user">
                                    <x-avatar :person="$about" />
                                    <div class="list-user-text">
                                        <div class="list-user-name"><a href="{{ $pageUrl(['employee_id' => $about?->id, 'tab' => null]) }}" title="Show all feedback about this person">{{ $about?->name ?? 'Removed employee' }}</a></div>
                                        <div class="list-user-meta">{{ collect([$item->appraisal?->appraisalCycle?->name, $locked ? 'appraisal completed' : null])->filter()->implode(' · ') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="list-user">
                                    <x-avatar :person="$item->reviewer" />
                                    <div class="list-user-text">
                                        <div class="list-user-name">{{ $item->reviewer?->name ?? 'Removed employee' }}</div>
                                        <div class="list-user-meta"><span class="tag tone-{{ $tone }}"><i class="ti {{ $relIcon[$item->relationship] ?? 'ti-user' }}"></i>{{ Feedback360::RELATIONSHIPS[$item->relationship] ?? $item->relationship }}</span></div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                <div class="rating-stars" title="{{ $item->rating }} of 5">{{ $stars($item->rating) }}</div>
                                <div class="payslip-parts">{{ $item->rating }} of 5</div>
                            </td>
                            <td class="rev-notes fb-comment" title="{{ $item->comments }}">{{ $item->comments ? Str::limit($item->comments, 140) : '—' }}</td>
                            <td class="text-nowrap text-muted font-13">{{ $item->created_at?->format('M j, Y') }}</td>
                            <td class="text-end">
                                @unless ($locked)
                                    @can('delete', $item)
                                        <form action="{{ route('admin.feedback-360.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this feedback?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon btn-icon-danger" title="Remove feedback"><i class="ti ti-trash font-16"></i></button>
                                        </form>
                                    @endcan
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="ti ti-messages font-24 d-block mb-2"></i>
                                @if ($counts['all'] || $activeFilters->isNotEmpty() || $state['tab'] !== '')
                                    <div class="fw-semibold text-body mb-1">No feedback matches</div>
                                    <span class="font-13"><a href="{{ route('admin.feedback-360.index') }}">Show all feedback</a></span>
                                @else
                                    <div class="fw-semibold text-body mb-1">No feedback yet</div>
                                    <span class="font-13">{{ $canCreate ? 'Record the first one with the form above.' : 'Feedback recorded by HR will appear here.' }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($feedback->hasPages())
            <div class="list-footer"><div class="ms-auto">{{ $feedback->links() }}</div></div>
        @endif
    </div>
@endsection

@if ($canCreate && $openAppraisals->isNotEmpty())
    @push('scripts')
        <script>
            (function () {
                var form = document.getElementById('fb-form');
                if (!form) return;
                var data = @json($formData);
                var labels = @json(Feedback360::RELATIONSHIPS);
                var words = { 1: 'Poor', 2: 'Below expectations', 3: 'Meets expectations', 4: 'Good', 5: 'Excellent' };
                var el = function (id) { return document.getElementById(id); };
                var appraisal = el('fb-appraisal'), reviewer = el('fb-reviewer');
                var radios = Array.prototype.slice.call(form.querySelectorAll('input[name="relationship"]'));
                var trim = function (n) { return String(Math.round(n * 10) / 10); };
                var touched = false;

                // Work out the relationship from who reports to whom, so it rarely needs picking by hand.
                function suggest(a, r) {
                    if (!a || !r) return null;
                    if (String(a.employee_id) === reviewer.value) return 'self';
                    if (a.manager_id && String(a.manager_id) === reviewer.value) return 'manager';
                    if (r.manager_id && String(r.manager_id) === String(a.employee_id)) return 'subordinate';
                    return 'peer';
                }

                function render() {
                    var a = data.appraisals[appraisal.value], r = data.people[reviewer.value];
                    var guess = suggest(a, r), warn = '';
                    if (guess && !touched) radios.forEach(function (x) { x.checked = x.value === guess; });
                    var picked = (radios.filter(function (x) { return x.checked; })[0] || {}).value;
                    var rating = parseInt((form.querySelector('input[name="rating"]:checked') || {}).value, 10);

                    el('fb-rel-note').textContent = guess ? '· looks like ' + labels[guess].toLowerCase() : '';
                    el('fb-rating-label').textContent = rating ? '· ' + rating + ' of 5, ' + words[rating] : '';
                    el('fb-so-far').hidden = !a;
                    el('fb-so-far-empty').hidden = !!a;
                    el('fb-so-far-count').hidden = !a;

                    if (a) {
                        var given = a.given, sum = given.reduce(function (s, g) { return s + g.rating; }, 0);
                        el('fb-so-far-count').textContent = given.length;
                        el('fb-avg').textContent = given.length ? trim(sum / given.length) : '—';
                        el('fb-avg-meta').textContent = given.length ? 'from ' + given.length + (given.length === 1 ? ' reviewer' : ' reviewers') : 'No feedback yet for ' + a.name;
                        el('fb-new-avg').textContent = rating ? trim((sum + rating) / (given.length + 1)) : '—';
                        el('fb-new-meta').textContent = rating ? 'from ' + (given.length + 1) + (given.length + 1 === 1 ? ' reviewer' : ' reviewers') : 'Pick a rating';
                        el('fb-by-rel').innerHTML = Object.keys(labels).map(function (key) {
                            var of = given.filter(function (g) { return g.relationship === key; });
                            var avg = of.length ? trim(of.reduce(function (s, g) { return s + g.rating; }, 0) / of.length) + ' of 5 <em>· ' + of.length + '</em>' : '<em>None yet</em>';
                            return '<div><span>' + labels[key] + '</span><strong>' + avg + '</strong></div>';
                        }).join('');

                        if (r && given.some(function (g) { return String(g.reviewer) === reviewer.value; })) warn = r.name + ' has already given feedback on this appraisal. A second entry will be rejected.';
                        else if (guess === 'self' && picked !== 'self') warn = r.name + ' is the person being appraised, so this must be "Self".';
                        else if (r && guess !== 'self' && picked === 'self') warn = '"Self" feedback can only come from ' + a.name + '.';
                        else if (r && guess && picked && picked !== guess) warn = 'The reporting lines suggest ' + r.name + ' is a ' + labels[guess].toLowerCase() + ', not a ' + labels[picked].toLowerCase() + '. Keep your choice if that is right.';
                    }
                    el('fb-warn').hidden = !warn;
                    el('fb-warn').querySelector('span').textContent = warn;
                }

                [appraisal, reviewer].forEach(function (input) { input.addEventListener('change', function () { touched = false; render(); }); });
                radios.forEach(function (x) { x.addEventListener('change', function () { touched = true; render(); }); });
                form.querySelectorAll('input[name="rating"]').forEach(function (x) { x.addEventListener('change', render); });
                render();
            })();
        </script>
    @endpush
@endif
