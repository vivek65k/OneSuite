@extends('layouts.admin')

@section('title', 'Goals')

@php
    use App\Models\Goal;
    use Illuminate\Support\Str;

    $num = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
    $pageUrl = fn (array $overrides = []) => route('admin.team-goals.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $tabs = [
        '' => ['label' => 'All goals', 'tone' => 'indigo', 'count' => $counts['all']],
        'overdue' => ['label' => 'Overdue', 'tone' => 'rose', 'count' => $counts['overdue']],
        'in_progress' => ['label' => 'In progress', 'tone' => 'sky', 'count' => $counts['in_progress']],
        'not_started' => ['label' => 'Not started', 'tone' => 'slate', 'count' => $counts['not_started']],
        'completed' => ['label' => 'Completed', 'tone' => 'success', 'count' => $counts['completed']],
    ];
    $statusMeta = ['not_started' => ['slate', 'ti-circle-dashed'], 'in_progress' => ['sky', 'ti-clock'], 'completed' => ['success', 'ti-circle-check']];
    $cycleName = $cycle?->name ?? 'No cycle';
    $closed = $cycle?->status === 'closed';
    $without = $kpis['without'];
    // A refused edit reopens its popup; a refused new goal keeps what was typed in the form.
    $fields = ['employee_id', 'appraisal_cycle_id', 'title', 'description', 'weight', 'target_date'];
    $editing = old('_method') === 'PUT' && old('title') !== null ? (int) old('_goal') : 0;
    $newErrors = ! $editing && $errors->hasAny($fields);
    $typed = collect($fields)->mapWithKeys(fn ($field) => [$field => is_scalar(old($field)) ? (string) old($field) : ''])->all();
    $fresh = fn (string $field, string $default = '') => $newErrors ? $typed[$field] : $default;
@endphp

