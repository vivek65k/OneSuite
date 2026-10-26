@extends('layouts.admin')

@section('title', 'Manager Review')

@php
    use Illuminate\Support\Str;

    $num = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
    $pageUrl = fn (array $overrides = []) => route('admin.manager-reviews.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $tabs = [
        '' => ['label' => 'All appraisals', 'tone' => 'indigo', 'count' => $counts['all']],
        'pending' => ['label' => 'To review', 'tone' => 'amber', 'count' => $counts['pending']],
        'reviewed' => ['label' => 'Reviewed', 'tone' => 'success', 'count' => $counts['reviewed']],
        'locked' => ['label' => 'Completed', 'tone' => 'slate', 'count' => $counts['locked']],
    ];
    $filterValues = [
        'employee_id' => $employees[$state['employee_id']] ?? null,
        'department_id' => $departments[$state['department_id']] ?? null,
        'cycle_id' => $cycles[$state['cycle_id']] ?? null,
        'search' => $state['search'],
    ];
    $activeFilters = collect(['employee_id' => 'Employee', 'department_id' => 'Department', 'cycle_id' => 'Cycle', 'search' => 'Search'])
        ->filter(fn ($label, $key) => filled($state[$key]))
        ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => $filterValues[$key] ?? 'Unknown']);
    $stars = fn ($rating) => str_repeat('★', (int) $rating).str_repeat('☆', max(0, 5 - (int) $rating));
    $donePct = $kpis['total'] ? round($kpis['reviewed'] / $kpis['total'] * 100) : 0;
@endphp

