@extends('layouts.admin')

@section('title', 'My Performance')

@php
    use Illuminate\Support\Str;

    $num = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
    $ratingLabels = [1 => 'Needs improvement', 2 => 'Below expectations', 3 => 'Meets expectations', 4 => 'Exceeds expectations', 5 => 'Outstanding'];
    $stageMeta = [
        'self_due' => ['amber', 'ti-edit', 'Your self review is due'],
        'with_manager' => ['indigo', 'ti-clock', 'With your manager'],
        'finalising' => ['violet', 'ti-clock', 'Being finalised'],
        'completed' => ['success', 'ti-circle-check', 'Completed'],
        'missed' => ['slate', 'ti-lock', 'Cycle closed without a self review'],
    ];
    $stars = fn ($rating) => collect(range(1, 5))->map(fn ($i) => '<i class="ti ti-star '.($i <= round((float) $rating) ? 'is-on' : '').'"></i>')->implode('');
    $latest = $summary['latest'];
    $goalTone = fn ($g) => $g->status === 'completed' ? 'success' : ($g->overdue ? 'rose' : ($g->progress_percent > 0 ? 'indigo' : 'slate'));
    // After a refused save, reopen the self-review form that was being filled in.
    $reopenId = $errors->hasAny(['self_review_rating', 'self_review_comments']) ? (int) old('_appraisal') : 0;
    $typed = collect(['rating' => old('self_review_rating'), 'comments' => old('self_review_comments')])->map(fn ($value) => is_scalar($value) ? (string) $value : '')->all();
@endphp

