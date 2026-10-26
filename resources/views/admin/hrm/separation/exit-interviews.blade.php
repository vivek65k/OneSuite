@extends('layouts.admin')

@section('title', 'Exit Interview')

@php
    use Illuminate\Support\Str;

    $pageUrl = fn (array $overrides = []) => route('admin.exit-interviews.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $tabs = [
        '' => ['label' => 'All leavers', 'tone' => 'indigo', 'count' => $counts['all']],
        'due' => ['label' => 'To interview', 'tone' => 'amber', 'count' => $counts['due']],
        'done' => ['label' => 'Interviewed', 'tone' => 'success', 'count' => $counts['done']],
        'missed' => ['label' => 'Left without one', 'tone' => 'rose', 'count' => $counts['missed']],
    ];
    $filterValues = [
        'department_id' => $departments[$state['department_id']] ?? null,
        'rehire' => ['yes' => 'Yes', 'no' => 'No'][$state['rehire']] ?? null,
        'search' => $state['search'],
    ];
    $activeFilters = collect(['department_id' => 'Department', 'rehire' => 'Would rehire', 'search' => 'Search'])
        ->filter(fn ($label, $key) => filled($state[$key]))
        ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => $filterValues[$key] ?? 'Unknown']);
    $ratingLabels = [1 => 'Very unhappy', 2 => 'Unhappy', 3 => 'Mixed', 4 => 'Happy', 5 => 'Very happy'];
    // After a failed save, reopen the form the person was filling in.
    $reopenId = $errors->any() ? (int) old('_resignation') : 0;
    $typed = collect(['date' => old('interview_date'), 'rating' => old('rating'), 'rehire' => old('would_rehire'), 'feedback' => old('feedback')])
        ->map(fn ($value) => is_scalar($value) ? (string) $value : '')->all();
@endphp