@section('content')
    <x-page-title title="Manager Review" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="To review" :value="$kpis['pending']" icon="ti-clipboard-list" :tone="$kpis['pending'] ? 'amber' : 'success'"
                    :hint="$kpis['pending'] ? $kpis['self_in'].' already have their self review in' : ($kpis['total'] ? 'Every open appraisal is reviewed' : 'No appraisal is open')" :href="$pageUrl(['tab' => 'pending'])" />
        <x-kpi-card label="Reviewed" :value="$kpis['total'] ? $kpis['reviewed'].' / '.$kpis['total'] : '—'" icon="ti-user-check" tone="teal" :hint="$kpis['total'] ? $donePct.'% of appraisals' : null" :href="$pageUrl(['tab' => 'reviewed'])" />
        <x-kpi-card label="Average manager rating" :value="$kpis['average'] !== null ? $num($kpis['average']).' / 5' : '—'" icon="ti-star" tone="indigo" :hint="$kpis['average'] !== null ? 'Across '.$kpis['reviewed'].' '.Str::plural('review', $kpis['reviewed']) : 'No reviews yet'" />
        <x-kpi-card label="Manager vs self" :value="$kpis['gap'] !== null ? ($kpis['gap'] > 0 ? '+' : '').$num($kpis['gap']).' pts' : '—'" icon="ti-arrows-up-down" :tone="$kpis['gap'] === null ? 'slate' : (abs($kpis['gap']) >= 1 ? 'rose' : 'success')"
                    :hint="$kpis['gap'] !== null ? ($kpis['gap'] < 0 ? 'Managers rate lower than people rate themselves' : ($kpis['gap'] > 0 ? 'Managers rate higher than people rate themselves' : 'Managers and employees agree on average')) : 'Needs both reviews to compare'" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.manager-reviews.index') }}">
            @if ($state['tab'] !== '')
                <input type="hidden" name="tab" value="{{ $state['tab'] }}">
            @endif
            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Review status">
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
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] }}" placeholder="Search name or code" aria-label="Search appraisals">
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
                    @if ($departments->count() > 1)
                        <select name="department_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Department">
                            <option value="">All departments</option>
                            @foreach ($departments as $id => $name)
                                <option value="{{ $id }}" @selected($state['department_id'] === $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    @endif
                    <select name="employee_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Employee">
                        <option value="">All employees</option>
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
                        <th>Employee</th>
                        <th>Self review</th>
                        <th>What to go on</th>
                        <th>Manager review</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($appraisals as $appraisal)
                        @php
                            $employee = $appraisal->employee;
                            $key = $appraisal->employee_id.':'.$appraisal->appraisal_cycle_id;
                            $goal = $goalProgress[$key] ?? null;
                            $kpi = $kpiAchievement[$key] ?? null;
                            $fb = $feedback[$appraisal->id] ?? null;
                            $selfDone = $appraisal->self_review_rating !== null;
                            $done = $appraisal->manager_review_rating !== null;
                            $locked = $appraisal->status === 'completed';
                            $own = $myEmployeeId && $appraisal->employee_id === $myEmployeeId;
                            $gap = $selfDone && $done ? $appraisal->manager_review_rating - $appraisal->self_review_rating : null;
                            $context = collect([
                                $goal ? $goal->goals.' '.Str::plural('goal', $goal->goals).' at '.round((float) $goal->progress).'%' : null,
                                $kpi ? $kpi['kpis'].' '.Str::plural('KPI', $kpi['kpis']).($kpi['achievement'] !== null ? ' at '.$num(round($kpi['achievement'], 1)).'%' : ', no results') : null,
                                $fb ? '360 feedback '.$num(round((float) $fb->rating, 1)).' of 5 from '.$fb->entries : null,
                            ])->filter()->values();
                        @endphp
                        <tr class="{{ ! $locked && ! $done ? 'is-pending-row' : '' }}">
                            <td>
                                <div class="list-user">
                                    <x-avatar :person="$employee" />
                                    <div class="list-user-text">
                                        <div class="list-user-name">{{ $employee?->name ?? 'Removed employee' }}</div>
                                        <div class="list-user-meta">{{ collect([$appraisal->appraisalCycle?->name, $employee?->department?->name])->filter()->implode(' · ') }}</div>
                                        @if ($employee?->manager)
                                            <div class="list-user-meta">Reports to {{ $employee->manager->name }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="review-cell">
                                @if ($selfDone)
                                    <div class="rating-stars" title="{{ $appraisal->self_review_rating }} of 5">{{ $stars($appraisal->self_review_rating) }}</div>
                                    @if ($appraisal->self_review_comments)
                                        <div class="payslip-parts text-wrap" title="{{ $appraisal->self_review_comments }}">{{ Str::limit($appraisal->self_review_comments, 90) }}</div>
                                    @endif
                                @else
                                    <span class="tag tone-slate"><i class="ti ti-clock"></i>Not submitted</span>
                                @endif
                            </td>
                            <td class="font-13">
                                @forelse ($context as $line)
                                    <div>{{ $line }}</div>
                                @empty
                                    <span class="text-muted">Nothing recorded for this cycle</span>
                                @endforelse
                            </td>
                            <td class="review-cell">
                                @if ($done)
                                    <div class="rating-stars" title="{{ $appraisal->manager_review_rating }} of 5">{{ $stars($appraisal->manager_review_rating) }}@if ($gap) <span class="tag tone-{{ abs($gap) >= 2 ? 'rose' : 'slate' }}" title="Compared with the self rating">{{ $gap > 0 ? '+' : '' }}{{ $gap }} vs self</span>@endif</div>
                                    @if ($appraisal->manager_review_comments)
                                        <div class="payslip-parts text-wrap" title="{{ $appraisal->manager_review_comments }}">{{ Str::limit($appraisal->manager_review_comments, 90) }}</div>
                                    @endif
                                    <div class="payslip-parts">{{ collect([$appraisal->managerReviewedBy ? 'by '.$appraisal->managerReviewedBy->name : null, $appraisal->manager_review_submitted_at?->format('M j, Y')])->filter()->implode(' · ') }}</div>
                                @else
                                    <span class="tag tone-amber"><i class="ti ti-clock"></i>Waiting for review</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                @if ($locked)
                                    <span class="tag tone-slate" title="Reopen the appraisal to change the review"><i class="ti ti-lock"></i>Completed</span>
                                @elseif ($own)
                                    <span class="font-12 text-muted" title="Someone else has to write the manager review for your appraisal">Your own appraisal</span>
                                @else
                                    @can('update', $appraisal)
                                        <button type="button" class="btn btn-sm review-btn {{ $done ? '' : 'is-approve' }}" data-bs-toggle="modal" data-bs-target="#review-modal"
                                                data-action="{{ route('admin.manager-reviews.update', $appraisal) }}" data-name="{{ $employee?->name ?? 'Removed employee' }}" data-cycle="{{ $appraisal->appraisalCycle?->name }}"
                                                data-self="{{ $appraisal->self_review_rating }}" data-self-comments="{{ $appraisal->self_review_comments }}" data-context="{{ $context->implode(' · ') }}"
                                                data-rating="{{ $appraisal->manager_review_rating }}" data-comments="{{ $appraisal->manager_review_comments }}">
                                            <i class="ti ti-pencil"></i> {{ $done ? 'Change review' : 'Review' }}
                                        </button>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="ti ti-user-check font-24 d-block mb-2"></i>
                                @if ($counts['all'] || $activeFilters->isNotEmpty() || $state['tab'] !== '')
                                    <div class="fw-semibold text-body mb-1">No appraisals match</div>
                                    <span class="font-13"><a href="{{ route('admin.manager-reviews.index') }}">Show all appraisals</a></span>
                                @else
                                    <div class="fw-semibold text-body mb-1">No appraisals to review yet</div>
                                    <span class="font-13">Reviews are written against an appraisal. @can('viewAny', \App\Models\Appraisal::class)<a href="{{ route('admin.appraisals.index') }}">Start appraisals</a> first.@endcan</span>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($appraisals->hasPages())
            <div class="list-footer"><div class="ms-auto">{{ $appraisals->links() }}</div></div>
        @endif
    </div>

    <div class="modal fade" id="review-modal" tabindex="-1" aria-labelledby="review-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form method="POST" action="#" class="modal-content" id="review-form">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="review-modal-title">Manager review</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="font-13 text-muted mb-3">For <strong id="review-who" class="text-body"></strong></p>
                    <div class="row g-3">
                        <div class="col-md-5">
                            <div class="review-context">
                                <div class="rev-compare-label">Their self review</div>
                                <div class="rating-stars" id="review-self-stars"></div>
                                <div class="font-13 text-muted" id="review-self-comments"></div>
                                <div class="rev-compare-label mt-3">What to go on</div>
                                <div class="font-13" id="review-context"></div>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label d-block">Your rating <span class="text-muted fw-normal" id="review-rating-label"></span></label>
                            <div class="star-pick" role="radiogroup" aria-label="Rating out of 5">
                                @foreach ([5, 4, 3, 2, 1] as $value)
                                    <input type="radio" name="manager_review_rating" id="review-star-{{ $value }}" value="{{ $value }}" required>
                                    <label for="review-star-{{ $value }}" title="{{ $value }} of 5">★</label>
                                @endforeach
                            </div>
                            <div class="run-note tone-amber mt-2" id="review-gap" hidden><i class="ti ti-arrows-up-down"></i><span></span></div>
                            <label class="form-label d-block mt-3" for="review-comments">Comments <span class="text-muted fw-normal">(optional)</span></label>
                            <textarea name="manager_review_comments" id="review-comments" rows="5" maxlength="2000" class="form-control" placeholder="What went well, what to work on, and why this rating"></textarea>
                            <div class="font-12 text-muted mt-1"><span id="review-count">0</span> / 2000</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-de-primary text-light"><i class="ti ti-check font-14 me-1"></i> <span id="review-submit">Submit review</span></button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var modal = document.getElementById('review-modal');
            if (!modal) return;
            var el = function (id) { return document.getElementById(id); };
            var words = { 1: 'Poor', 2: 'Below expectations', 3: 'Meets expectations', 4: 'Good', 5: 'Excellent' };
            var stars = function (n) { return '★★★★★'.slice(0, n) + '☆☆☆☆☆'.slice(0, 5 - n); };
            var radios = Array.prototype.slice.call(modal.querySelectorAll('input[name="manager_review_rating"]'));
            var comments = el('review-comments'), self = null;

            function render() {
                var rating = parseInt((radios.filter(function (r) { return r.checked; })[0] || {}).value, 10);
                var gap = rating && self ? rating - self : null;
                el('review-rating-label').textContent = rating ? '· ' + rating + ' of 5, ' + words[rating] : '';
                el('review-count').textContent = comments.value.length;
                el('review-gap').hidden = !(gap !== null && Math.abs(gap) >= 2);
                el('review-gap').querySelector('span').textContent = gap !== null && Math.abs(gap) >= 2
                    ? 'That is ' + Math.abs(gap) + ' ' + (gap < 0 ? 'lower' : 'higher') + ' than their own rating of ' + self + '. A comment explaining why will help.'
                    : '';
            }

            modal.addEventListener('show.bs.modal', function (event) {
                var b = event.relatedTarget;
                if (!b) return;
                self = parseInt(b.dataset.self, 10) || null;
                el('review-form').action = b.dataset.action;
                el('review-who').textContent = b.dataset.name + (b.dataset.cycle ? ' · ' + b.dataset.cycle : '');
                el('review-self-stars').textContent = self ? stars(self) + '  ' + self + ' of 5' : '';
                el('review-self-comments').textContent = self ? (b.dataset.selfComments || 'No comment given.') : 'They have not submitted a self review yet. You can still review them.';
                el('review-context').textContent = b.dataset.context || 'No goals, KPIs or 360 feedback are recorded for this cycle.';
                radios.forEach(function (r) { r.checked = r.value === b.dataset.rating; });
                comments.value = b.dataset.comments || '';
                el('review-submit').textContent = b.dataset.rating ? 'Update review' : 'Submit review';
                render();
            });
            radios.forEach(function (r) { r.addEventListener('change', render); });
            comments.addEventListener('input', render);
        })();
    </script>
@endpush