@section('content')
    <x-page-title title="My Performance" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Latest rating" :value="$latest && $latest->overall_rating !== null ? $num($latest->overall_rating).' / 5' : '—'" icon="ti-star" tone="indigo" :hint="$latest ? ($latest->appraisalCycle?->name ?? 'Last appraisal').($latest->overall_rating !== null ? ' · '.($ratingLabels[(int) round((float) $latest->overall_rating)] ?? '') : '') : 'No completed appraisal yet'" />
        <x-kpi-card label="Self reviews due" :value="$summary['self_due']" icon="ti-edit" :tone="$summary['self_due'] ? 'amber' : 'success'" :hint="$summary['self_due'] ? 'Write yours below' : ($summary['in_review'] ? $summary['in_review'].' with your manager' : 'Nothing is waiting on you')" />
        <x-kpi-card label="Goal progress" :value="$summary['goal_progress'] !== null ? $summary['goal_progress'].'%' : '—'" icon="ti-flag" :tone="$summary['goal_overdue'] ? 'rose' : 'teal'" :hint="$summary['goal_count'] ? $summary['goal_done'].' of '.$summary['goal_count'].' done'.($summary['goal_overdue'] ? ' · '.$summary['goal_overdue'].' overdue' : '') : 'No goals set'" />
        <x-kpi-card label="KPI achievement" :value="$summary['kpi_achievement'] !== null ? $summary['kpi_achievement'].'%' : '—'" icon="ti-trending-up" :tone="$summary['kpi_achievement'] !== null && $summary['kpi_achievement'] < 80 ? 'amber' : 'success'" :hint="$summary['kpi_count'] ? ($summary['kpi_scored'] ? $summary['kpi_on_target'].' of '.$summary['kpi_scored'].' on target' : 'No results recorded yet') : 'No KPIs assigned'" />
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><span class="list-toolbar-title">Appraisals</span></div>
        <div class="table-responsive">
            <table class="table list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Cycle</th>
                        <th style="width: 30%">My self review</th>
                        <th style="width: 30%">Manager's review</th>
                        <th>Outcome</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($appraisals as $a)
                        @php
                            $stage = $stageMeta[$a->stage];
                            $cycleRow = $a->appraisalCycle;
                            $increment = $increments[$a->id] ?? null;
                        @endphp
                        <tr class="{{ $a->stage === 'self_due' ? 'is-pending-row' : '' }}">
                            <td>
                                <div class="fw-semibold">{{ $cycleRow?->name ?? 'Appraisal' }}</div>
                                @if ($cycleRow?->period_start && $cycleRow?->period_end)
                                    <div class="payslip-parts">{{ $cycleRow->period_start->format('M Y') }} – {{ $cycleRow->period_end->format('M Y') }}</div>
                                @endif
                                <span class="tag tone-{{ $stage[0] }} mt-1"><i class="ti {{ $stage[1] }}"></i>{{ $stage[2] }}</span>
                            </td>
                            <td>
                                @if ($a->self_review_rating !== null)
                                    <span class="star-rating" title="{{ $a->self_review_rating }} out of 5">{!! $stars($a->self_review_rating) !!}</span>
                                    <span class="font-12 text-muted ms-1">{{ $ratingLabels[$a->self_review_rating] ?? '' }}</span>
                                    @if ($a->self_review_comments)
                                        <div class="font-13 mt-1" style="white-space: pre-line" title="{{ Str::length($a->self_review_comments) > 240 ? $a->self_review_comments : '' }}">{{ Str::limit($a->self_review_comments, 240) }}</div>
                                    @endif
                                    <div class="payslip-parts">submitted {{ $a->self_review_submitted_at?->format('M j, Y') }}</div>
                                @else
                                    <span class="text-muted font-13">{{ $a->can_review ? 'Not written yet' : 'Not submitted' }}</span>
                                @endif
                                @if ($a->can_review)
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-sm {{ $a->self_review_rating === null ? 'review-btn is-approve' : 'btn-light' }}" data-bs-toggle="modal" data-bs-target="#review-modal"
                                                data-id="{{ $a->id }}" data-action="{{ route('admin.my-performance.self-review', $a) }}" data-cycle="{{ $cycleRow?->name ?? 'this appraisal' }}" data-rating="{{ $a->self_review_rating }}" data-comments="{{ $a->self_review_comments }}">
                                            <i class="ti ti-edit"></i> {{ $a->self_review_rating === null ? 'Write my self review' : 'Edit' }}
                                        </button>
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if ($a->released && $a->manager_review_rating !== null)
                                    <span class="star-rating" title="{{ $a->manager_review_rating }} out of 5">{!! $stars($a->manager_review_rating) !!}</span>
                                    <span class="font-12 text-muted ms-1">{{ $ratingLabels[$a->manager_review_rating] ?? '' }}</span>
                                    @if ($a->manager_review_comments)
                                        <div class="font-13 mt-1" style="white-space: pre-line" title="{{ Str::length($a->manager_review_comments) > 240 ? $a->manager_review_comments : '' }}">{{ Str::limit($a->manager_review_comments, 240) }}</div>
                                    @endif
                                    <div class="payslip-parts">{{ collect([$a->managerReviewedBy?->name ? 'by '.$a->managerReviewedBy->name : null, $a->manager_review_submitted_at?->format('M j, Y')])->filter()->implode(' · ') }}</div>
                                @elseif ($a->manager_review_rating !== null)
                                    <span class="font-13 text-muted"><i class="ti ti-lock"></i> Done. You will see it once the appraisal is completed.</span>
                                @else
                                    <span class="font-13 text-muted">{{ $a->stage === 'self_due' ? 'Starts after your self review' : ($a->released ? 'No manager review recorded' : 'Not reviewed yet') }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($a->released)
                                    @if ($a->overall_rating !== null)
                                        <div class="fw-bold font-18">{{ $num($a->overall_rating) }} <span class="font-12 text-muted fw-normal">/ 5</span></div>
                                        <div class="font-12 text-muted">{{ $ratingLabels[(int) round((float) $a->overall_rating)] ?? '' }}</div>
                                    @else
                                        <span class="text-muted">No overall rating</span>
                                    @endif
                                    <div class="payslip-parts">completed {{ $a->completed_at?->format('M j, Y') }}</div>
                                    @if ($a->feedback360s_count)
                                        <div class="payslip-parts">360° feedback: {{ $num(round((float) $a->feedback360s_avg_rating, 1)) }} / 5 from {{ $a->feedback360s_count }} {{ Str::plural('colleague', $a->feedback360s_count) }}</div>
                                    @endif
                                    @if ($increment)
                                        <span class="tag tone-success mt-1"><i class="ti ti-trending-up"></i>{{ $num($increment->increment_percent) }}% increment from {{ $increment->effective_date?->format('M j, Y') }}</span>
                                    @endif
                                @else
                                    <span class="text-muted font-13">Shared when completed</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-5">
                                <i class="ti ti-star font-24 d-block mb-2"></i>
                                <div class="fw-semibold text-body mb-1">No appraisals yet</div>
                                <span class="font-13">You will see your appraisal here when HR starts a review cycle for you.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card form-section tone-teal mb-0 h-100">
                <div class="form-section-head form-section-head-compact">
                    <span class="form-section-icon form-section-icon-sm"><i class="ti ti-flag"></i></span>
                    <h5 class="form-section-title">My goals{{ $cycle ? ' · '.$cycle->name : '' }}</h5>
                    @if ($cycles->count() > 1)
                        <form method="GET" action="{{ route('admin.my-performance.index') }}" class="ms-auto">
                            <select name="cycle" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Appraisal cycle">
                                @foreach ($cycles as $option)
                                    <option value="{{ $option->id }}" @selected($option->id === $cycle?->id)>{{ $option->name }}{{ $option->status === 'closed' ? ' (closed)' : '' }}</option>
                                @endforeach
                            </select>
                        </form>
                    @elseif ($goals->isNotEmpty())
                        <span class="count-pill ms-auto">{{ $summary['goal_done'] }} / {{ $summary['goal_count'] }} done</span>
                    @endif
                </div>
                <div class="form-section-body">
                    @forelse ($goals as $goal)
                        <div class="py-3 {{ $loop->last ? '' : 'border-bottom' }}">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div style="min-width: 0">
                                    <div class="fw-semibold">{{ $goal->title }}</div>
                                    @if ($goal->description)
                                        <div class="font-13 text-muted" title="{{ Str::length($goal->description) > 160 ? $goal->description : '' }}">{{ Str::limit($goal->description, 160) }}</div>
                                    @endif
                                </div>
                                @if ((float) $goal->weight > 0)
                                    <span class="tag tone-slate text-nowrap" title="Share of your overall goals">{{ $num($goal->weight) }}% weight</span>
                                @endif
                            </div>
                            <div class="mini-progress tone-{{ $goalTone($goal) }} mt-2">
                                <div class="mini-progress-bar is-toned"><span style="width: {{ (int) $goal->progress_percent }}%"></span></div>
                                <span class="mini-progress-label">{{ (int) $goal->progress_percent }}%</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-1">
                                <span class="font-12 {{ $goal->overdue ? 'text-danger' : 'text-muted' }}">
                                    @if ($goal->status === 'completed')
                                        <i class="ti ti-circle-check text-success"></i> Completed
                                    @elseif ($goal->target_date)
                                        {{ $goal->overdue ? 'Was due '.$goal->target_date->format('M j, Y').' · '.abs($goal->days_left).' '.Str::plural('day', abs($goal->days_left)).' overdue' : 'Due '.$goal->target_date->format('M j, Y').($goal->days_left === 0 ? ' · today' : ' · '.$goal->days_left.' '.Str::plural('day', $goal->days_left).' left') }}
                                    @else
                                        No target date
                                    @endif
                                </span>
                                @if ($goal->locked)
                                    <span class="font-12 text-muted"><i class="ti ti-lock"></i> Cycle closed</span>
                                @else
                                    <form action="{{ route('admin.my-performance.goal', $goal) }}" method="POST" class="d-flex align-items-center gap-2">
                                        @csrf
                                        @method('PUT')
                                        <input type="range" name="progress_percent" min="0" max="100" step="5" value="{{ (int) $goal->progress_percent }}" class="form-range" style="width: 130px" aria-label="Progress for {{ $goal->title }}" oninput="this.nextElementSibling.textContent = this.value + '%'; this.form.querySelector('button').disabled = Number(this.value) === {{ (int) $goal->progress_percent }}">
                                        <span class="font-12 fw-semibold" style="width: 36px">{{ (int) $goal->progress_percent }}%</span>
                                        <button type="submit" class="btn btn-sm btn-light" disabled>Update</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <span class="text-muted font-13">No goals have been set for you{{ $cycle ? ' in '.$cycle->name : '' }}. Your manager or HR adds them.</span>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card form-section tone-violet mb-0 h-100">
                <div class="form-section-head form-section-head-compact">
                    <span class="form-section-icon form-section-icon-sm"><i class="ti ti-trending-up"></i></span>
                    <h5 class="form-section-title">My KPIs</h5>
                    @if ($summary['kpi_scored'])
                        <span class="count-pill ms-auto">{{ $summary['kpi_on_target'] }} / {{ $summary['kpi_scored'] }} on target</span>
                    @endif
                </div>
                <div class="form-section-body">
                    @forelse ($kpis as $row)
                        @php
                            $achieved = $row->achievementPercent();
                            $unit = $row->kpi?->unit;
                            $tone = $achieved === null ? 'slate' : ($achieved >= 100 ? 'success' : ($achieved >= 70 ? 'amber' : 'rose'));
                        @endphp
                        <div class="py-2 {{ $loop->last ? '' : 'border-bottom' }}">
                            <div class="d-flex justify-content-between gap-2 font-13">
                                <span class="fw-semibold" @if ($row->kpi?->description) title="{{ $row->kpi->description }}" @endif>{{ $row->kpi?->name ?? 'Removed KPI' }}</span>
                                <span class="text-nowrap">{{ $row->actual_value !== null ? $num($row->actual_value) : '—' }} <span class="text-muted">of {{ $num($row->target_value) }}{{ $unit ? ' '.$unit : '' }}</span></span>
                            </div>
                            <div class="mini-progress tone-{{ $tone }}">
                                <div class="mini-progress-bar is-toned"><span style="width: {{ $achieved === null ? 0 : min(100, round($achieved)) }}%"></span></div>
                                <span class="mini-progress-label">{{ $achieved === null ? 'no result yet' : $num(round($achieved)).'%' }}</span>
                            </div>
                            @if ((float) $row->weight > 0)
                                <div class="font-12 text-muted">{{ $num($row->weight) }}% weight</div>
                            @endif
                        </div>
                    @empty
                        <span class="text-muted font-13">No KPIs are assigned to you{{ $cycle ? ' in '.$cycle->name : '' }}.</span>
                    @endforelse
                    @if ($kpis->isNotEmpty())
                        <div class="font-12 text-muted mt-3"><i class="ti ti-info-circle"></i> Results are recorded by your manager or HR.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="review-modal" tabindex="-1" aria-labelledby="review-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="#" class="modal-content" id="review-form">
                @csrf
                <input type="hidden" name="_appraisal" id="review-id">
                <div class="modal-header">
                    <h5 class="modal-title" id="review-modal-title">My self review</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="font-13 text-muted mb-3" id="review-intro"></div>
                    <label class="form-label d-block">How would you rate your own performance? <span class="text-danger">*</span></label>
                    <div class="star-picker" role="radiogroup" aria-label="Self rating">
                        {{-- Rendered 5 to 1 so the CSS ~ sibling selector can light up every lower star --}}
                        @for ($i = 5; $i >= 1; $i--)
                            <input type="radio" id="review-rating-{{ $i }}" name="self_review_rating" value="{{ $i }}" required>
                            <label for="review-rating-{{ $i }}" title="{{ $i }} · {{ $ratingLabels[$i] }}"><i class="ti ti-star"></i></label>
                        @endfor
                    </div>
                    <div class="star-picker-meta"><span class="star-picker-label" id="review-rating-label">Pick a rating</span></div>

                    <label class="form-label mt-3 d-flex justify-content-between" for="review-comments"><span>What went well, and what would you do differently?</span> <span class="text-muted fw-normal font-12" id="review-count">0 / 2000</span></label>
                    <textarea name="self_review_comments" id="review-comments" rows="5" maxlength="2000" class="form-control" placeholder="Achievements, challenges, what you learned, what you need"></textarea>
                    <div class="font-12 text-muted mt-2"><i class="ti ti-info-circle"></i> You can change this until your manager has reviewed it.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-de-primary text-light" id="review-submit">Submit</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var $ = function (id) { return document.getElementById(id); };
            var modal = $('review-modal'), labels = @json($ratingLabels);
            function setRating(value) {
                modal.querySelectorAll('input[name="self_review_rating"]').forEach(function (r) { r.checked = r.value === String(value); });
                $('review-rating-label').textContent = value ? value + ' / 5 · ' + labels[value] : 'Pick a rating';
            }
            function count() { $('review-count').textContent = $('review-comments').value.length + ' / 2000'; }
            function fill(d) {
                $('review-form').action = d.action;
                $('review-id').value = d.id;
                $('review-modal-title').textContent = (d.rating ? 'Edit my self review' : 'My self review');
                $('review-intro').textContent = 'For ' + d.cycle + '.';
                setRating(d.rating || '');
                $('review-comments').value = d.comments || '';
                $('review-submit').textContent = d.rating ? 'Save changes' : 'Submit';
                count();
            }
            modal.addEventListener('show.bs.modal', function (event) { if (event.relatedTarget) fill(event.relatedTarget.dataset); });
            modal.querySelectorAll('input[name="self_review_rating"]').forEach(function (r) { r.addEventListener('change', function () { setRating(r.value); }); });
            $('review-comments').addEventListener('input', count);
            // A refused save: reopen the form with what was typed.
            var reopen = {{ $reopenId }};
            var button = reopen ? document.querySelector('[data-bs-target="#review-modal"][data-id="' + reopen + '"]') : null;
            if (button) {
                var typed = @json($typed);
                fill(button.dataset);
                setRating(typed.rating);
                $('review-comments').value = typed.comments;
                count();
                bootstrap.Modal.getOrCreateInstance(modal).show();
            }
        })();
    </script>
@endpush