@section('content')
    <x-page-title title="Exit Interview" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="To interview" :value="$kpis['due']" icon="ti-message-circle" :tone="$kpis['due'] ? 'amber' : 'success'" :hint="$kpis['due_week'] ? $kpis['due_week'].' leaving within a week' : ($kpis['due'] ? 'Before their last working day' : 'Nobody on notice is waiting')" />
        <x-kpi-card label="Interviewed" :value="$kpis['done']" icon="ti-circle-check" tone="indigo" :hint="$kpis['leavers'] ? 'of '.$kpis['leavers'].' '.Str::plural('leaver', $kpis['leavers']).($kpis['missed'] ? ' · '.$kpis['missed'].' left without one' : '') : 'No leavers yet'" />
        <x-kpi-card label="Average rating" :value="$kpis['rating'] !== null ? number_format($kpis['rating'], 1).' / 5' : '—'" icon="ti-star" :tone="$kpis['rating'] !== null && $kpis['rating'] < 3 ? 'rose' : 'teal'" :hint="$kpis['rated'] ? 'from '.$kpis['rated'].' '.Str::plural('rating', $kpis['rated']).($kpis['low'] ? ' · '.$kpis['low'].' rated 2 or less' : '') : 'No ratings given yet'" />
        <x-kpi-card label="Would rehire" :value="$kpis['rehire'] !== null ? $kpis['rehire'].'%' : '—'" icon="ti-user-check" :tone="$kpis['rehire'] !== null && $kpis['rehire'] < 50 ? 'rose' : 'success'" :hint="$kpis['asked'] ? $kpis['rehire_yes'].' of '.$kpis['asked'].' answered yes' : 'Not answered for anyone yet'" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.exit-interviews.index') }}">
            @if ($state['tab'] !== '')
                <input type="hidden" name="tab" value="{{ $state['tab'] }}">
            @endif
            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Interview status">
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
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] }}" placeholder="Search employee or feedback" aria-label="Search">
                    </div>
                    @if ($departments->count() > 1 || $state['department_id'])
                        <select name="department_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Department">
                            <option value="">All departments</option>
                            @foreach ($departments as $id => $name)
                                <option value="{{ $id }}" @selected($state['department_id'] === $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    @endif
                    <select name="rehire" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Would rehire">
                        <option value="">Rehire: any</option>
                        <option value="yes" @selected($state['rehire'] === 'yes')>Would rehire</option>
                        <option value="no" @selected($state['rehire'] === 'no')>Would not rehire</option>
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
                        <th>Leaving</th>
                        <th style="width: 40%">Exit interview</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($resignations as $r)
                        @php
                            $person = $r->employee;
                            $interview = $r->exitInterview;
                            $daysLeft = (int) today()->diffInDays($r->last_working_day, false);
                            $gone = $r->status === 'completed';
                            $own = $ownEmployeeId !== null && (int) $r->employee_id === (int) $ownEmployeeId;
                            $canSave = ! $own && ($interview ? $canUpdate : $canCreate);
                            $name = $person?->name ?? 'Removed employee';
                        @endphp
                        <tr class="{{ ! $interview && ! $gone ? 'is-pending-row' : '' }}">
                            <td>
                                <div class="list-user">
                                    <x-avatar :person="$person" />
                                    <div class="list-user-text">
                                        <div class="list-user-name">{{ $name }}</div>
                                        <div class="list-user-meta">{{ collect([$person?->employee_code, $person?->designation, $person?->department?->name])->filter()->implode(' · ') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="text-nowrap">{{ $r->last_working_day->format('M j, Y') }}</div>
                                <div class="payslip-parts {{ ! $gone && $daysLeft < 0 ? 'text-danger' : '' }}">
                                    {{ $gone ? 'left the company' : ($daysLeft > 0 ? 'last day in '.$daysLeft.' '.Str::plural('day', $daysLeft) : ($daysLeft === 0 ? 'last day is today' : 'last day passed '.abs($daysLeft).' '.Str::plural('day', abs($daysLeft)).' ago')) }}
                                </div>
                                @if ($r->reason)
                                    <div class="payslip-parts" title="{{ $r->reason }}">Reason given: {{ Str::limit($r->reason, 50) }}</div>
                                @endif
                            </td>
                            <td>
                                @if ($interview)
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        @if ($interview->rating)
                                            <span class="star-rating" title="{{ $interview->rating }} out of 5 · {{ $ratingLabels[$interview->rating] ?? '' }}" aria-label="{{ $interview->rating }} out of 5">
                                                @for ($i = 1; $i <= 5; $i++)
                                                    <i class="ti ti-star {{ $i <= $interview->rating ? 'is-on' : '' }}"></i>
                                                @endfor
                                            </span>
                                        @else
                                            <span class="font-12 text-muted">No rating</span>
                                        @endif
                                        @if (! is_null($interview->would_rehire))
                                            <span class="tag tone-{{ $interview->would_rehire ? 'success' : 'rose' }}"><i class="ti {{ $interview->would_rehire ? 'ti-user-check' : 'ti-user-x' }}"></i>{{ $interview->would_rehire ? 'Would rehire' : 'Would not rehire' }}</span>
                                        @endif
                                    </div>
                                    @if ($interview->feedback)
                                        <div class="font-13 mt-1" style="white-space: pre-line" title="{{ Str::length($interview->feedback) > 220 ? $interview->feedback : '' }}">{{ Str::limit($interview->feedback, 220) }}</div>
                                    @endif
                                    <div class="payslip-parts">{{ collect([$interview->interview_date?->format('M j, Y'), $interview->conductedBy?->name ? 'by '.$interview->conductedBy->name : null])->filter()->implode(' · ') }}</div>
                                @elseif ($gone)
                                    <span class="tag tone-rose"><i class="ti ti-alert-circle"></i>Left without an interview</span>
                                    <div class="payslip-parts">It can still be recorded if it took place</div>
                                @else
                                    <span class="tag tone-amber"><i class="ti ti-clock"></i>Not held yet</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="list-row-actions">
                                    @if ($own)
                                        <span class="font-12 text-muted" title="Someone else has to record your own exit interview">Your own exit</span>
                                    @elseif ($canSave)
                                        <button type="button" class="{{ $interview ? 'btn-icon' : 'btn btn-sm review-btn is-approve' }}" title="{{ $interview ? 'Edit interview' : 'Record the interview' }}" data-bs-toggle="modal" data-bs-target="#interview-modal"
                                                data-id="{{ $r->id }}" data-action="{{ route('admin.exit-interviews.store', $r) }}" data-who="{{ $name }}" data-resigned="{{ $r->resignation_date->toDateString() }}" data-last="{{ $r->last_working_day->toDateString() }}"
                                                data-date="{{ $interview?->interview_date?->toDateString() }}" data-rating="{{ $interview?->rating }}" data-rehire="{{ is_null($interview?->would_rehire) ? '' : (int) $interview->would_rehire }}" data-feedback="{{ $interview?->feedback }}" data-exists="{{ $interview ? 1 : 0 }}">
                                            @if ($interview)<i class="ti ti-edit font-16"></i>@else<i class="ti ti-message-circle"></i> Record @endif
                                        </button>
                                    @endif
                                    @if ($interview && $canDelete && ! $own)
                                        <form action="{{ route('admin.exit-interviews.destroy', $r) }}" method="POST" class="d-inline" onsubmit="return confirm(@js('Remove the exit interview with '.$name.'? The rating and feedback will be lost.'))">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon btn-icon-danger" title="Remove interview"><i class="ti ti-trash font-16"></i></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-5">
                                <i class="ti ti-message-circle font-24 d-block mb-2"></i>
                                @if ($activeFilters->isNotEmpty() || $state['tab'] !== '')
                                    <div class="fw-semibold text-body mb-1">Nobody matches</div>
                                    <span class="font-13"><a href="{{ route('admin.exit-interviews.index') }}">Show all leavers</a></span>
                                @else
                                    <div class="fw-semibold text-body mb-1">Nobody to interview</div>
                                    <span class="font-13">People appear here once their resignation is accepted.</span>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($canCreate || $canUpdate)
        <div class="modal fade" id="interview-modal" tabindex="-1" aria-labelledby="interview-modal-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="#" class="modal-content" id="interview-form">
                    @csrf
                    <input type="hidden" name="_resignation" id="interview-id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="interview-modal-title">Exit interview</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="font-13 text-muted mb-3" id="interview-intro"></div>
                        <label class="form-label" for="interview-date">Held on</label>
                        <input type="date" name="interview_date" id="interview-date" class="form-control" style="max-width: 220px" max="{{ today()->toDateString() }}" required>

                        <label class="form-label mt-3 d-block">How did they rate their time here? <span class="text-muted fw-normal">(optional)</span></label>
                        <div class="star-picker" role="radiogroup" aria-label="Rating">
                            {{-- Rendered 5 to 1 so the CSS ~ sibling selector can light up every lower star --}}
                            @for ($i = 5; $i >= 1; $i--)
                                <input type="radio" id="interview-rating-{{ $i }}" name="rating" value="{{ $i }}">
                                <label for="interview-rating-{{ $i }}" title="{{ $i }} · {{ $ratingLabels[$i] }}"><i class="ti ti-star"></i></label>
                            @endfor
                        </div>
                        <div class="star-picker-meta">
                            <span class="star-picker-label" id="interview-rating-label">Not rated</span>
                            <button type="button" class="btn btn-link btn-sm p-0 text-muted" id="interview-rating-clear" hidden>Clear rating</button>
                            <input type="radio" name="rating" value="" id="interview-rating-none" class="d-none" checked>
                        </div>

                        <label class="form-label mt-3 d-block">Would the company rehire them? <span class="text-muted fw-normal">(optional)</span></label>
                        <input type="hidden" name="would_rehire" id="interview-rehire" value="">
                        <div class="segmented" id="interview-rehire-options" role="group" aria-label="Would rehire">
                            <button type="button" class="segmented-item" data-value="1"><i class="ti ti-user-check"></i> Yes</button>
                            <button type="button" class="segmented-item" data-value="0"><i class="ti ti-user-x"></i> No</button>
                            <button type="button" class="segmented-item" data-value="">Not answered</button>
                        </div>

                        <label class="form-label mt-3 d-flex justify-content-between" for="interview-feedback"><span>What they said</span> <span class="text-muted fw-normal font-12" id="interview-count">0 / 2000</span></label>
                        <textarea name="feedback" id="interview-feedback" rows="4" maxlength="2000" class="form-control" placeholder="Why they are leaving, what worked, what should change"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-de-primary text-light" id="interview-submit">Record interview</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        (function () {
            var el = function (id) { return document.getElementById(id); };
            var modal = el('interview-modal');
            if (!modal) return;
            var today = @json(today()->toDateString());
            var labels = @json($ratingLabels);
            var nice = function (s) { return new Date(s + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }); };

            function setRating(value) {
                (el('interview-rating-' + value) || el('interview-rating-none')).checked = true;
                el('interview-rating-label').textContent = value ? value + ' / 5 · ' + labels[value] : 'Not rated';
                el('interview-rating-clear').hidden = !value;
            }
            function setRehire(value) {
                el('interview-rehire').value = value;
                el('interview-rehire-options').querySelectorAll('.segmented-item').forEach(function (b) { b.classList.toggle('active', b.dataset.value === value); });
            }
            function count() { el('interview-count').textContent = el('interview-feedback').value.length + ' / 2000'; }
            function fill(d) {
                el('interview-form').action = d.action;
                el('interview-id').value = d.id;
                el('interview-modal-title').textContent = (d.exists === '1' ? 'Edit exit interview' : 'Record exit interview');
                el('interview-intro').textContent = d.who + ' resigned on ' + nice(d.resigned) + '; last working day ' + nice(d.last) + '.';
                el('interview-date').min = d.resigned;
                el('interview-date').value = d.date || today;
                setRating(d.rating || '');
                setRehire(d.rehire || '');
                el('interview-feedback').value = d.feedback || '';
                el('interview-submit').textContent = d.exists === '1' ? 'Save changes' : 'Record interview';
                count();
            }

            modal.addEventListener('show.bs.modal', function (event) { if (event.relatedTarget) fill(event.relatedTarget.dataset); });
            modal.querySelectorAll('.star-picker input').forEach(function (input) { input.addEventListener('change', function () { setRating(input.value); }); });
            el('interview-rating-clear').addEventListener('click', function () { setRating(''); });
            el('interview-rehire-options').querySelectorAll('.segmented-item').forEach(function (b) { b.addEventListener('click', function () { setRehire(b.dataset.value); }); });
            el('interview-feedback').addEventListener('input', count);

            // A save that was refused: put back what was typed.
            var reopen = {{ $reopenId }};
            var button = reopen ? document.querySelector('[data-bs-target="#interview-modal"][data-id="' + reopen + '"]') : null;
            if (button) {
                var d = Object.assign({}, button.dataset, @json($typed));
                fill(d);
                bootstrap.Modal.getOrCreateInstance(modal).show();
            }
        })();
    </script>
@endpush