@section('content')
    <x-page-title title="Goals" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any() && ! $newErrors)
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    @unless ($hasTeam)
        <div class="card">
            <div class="card-body text-center text-muted py-5">
                <i class="ti ti-users font-24 d-block mb-2"></i>
                <div class="fw-semibold text-body mb-1">Nobody reports to you</div>
                <span class="font-13">You set goals for the people below you in the reporting line. HR sets who reports to whom on each employee's profile.</span>
            </div>
        </div>
    @else
        <div class="kpi-strip">
            <x-kpi-card label="Goals · {{ Str::limit($cycleName, 16) }}" :value="$kpis['goals']" icon="ti-flag" tone="indigo" :hint="$kpis['goals'] ? $kpis['done'].' completed' : 'None set yet'" />
            <x-kpi-card label="Average progress" :value="$kpis['progress'] !== null ? $kpis['progress'].'%' : '—'" icon="ti-trending-up" tone="teal" :hint="$kpis['due_soon'] ? $kpis['due_soon'].' due within 14 days' : 'Across every goal in this cycle'" />
            <x-kpi-card label="Overdue" :value="$kpis['overdue']" icon="ti-alert-triangle" :tone="$kpis['overdue'] ? 'rose' : 'success'" :hint="$kpis['overdue'] ? 'Past the target date and not completed' : 'Nothing is past its date'" :href="$kpis['overdue'] ? $pageUrl(['tab' => 'overdue', 'employee_id' => null]) : null" />
            <x-kpi-card label="People without goals" :value="$without->count()" icon="ti-user-x" :tone="$without->count() ? 'amber' : 'success'" :hint="$without->count() ? Str::limit($without->map(fn ($p) => Str::before($p->name, ' '))->implode(', '), 40) : ($kpis['unweighted'] ? $kpis['unweighted'].' with weights not adding up to 100%' : 'Everyone has at least one')" />
        </div>

        @if ($canAdd)
            <form method="POST" action="{{ route('admin.team-goals.store') }}" class="card form-section tone-indigo" id="goal-form">
                @csrf
                <div class="form-section-head">
                    <span class="form-section-icon"><i class="ti ti-flag"></i></span>
                    <div class="flex-grow-1">
                        <h5 class="form-section-title">Set a goal</h5>
                        <p class="form-section-sub">Something specific with a date. Weights say how much each goal counts; one person's weights in a cycle add up to 100% at most.</p>
                    </div>
                </div>
                <div class="form-section-body">
                    @if ($newErrors)
                        <div class="alert alert-danger py-2 font-13">@foreach (collect($fields)->flatMap(fn ($field) => $errors->get($field)) as $message)<div>{{ $message }}</div>@endforeach</div>
                    @endif
                    <div class="row g-3">
                        <div class="col-md-6 col-lg-3">
                            <label class="form-label" for="goal-person">For whom? <span class="text-danger">*</span></label>
                            <select name="employee_id" id="goal-person" class="form-select" required>
                                <option value="">Choose a team member</option>
                                @foreach ($people as $person)
                                    <option value="{{ $person->id }}" @selected($fresh('employee_id', (string) ($focus?->id ?? '')) === (string) $person->id)>{{ $person->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label class="form-label" for="goal-cycle">Appraisal cycle</label>
                            <select name="appraisal_cycle_id" id="goal-cycle" class="form-select">
                                <option value="">Not tied to a cycle</option>
                                @foreach ($openCycles as $option)
                                    <option value="{{ $option->id }}" data-end="{{ $option->period_end?->toDateString() }}" @selected($fresh('appraisal_cycle_id', (string) ($cycle?->id ?? '')) === (string) $option->id)>{{ $option->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label" for="goal-title">Goal <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="goal-title" class="form-control" value="{{ $fresh('title') }}" maxlength="255" placeholder="e.g. Close 20 new accounts" required>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label class="form-label" for="goal-weight">Weight (%)</label>
                            <input type="number" step="0.01" min="0" max="100" name="weight" id="goal-weight" class="form-control" value="{{ $fresh('weight') }}" placeholder="e.g. 25">
                            <div class="form-hint"><i class="ti ti-info-circle"></i> <span id="goal-weight-hint">Choose a person to see how much is free.</span></div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label class="form-label" for="goal-date">Target date</label>
                            <input type="date" name="target_date" id="goal-date" class="form-control" value="{{ $fresh('target_date') }}" min="2000-01-01" max="{{ $maxDate }}">
                            <div class="quick-chips">
                                <button type="button" class="quick-chip" data-date="month">End of month</button>
                                <button type="button" class="quick-chip" data-date="quarter">End of quarter</button>
                                <button type="button" class="quick-chip" data-date="cycle" id="goal-date-cycle" hidden>End of cycle</button>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label" for="goal-description">How it will be measured <span class="text-muted fw-normal">(optional)</span></label>
                            <textarea name="description" id="goal-description" rows="3" maxlength="2000" class="form-control" placeholder="What done looks like, and how you will both know">{{ $fresh('description') }}</textarea>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn-de-primary text-light px-4" id="goal-submit"><i class="ti ti-plus font-14 me-1"></i> Set goal</button>
                    </div>
                </div>
            </form>
        @endif

        <div class="card list-view">
            <form method="GET" action="{{ route('admin.team-goals.index') }}">
                @if ($state['tab'] !== '')
                    <input type="hidden" name="tab" value="{{ $state['tab'] }}">
                @endif
                <div class="list-toolbar">
                    <nav class="list-tabs" aria-label="Goal status">
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
                        @if ($cycles->isNotEmpty())
                            <select name="cycle" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Appraisal cycle">
                                @foreach ($cycles as $option)
                                    <option value="{{ $option->id }}" @selected($cycleKey === $option->id)>{{ $option->name }}{{ $option->status === 'closed' ? ' (closed)' : '' }}</option>
                                @endforeach
                                @if ($hasUncycled || $cycleKey === 'none')
                                    <option value="none" @selected($cycleKey === 'none')>Not tied to a cycle</option>
                                @endif
                            </select>
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
                <div class="list-subbar"><span class="font-13 text-muted"><i class="ti ti-lock"></i> This cycle is closed, so its goals can no longer be changed.</span></div>
            @endif

            <div class="table-responsive table-responsive-sticky">
                <table class="table table-hover list-table mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Team member</th>
                            <th style="width: 30%">Goal</th>
                            <th>Target date</th>
                            <th style="min-width: 250px">Progress</th>
                            <th class="text-end"><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($goals as $goal)
                            @php
                                $person = $goal->employee;
                                $status = $statusMeta[$goal->status] ?? ['slate', 'ti-point'];
                                $tone = $goal->status === 'completed' ? 'success' : ($goal->overdue ? 'rose' : ($goal->progress_percent > 0 ? 'indigo' : 'slate'));
                            @endphp
                            <tr class="{{ $goal->overdue ? 'is-pending-row' : '' }}">
                                <td>
                                    <div class="list-user">
                                        <x-avatar :person="$person" />
                                        <div class="list-user-text">
                                            <div class="list-user-name">{{ $person->name }}</div>
                                            <div class="list-user-meta">{{ collect([$person->designation ?: $person->department?->name, $person->is_direct ? null : 'indirect'])->filter()->implode(' · ') ?: $person->employee_code }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $goal->title }}</div>
                                    @if ($goal->description)
                                        <div class="font-13 text-muted" title="{{ Str::length($goal->description) > 140 ? $goal->description : '' }}">{{ Str::limit($goal->description, 140) }}</div>
                                    @endif
                                    @if ((float) $goal->weight > 0)
                                        <span class="tag tone-slate mt-1" title="Share of {{ $person->name }}'s goals in this cycle">{{ $num($goal->weight) }}% weight</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    @if ($goal->target_date)
                                        {{ $goal->target_date->format('M j, Y') }}
                                        <div class="payslip-parts {{ $goal->overdue ? 'text-danger' : '' }}">
                                            @if ($goal->status === 'completed')
                                                done
                                            @elseif ($goal->overdue)
                                                {{ abs($goal->days_left) }} {{ Str::plural('day', abs($goal->days_left)) }} overdue
                                            @else
                                                {{ $goal->days_left === 0 ? 'due today' : $goal->days_left.' '.Str::plural('day', $goal->days_left).' left' }}
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted">No date</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="mini-progress tone-{{ $tone }}">
                                        <div class="mini-progress-bar is-toned"><span style="width: {{ (int) $goal->progress_percent }}%"></span></div>
                                        <span class="mini-progress-label">{{ (int) $goal->progress_percent }}%</span>
                                    </div>
                                    @if ($goal->lock)
                                        <div class="font-12 text-muted"><i class="ti ti-lock"></i> {{ $goal->lock }}</div>
                                    @else
                                        <form action="{{ route('admin.team-goals.update', $goal) }}" method="POST" class="d-flex align-items-center gap-2 mt-1">
                                            @csrf
                                            @method('PUT')
                                            <input type="range" name="progress_percent" min="0" max="100" step="5" value="{{ (int) $goal->progress_percent }}" class="form-range" style="width: 120px" aria-label="Progress for {{ $goal->title }}"
                                                   oninput="this.nextElementSibling.textContent = this.value + '%'; this.form.querySelector('button').disabled = Number(this.value) === {{ (int) $goal->progress_percent }}">
                                            <span class="font-12 fw-semibold" style="width: 36px">{{ (int) $goal->progress_percent }}%</span>
                                            <button type="submit" class="btn btn-sm btn-light" disabled>Update</button>
                                        </form>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    @unless ($goal->lock)
                                        <div class="list-row-actions">
                                            <button type="button" class="btn-icon" title="Edit this goal" data-bs-toggle="modal" data-bs-target="#edit-modal" data-id="{{ $goal->id }}" data-action="{{ route('admin.team-goals.update', $goal) }}" data-who="{{ $person->name }}"
                                                    data-cycle="{{ $goal->appraisal_cycle_id }}" data-title="{{ $goal->title }}" data-description="{{ $goal->description }}" data-weight="{{ $goal->weight !== null ? $num($goal->weight) : '' }}" data-date="{{ $goal->target_date?->toDateString() }}"><i class="ti ti-edit font-16"></i></button>
                                            <form action="{{ route('admin.team-goals.destroy', $goal) }}" method="POST" class="d-inline" onsubmit="return confirm(@js('Remove "'.$goal->title.'" from '.$person->name.'\'s goals?'.($goal->progress_percent > 0 ? ' It is at '.$goal->progress_percent.'% and that progress will be lost.' : '')))">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-icon btn-icon-danger" title="Remove this goal"><i class="ti ti-trash font-16"></i></button>
                                            </form>
                                        </div>
                                    @endunless
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">
                                    <i class="ti ti-flag font-24 d-block mb-2"></i>
                                    @if ($state['tab'] !== '' || $focus)
                                        <div class="fw-semibold text-body mb-1">No goals match</div>
                                        <span class="font-13"><a href="{{ $pageUrl(['tab' => null, 'employee_id' => null]) }}">Show all goals</a></span>
                                    @else
                                        <div class="fw-semibold text-body mb-1">No goals {{ $cycle ? 'in '.$cycle->name : 'outside a cycle' }} yet</div>
                                        <span class="font-13">{{ $canAdd ? 'Set the first one with the form above.' : 'This cycle is closed.' }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($perPerson->isNotEmpty())
            <div class="card list-view">
                <div class="list-toolbar"><span class="list-toolbar-title">By person · {{ $cycleName }}</span></div>
                <div class="table-responsive">
                    <table class="table list-table mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Team member</th>
                                <th>Goals</th>
                                <th style="width: 26%">Weight given out</th>
                                <th style="width: 26%">Progress</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($perPerson as $row)
                                @php $off = $row['goals'] > 0 && $row['weight'] > 0 && abs($row['weight'] - 100) > 0.01; @endphp
                                <tr>
                                    <td>
                                        <a href="{{ $pageUrl(['employee_id' => $row['employee']->id, 'tab' => null]) }}" class="list-user text-body" title="Show only {{ $row['employee']->name }}'s goals">
                                            <x-avatar :person="$row['employee']" />
                                            <div class="list-user-text"><div class="list-user-name">{{ $row['employee']->name }}</div></div>
                                        </a>
                                    </td>
                                    <td>
                                        @if ($row['goals'])
                                            {{ $row['done'] }} of {{ $row['goals'] }} done
                                            @if ($row['overdue'])
                                                <span class="text-danger font-12">· {{ $row['overdue'] }} overdue</span>
                                            @endif
                                        @else
                                            <span class="text-warning"><i class="ti ti-alert-triangle"></i> No goals yet</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($row['goals'])
                                            <div class="mini-progress tone-{{ $off ? 'amber' : ($row['weight'] > 0 ? 'success' : 'slate') }}">
                                                <div class="mini-progress-bar is-toned"><span style="width: {{ min(100, $row['weight']) }}%"></span></div>
                                                <span class="mini-progress-label">{{ $num($row['weight']) }}%</span>
                                            </div>
                                            <div class="font-12 text-muted">{{ $row['weight'] <= 0 ? 'No weights set; goals count equally' : ($off ? $num(100 - $row['weight']).'% still free' : 'Fully assigned') }}</div>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($row['progress'] !== null)
                                            <div class="mini-progress tone-teal">
                                                <div class="mini-progress-bar is-toned"><span style="width: {{ $row['progress'] }}%"></span></div>
                                                <span class="mini-progress-label">{{ $row['progress'] }}%</span>
                                            </div>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="modal fade" id="edit-modal" tabindex="-1" aria-labelledby="edit-modal-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="#" class="modal-content" id="edit-form">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_goal" id="edit-id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="edit-modal-title">Edit goal</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label" for="edit-title">Goal</label>
                        <input type="text" name="title" id="edit-title" class="form-control" maxlength="255" required>
                        <label class="form-label mt-3" for="edit-description">How it will be measured</label>
                        <textarea name="description" id="edit-description" rows="3" maxlength="2000" class="form-control"></textarea>
                        <div class="row g-3 mt-0">
                            <div class="col-sm-4"><label class="form-label" for="edit-weight">Weight (%)</label><input type="number" step="0.01" min="0" max="100" name="weight" id="edit-weight" class="form-control"></div>
                            <div class="col-sm-4"><label class="form-label" for="edit-date">Target date</label><input type="date" name="target_date" id="edit-date" class="form-control" min="2000-01-01" max="{{ $maxDate }}"></div>
                            <div class="col-sm-4">
                                <label class="form-label" for="edit-cycle">Cycle</label>
                                <select name="appraisal_cycle_id" id="edit-cycle" class="form-select">
                                    <option value="">None</option>
                                    @foreach ($openCycles as $option)
                                        <option value="{{ $option->id }}">{{ $option->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="font-12 text-muted mt-2"><i class="ti ti-info-circle"></i> Progress is changed with the slider in the list, not here.</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-de-primary text-light"><i class="ti ti-check font-14 me-1"></i> Save</button>
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
            var used = @json($weightsUsed);
            var pad = function (n) { return String(n).padStart(2, '0'); };
            var iso = function (d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); };
            var fmt = function (n) { return (Math.round(n * 100) / 100).toString(); };

            var form = $('goal-form');
            if (form) {
                var person = $('goal-person'), cycle = $('goal-cycle'), weight = $('goal-weight');
                // How much of the person's 100% is still free in the chosen cycle.
                function free() {
                    var hint = $('goal-weight-hint'), over = false;
                    if (!person.value) { hint.textContent = 'Choose a person to see how much is free.'; }
                    else {
                        var taken = used[person.value + ':' + (cycle.value || 'none')] || 0, left = Math.max(0, 100 - taken);
                        over = parseFloat(weight.value) > left + 0.001;
                        hint.textContent = over ? 'Only ' + fmt(left) + '% is free for them in this cycle.' : (taken ? fmt(taken) + '% already given out; ' + fmt(left) + '% free.' : 'None given out yet; all 100% is free.');
                    }
                    hint.parentElement.classList.toggle('text-danger', over);
                    $('goal-submit').disabled = over;
                    var end = cycle.selectedOptions[0] ? cycle.selectedOptions[0].dataset.end : '';
                    $('goal-date-cycle').hidden = !end;
                }
                [person, cycle].forEach(function (input) { input.addEventListener('change', free); });
                weight.addEventListener('input', free);
                form.querySelectorAll('[data-date]').forEach(function (chip) {
                    chip.addEventListener('click', function () {
                        var now = new Date(), to;
                        if (chip.dataset.date === 'month') to = new Date(now.getFullYear(), now.getMonth() + 1, 0);
                        else if (chip.dataset.date === 'quarter') to = new Date(now.getFullYear(), Math.floor(now.getMonth() / 3) * 3 + 3, 0);
                        else { $('goal-date').value = cycle.selectedOptions[0].dataset.end; return; }
                        $('goal-date').value = iso(to);
                    });
                });
                form.addEventListener('submit', function () { setTimeout(function () { $('goal-submit').disabled = true; }, 0); });
                free();
            }

            var modal = $('edit-modal');
            if (!modal) return;
            function fill(d) {
                $('edit-form').action = d.action;
                $('edit-id').value = d.id;
                $('edit-modal-title').textContent = 'Edit ' + d.who + '’s goal';
                $('edit-title').value = d.title || '';
                $('edit-description').value = d.description || '';
                $('edit-weight').value = d.weight || '';
                $('edit-date').value = d.date || '';
                $('edit-cycle').value = d.cycle || '';
            }
            modal.addEventListener('show.bs.modal', function (event) { if (event.relatedTarget) fill(event.relatedTarget.dataset); });
            // A refused edit: reopen the popup with what was typed.
            var reopen = {{ $editing }};
            var button = reopen ? document.querySelector('[data-bs-target="#edit-modal"][data-id="' + reopen + '"]') : null;
            if (button) {
                var typed = @json($typed);
                fill(Object.assign({}, button.dataset, { title: typed.title, description: typed.description, weight: typed.weight, date: typed.target_date, cycle: typed.appraisal_cycle_id }));
                bootstrap.Modal.getOrCreateInstance(modal).show();
            }
        })();
    </script>
@endpush
