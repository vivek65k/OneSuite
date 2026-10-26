@extends('layouts.admin')

@section('title', 'Performance Review')

@php
    use Illuminate\Support\Str;

    $num = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
    $pageUrl = fn (array $overrides = []) => route('admin.team-performance.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $ratingLabels = [1 => 'Needs improvement', 2 => 'Below expectations', 3 => 'Meets expectations', 4 => 'Exceeds expectations', 5 => 'Outstanding'];
    $tabs = [
        '' => ['label' => 'Everyone', 'tone' => 'indigo', 'count' => $counts['all']],
        'todo' => ['label' => 'Ready for my review', 'tone' => 'amber', 'count' => $counts['todo']],
        'waiting' => ['label' => 'No self review yet', 'tone' => 'slate', 'count' => $counts['waiting']],
        'reviewed' => ['label' => 'Reviewed, with HR', 'tone' => 'sky', 'count' => $counts['reviewed']],
        'completed' => ['label' => 'Completed', 'tone' => 'success', 'count' => $counts['completed']],
    ];
    $stageMeta = [
        'todo' => ['amber', 'ti-edit', 'Ready for your review'],
        'waiting' => ['slate', 'ti-clock', 'Waiting for their self review'],
        'reviewed' => ['sky', 'ti-clock', 'Reviewed, with HR'],
        'completed' => ['success', 'ti-circle-check', 'Completed'],
    ];
    $stars = fn ($rating) => collect(range(1, 5))->map(fn ($i) => '<i class="ti ti-star '.($i <= round((float) $rating) ? 'is-on' : '').'"></i>')->implode('');
    $closed = $cycle?->status === 'closed';
    // After a refused save, reopen the review that was being written.
    $reopenId = $errors->hasAny(['manager_review_rating', 'manager_review_comments']) ? (int) old('_appraisal') : 0;
    $typed = collect(['rating' => old('manager_review_rating'), 'comments' => old('manager_review_comments')])->map(fn ($value) => is_scalar($value) ? (string) $value : '')->all();
@endphp

@section('content')
    <x-page-title title="Performance Review" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    @unless ($hasTeam)
        <div class="card">
            <div class="card-body text-center text-muted py-5">
                <i class="ti ti-users font-24 d-block mb-2"></i>
                <div class="fw-semibold text-body mb-1">Nobody reports to you</div>
                <span class="font-13">You review the appraisals of the people below you in the reporting line. HR sets who reports to whom on each employee's profile.</span>
            </div>
        </div>
    @else
        <div class="kpi-strip">
            <x-kpi-card label="Ready for my review" :value="$summary['todo']" icon="ti-edit" :tone="$summary['todo'] ? 'amber' : 'success'" :hint="$summary['todo'] ? 'Their self review is in' : ($summary['waiting'] ? $summary['waiting'].' still to write their self review' : 'Nothing is waiting on you')" :href="$summary['todo'] ? $pageUrl(['tab' => 'todo', 'employee_id' => null]) : null" />
            <x-kpi-card label="Reviewed" :value="$summary['reviewed'].' / '.$summary['total']" icon="ti-checks" tone="indigo" :hint="$summary['total'] ? ($summary['completed'].' completed by HR'.($summary['without'] > 0 ? ' · '.$summary['without'].' on the team have no appraisal' : '')) : 'No appraisals in this cycle'" />
            <x-kpi-card label="My average rating" :value="$summary['average'] !== null ? number_format($summary['average'], 1).' / 5' : '—'" icon="ti-star" tone="teal" :hint="$summary['average'] !== null ? ($ratingLabels[(int) round($summary['average'])] ?? '') : 'No reviews written yet'" />
            <x-kpi-card label="Self vs my rating" :value="$summary['gap'] !== null ? ($summary['gap'] > 0 ? '+' : '').number_format($summary['gap'], 1) : '—'" icon="ti-arrows-left-right" :tone="$summary['gap'] !== null && abs($summary['gap']) >= 1 ? 'rose' : 'violet'" :hint="$summary['gap'] === null ? 'Needs both reviews' : ($summary['gap'] > 0.2 ? 'People rate themselves higher than you do' : ($summary['gap'] < -0.2 ? 'You rate people higher than they rate themselves' : 'You and your team see it alike'))" />
        </div>

        <div class="card list-view">
            <form method="GET" action="{{ route('admin.team-performance.index') }}">
                @if ($state['tab'] !== '')
                    <input type="hidden" name="tab" value="{{ $state['tab'] }}">
                @endif
                <div class="list-toolbar">
                    <nav class="list-tabs" aria-label="Review stage">
                        @foreach ($tabs as $value => $tab)
                            @if ($value === '' || $tab['count'] || $state['tab'] === $value)
                                <a href="{{ $pageUrl(['tab' => $value ?: null]) }}" class="list-tab tone-{{ $tab['tone'] }} {{ $state['tab'] === $value ? 'active' : '' }}">
                                    {{ $tab['label'] }}
                                    <span class="list-tab-count">{{ $tab['count'] }}</span>
                                </a>
                            @endif
                        @endforeach
                    </nav>
                    <div class="list-toolbar-actions">
                        @if ($cycles->count() > 1)
                            <select name="cycle" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Appraisal cycle">
                                @foreach ($cycles as $option)
                                    <option value="{{ $option->id }}" @selected($option->id === $cycle?->id)>{{ $option->name }}{{ $option->status === 'closed' ? ' (closed)' : '' }}</option>
                                @endforeach
                            </select>
                        @elseif ($cycle)
                            <span class="tag tone-{{ $closed ? 'slate' : 'indigo' }}"><i class="ti ti-calendar-event"></i>{{ $cycle->name }}{{ $closed ? ' · closed' : '' }}</span>
                        @endif
                        @if ($people->count() > 1 || $focus)
                            <select name="employee_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Team member">
                                <option value="">Everyone</option>
                                @foreach ($people as $person)
                                    <option value="{{ $person->id }}" @selected($focus?->id === $person->id)>{{ $person->name }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                </div>
            </form>
            @if ($focus)
                <div class="list-subbar">
                    <div class="list-chips">
                        <a href="{{ $pageUrl(['employee_id' => null]) }}" class="filter-chip" title="Remove filter">Person: <strong>{{ $focus->name }}</strong> <i class="ti ti-x"></i></a>
                    </div>
                </div>
            @endif
            @if ($closed)
                <div class="list-subbar"><span class="font-13 text-muted"><i class="ti ti-lock"></i> This cycle is closed, so reviews can no longer be written or changed.</span></div>
            @endif

            <div class="table-responsive table-responsive-sticky">
                <table class="table list-table mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Team member</th>
                            <th style="width: 17%">Goals &amp; KPIs</th>
                            <th style="width: 24%">Their self review</th>
                            <th style="width: 24%">My review</th>
                            <th>Outcome</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($appraisals as $a)
                            @php
                                $person = $a->employee;
                                $stage = $stageMeta[$a->stage];
                                $e = $a->evidence;
                                $gap = $a->self_review_rating !== null && $a->manager_review_rating !== null ? $a->self_review_rating - $a->manager_review_rating : null;
                                $byOther = $a->manager_review_rating !== null && $a->managerReviewedBy && (int) $a->manager_reviewed_by_user_id !== (int) auth()->id();
                            @endphp
                            <tr class="{{ $a->stage === 'todo' ? 'is-pending-row' : '' }}">
                                <td>
                                    <div class="list-user">
                                        <x-avatar :person="$person" />
                                        <div class="list-user-text">
                                            <div class="list-user-name">{{ $person->name }}</div>
                                            <div class="list-user-meta">{{ collect([$person->designation ?: $person->department?->name, $person->is_direct ? null : 'indirect'])->filter()->implode(' · ') ?: $person->employee_code }}</div>
                                        </div>
                                    </div>
                                    <span class="tag tone-{{ $stage[0] }} mt-2"><i class="ti {{ $stage[1] }}"></i>{{ $stage[2] }}</span>
                                </td>
                                <td>
                                    @if ($e['goal_progress'] !== null)
                                        <div class="mini-progress tone-teal" title="{{ $e['goals_done'] }} of {{ $e['goals'] }} goals completed">
                                            <div class="mini-progress-bar is-toned"><span style="width: {{ $e['goal_progress'] }}%"></span></div>
                                            <span class="mini-progress-label">{{ $e['goal_progress'] }}%</span>
                                        </div>
                                        <div class="font-12 text-muted">{{ $e['goals_done'] }} of {{ $e['goals'] }} {{ Str::plural('goal', $e['goals']) }} done</div>
                                    @else
                                        <div class="font-12 text-muted">No goals in this cycle</div>
                                    @endif
                                    @if ($e['kpi_achievement'] !== null)
                                        <div class="font-12 mt-1 {{ $e['kpi_achievement'] >= 100 ? 'text-success' : ($e['kpi_achievement'] < 70 ? 'text-danger' : 'text-warning') }}"><i class="ti ti-trending-up"></i> KPIs at {{ $e['kpi_achievement'] }}% of target</div>
                                    @elseif ($e['kpis'])
                                        <div class="font-12 text-muted mt-1">{{ $e['kpis'] }} {{ Str::plural('KPI', $e['kpis']) }}, no results yet</div>
                                    @endif
                                    @if ($a->feedback360s_count)
                                        <div class="font-12 text-muted mt-1">360°: {{ $num(round((float) $a->feedback360s_avg_rating, 1)) }} / 5 from {{ $a->feedback360s_count }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if ($a->self_review_rating !== null)
                                        <span class="star-rating" title="{{ $a->self_review_rating }} out of 5">{!! $stars($a->self_review_rating) !!}</span>
                                        <span class="font-12 text-muted ms-1">{{ $ratingLabels[$a->self_review_rating] ?? '' }}</span>
                                        @if ($a->self_review_comments)
                                            <div class="font-13 mt-1" style="white-space: pre-line" title="{{ Str::length($a->self_review_comments) > 200 ? $a->self_review_comments : '' }}">{{ Str::limit($a->self_review_comments, 200) }}</div>
                                        @endif
                                        <div class="payslip-parts">submitted {{ $a->self_review_submitted_at?->format('M j, Y') }}</div>
                                    @else
                                        <span class="text-muted font-13">Not written yet</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($a->manager_review_rating !== null)
                                        <span class="star-rating" title="{{ $a->manager_review_rating }} out of 5">{!! $stars($a->manager_review_rating) !!}</span>
                                        <span class="font-12 text-muted ms-1">{{ $ratingLabels[$a->manager_review_rating] ?? '' }}</span>
                                        @if ($a->manager_review_comments)
                                            <div class="font-13 mt-1" style="white-space: pre-line" title="{{ Str::length($a->manager_review_comments) > 200 ? $a->manager_review_comments : '' }}">{{ Str::limit($a->manager_review_comments, 200) }}</div>
                                        @endif
                                        <div class="payslip-parts">{{ collect([$byOther ? 'by '.$a->managerReviewedBy->name : null, $a->manager_review_submitted_at?->format('M j, Y')])->filter()->implode(' · ') }}</div>
                                        @if ($gap !== null && abs($gap) >= 2)
                                            <div class="payslip-parts text-warning"><i class="ti ti-alert-triangle"></i> {{ abs($gap) }} points {{ $gap > 0 ? 'below' : 'above' }} their own rating; worth a conversation</div>
                                        @endif
                                    @elseif (! $a->can_review)
                                        <span class="text-muted font-13">Not reviewed</span>
                                    @endif
                                    @if ($a->can_review)
                                        <div class="{{ $a->manager_review_rating !== null ? 'mt-2' : '' }}">
                                            <button type="button" class="btn btn-sm {{ $a->manager_review_rating === null ? 'review-btn is-approve' : 'btn-light' }}" data-bs-toggle="modal" data-bs-target="#review-modal"
                                                    data-id="{{ $a->id }}" data-action="{{ route('admin.team-performance.review', $a) }}" data-who="{{ $person->name }}" data-rating="{{ $a->manager_review_rating }}" data-comments="{{ $a->manager_review_comments }}"
                                                    data-self="{{ $a->self_review_rating }}" data-self-comments="{{ $a->self_review_comments }}"
                                                    data-evidence="{{ collect([$e['goal_progress'] !== null ? 'Goals at '.$e['goal_progress'].'% ('.$e['goals_done'].' of '.$e['goals'].' done)' : null, $e['kpi_achievement'] !== null ? 'KPIs at '.$e['kpi_achievement'].'% of target' : null, $a->feedback360s_count ? '360° feedback '.$num(round((float) $a->feedback360s_avg_rating, 1)).' / 5' : null])->filter()->implode(' · ') }}">
                                                <i class="ti ti-edit"></i> {{ $a->manager_review_rating === null ? 'Write my review' : 'Edit' }}
                                            </button>
                                            @if ($a->stage === 'waiting')
                                                <div class="font-12 text-muted mt-1">You can review now, or wait for their self review.</div>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if ($a->stage === 'completed')
                                        @if ($a->overall_rating !== null)
                                            <div class="fw-bold font-18">{{ $num($a->overall_rating) }} <span class="font-12 text-muted fw-normal">/ 5</span></div>
                                        @else
                                            <span class="text-muted">No overall rating</span>
                                        @endif
                                        <div class="payslip-parts">completed {{ $a->completed_at?->format('M j, Y') }}</div>
                                    @elseif ($a->stage === 'reviewed')
                                        <span class="font-13 text-muted">HR completes it next</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">
                                    <i class="ti ti-star font-24 d-block mb-2"></i>
                                    @if ($state['tab'] !== '' || $focus)
                                        <div class="fw-semibold text-body mb-1">{{ $state['tab'] === 'todo' ? 'Nothing is ready for your review' : 'No appraisals match' }}</div>
                                        <span class="font-13"><a href="{{ $pageUrl(['tab' => null, 'employee_id' => null]) }}">Show everyone</a></span>
                                    @else
                                        <div class="fw-semibold text-body mb-1">No appraisals for your team yet</div>
                                        <span class="font-13">They appear here when HR starts a review cycle for the people who report to you.</span>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="modal fade" id="review-modal" tabindex="-1" aria-labelledby="review-modal-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <form method="POST" action="#" class="modal-content" id="review-form">
                    @csrf
                    <input type="hidden" name="_appraisal" id="review-id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="review-modal-title">Review</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <div class="font-12 text-muted mb-1">What they said about themselves</div>
                                <div class="border rounded-3 p-3 h-100">
                                    <div id="review-self-rating" class="fw-semibold mb-1"></div>
                                    <div id="review-self-comments" class="font-13" style="white-space: pre-line; max-height: 220px; overflow: auto"></div>
                                    <div id="review-evidence" class="font-12 text-muted mt-2"></div>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label d-block">Your rating <span class="text-danger">*</span></label>
                                <div class="star-picker" role="radiogroup" aria-label="Manager rating">
                                    {{-- Rendered 5 to 1 so the CSS ~ sibling selector can light up every lower star --}}
                                    @for ($i = 5; $i >= 1; $i--)
                                        <input type="radio" id="review-rating-{{ $i }}" name="manager_review_rating" value="{{ $i }}" required>
                                        <label for="review-rating-{{ $i }}" title="{{ $i }} · {{ $ratingLabels[$i] }}"><i class="ti ti-star"></i></label>
                                    @endfor
                                </div>
                                <div class="star-picker-meta"><span class="star-picker-label" id="review-rating-label">Pick a rating</span></div>
                                <div class="font-12 text-warning d-none" id="review-gap"><i class="ti ti-alert-triangle"></i> <span></span></div>

                                <label class="form-label mt-3 d-flex justify-content-between" for="review-comments"><span>Your comments <span class="text-danger d-none" id="review-required">*</span></span> <span class="text-muted fw-normal font-12" id="review-count">0 / 2000</span></label>
                                <textarea name="manager_review_comments" id="review-comments" rows="6" maxlength="2000" class="form-control" placeholder="What went well, what to work on, and what you expect next"></textarea>
                                <div class="font-12 text-muted mt-2"><i class="ti ti-info-circle"></i> They see your rating and comments once HR completes the appraisal. You can change this until then.</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-de-primary text-light" id="review-submit">Submit review</button>
                    </div>
                </form>
            </div>
        </div>
    @endunless
@endsection

@push('scripts')
    <script>
        (function () {
            var $ = function (id) { return document.getElementById(id); };
            var modal = $('review-modal');
            if (!modal) return;
            var labels = @json($ratingLabels), self = 0;
            function setRating(value) {
                modal.querySelectorAll('input[name="manager_review_rating"]').forEach(function (r) { r.checked = r.value === String(value); });
                $('review-rating-label').textContent = value ? value + ' / 5 · ' + labels[value] : 'Pick a rating';
                var n = parseInt(value, 10) || 0, gap = self && n ? self - n : 0;
                $('review-gap').classList.toggle('d-none', Math.abs(gap) < 2);
                $('review-gap').querySelector('span').textContent = Math.abs(gap) >= 2 ? 'That is ' + Math.abs(gap) + ' points ' + (gap > 0 ? 'below' : 'above') + ' their own rating. Say why in your comments.' : '';
                // The lowest ratings and the top one need a reason behind them.
                var needs = n === 1 || n === 2 || n === 5;
                $('review-required').classList.toggle('d-none', !needs);
                $('review-comments').required = needs;
            }
            function count() { $('review-count').textContent = $('review-comments').value.length + ' / 2000'; }
            function fill(d) {
                self = parseInt(d.self, 10) || 0;
                $('review-form').action = d.action;
                $('review-id').value = d.id;
                $('review-modal-title').textContent = (d.rating ? 'Edit your review of ' : 'Review ') + d.who;
                $('review-self-rating').textContent = self ? self + ' / 5 · ' + labels[self] : 'No self review yet';
                $('review-self-comments').textContent = d.selfComments || (self ? 'No comments.' : 'You can still write your review; theirs can follow.');
                $('review-evidence').textContent = d.evidence || 'No goals or KPIs recorded for this cycle.';
                $('review-comments').value = d.comments || '';
                $('review-submit').textContent = d.rating ? 'Save changes' : 'Submit review';
                setRating(d.rating || '');
                count();
            }
            modal.addEventListener('show.bs.modal', function (event) { if (event.relatedTarget) fill(event.relatedTarget.dataset); });
            modal.querySelectorAll('input[name="manager_review_rating"]').forEach(function (r) { r.addEventListener('change', function () { setRating(r.value); }); });
            $('review-comments').addEventListener('input', count);
            $('review-form').addEventListener('submit', function () { setTimeout(function () { $('review-submit').disabled = true; }, 0); });
            // A refused save: reopen the form with what was typed.
            var reopen = {{ $reopenId }};
            var button = reopen ? document.querySelector('[data-bs-target="#review-modal"][data-id="' + reopen + '"]') : null;
            if (button) {
                var typed = @json($typed);
                fill(button.dataset);
                $('review-comments').value = typed.comments;
                setRating(typed.rating);
                count();
                bootstrap.Modal.getOrCreateInstance(modal).show();
            }
        })();
    </script>
@endpush
