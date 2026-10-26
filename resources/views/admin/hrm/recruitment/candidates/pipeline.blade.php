@extends('layouts.admin')

@section('title', 'Hiring Pipeline')

@php
    $stageMeta = [
        'applied' => ['tone' => 'slate', 'icon' => 'ti-inbox'],
        'screening' => ['tone' => 'sky', 'icon' => 'ti-file-search'],
        'interview' => ['tone' => 'violet', 'icon' => 'ti-message-circle-2'],
        'offer' => ['tone' => 'amber', 'icon' => 'ti-file-certificate'],
        'hired' => ['tone' => 'success', 'icon' => 'ti-award'],
        'rejected' => ['tone' => 'danger', 'icon' => 'ti-circle-x'],
    ];

    $filterLabels = ['search' => 'Search', 'job_position_id' => 'Applied for', 'source' => 'Source'];
    $activeFilters = collect($filterLabels)
        ->filter(fn ($label, $key) => filled($state[$key] ?? null))
        ->map(fn ($label, $key) => [
            'key' => $key,
            'label' => $label,
            'value' => $key === 'job_position_id' ? ($jobPositions[$state[$key]] ?? $state[$key]) : $state[$key],
        ]);

    // The pipeline isn't session-persisted, so the bare route is a real reset.
    $boardUrl = fn (array $overrides = []) => route('admin.candidates.pipeline', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));

    $total = $grouped->flatten(1)->count();
    $active = collect(['applied', 'screening', 'interview', 'offer'])->sum(fn ($stage) => ($grouped[$stage] ?? collect())->count());
    $hired = ($grouped['hired'] ?? collect())->count();
    $decided = $hired + ($grouped['rejected'] ?? collect())->count();

    $avatarTones = ['indigo', 'teal', 'pink', 'amber', 'violet', 'sky'];
    $canUpdate = auth()->user()->can('update_candidate');
@endphp

@section('content')
    <x-page-title title="Hiring Pipeline" :breadcrumbs="['Candidates' => route('admin.candidates.index')]" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="On the board" :value="$total" icon="ti-layout-kanban" tone="indigo" />
        <x-kpi-card label="Active" :value="$active" icon="ti-chart-arrows" tone="sky" hint="Applied to offer" />
        <x-kpi-card label="Hired" :value="$hired" icon="ti-award" tone="success" />
        <x-kpi-card label="Hire rate" :value="$decided ? round($hired / $decided * 100).'%' : '—'" icon="ti-chart-donut-3" tone="pink" hint="Of decided candidates" />
    </div>

    <div class="card list-view mb-3">
        <form method="GET" action="{{ route('admin.candidates.pipeline') }}">
            <div class="list-toolbar">
                <div class="list-toolbar-title">
                    <span class="list-toolbar-title-icon tone-violet"><i class="ti ti-layout-kanban"></i></span>
                    <div>
                        <div class="fw-semibold">Pipeline board</div>
                        <div class="text-muted font-12">
                            @if ($canUpdate)
                                Drag a card to another column to change its stage
                            @else
                                {{ $total }} {{ Str::plural('candidate', $total) }}
                            @endif
                        </div>
                    </div>
                </div>

                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] ?? '' }}" placeholder="Search name or email" aria-label="Search candidates">
                    </div>
                    <select name="job_position_id" class="form-select form-select-sm list-inline-select" aria-label="Applied for" onchange="this.form.submit()">
                        <option value="">All positions</option>
                        @foreach ($jobPositions as $id => $title)
                            <option value="{{ $id }}" @selected((string) ($state['job_position_id'] ?? '') === (string) $id)>{{ $title }}</option>
                        @endforeach
                    </select>
                    @if ($sources->isNotEmpty())
                        <select name="source" class="form-select form-select-sm list-inline-select" aria-label="Source" onchange="this.form.submit()">
                            <option value="">All sources</option>
                            @foreach ($sources as $source)
                                <option value="{{ $source }}" @selected(($state['source'] ?? '') === $source)>{{ $source }}</option>
                            @endforeach
                        </select>
                    @endif
                    <a href="{{ route('admin.candidates.index') }}" class="btn btn-light btn-sm" title="List view"><i class="ti ti-list font-14 me-1"></i> List</a>
                    @can('create', \App\Models\Candidate::class)
                        <a href="{{ route('admin.candidates.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-user-plus font-14 me-1"></i> Add candidate</a>
                    @endcan
                </div>
            </div>
        </form>

        @if ($activeFilters->isNotEmpty())
            <div class="list-subbar border-bottom-0">
                <div class="list-chips">
                    @foreach ($activeFilters as $filter)
                        <a href="{{ $boardUrl([$filter['key'] => null]) }}" class="filter-chip" title="Remove filter">
                            {{ $filter['label'] }}: <strong>{{ $filter['value'] }}</strong>
                            <i class="ti ti-x"></i>
                        </a>
                    @endforeach
                    <a href="{{ route('admin.candidates.pipeline') }}" class="list-clear-all">Clear all</a>
                </div>
            </div>
        @endif
    </div>

    <div class="kanban" id="pipeline-board" data-csrf="{{ csrf_token() }}" @if ($canUpdate) data-draggable="1" @endif>
        @foreach (\App\Models\Candidate::STAGES as $stage)
            @php
                $meta = $stageMeta[$stage] ?? ['tone' => 'slate', 'icon' => 'ti-point'];
                $cards = $grouped[$stage] ?? collect();
            @endphp
            <section class="kanban-col tone-{{ $meta['tone'] }}" data-stage="{{ $stage }}" aria-label="{{ Str::headline($stage) }}">
                <header class="kanban-col-head">
                    <span class="kanban-col-icon"><i class="ti {{ $meta['icon'] }}"></i></span>
                    <span class="kanban-col-title">{{ Str::headline($stage) }}</span>
                    <span class="kanban-col-count" data-count>{{ $cards->count() }}</span>
                </header>

                <div class="kanban-col-body" data-dropzone>
                    @foreach ($cards as $candidate)
                        @php
                            $initials = collect(preg_split('/\s+/', trim($candidate->name)))
                                ->filter()->take(2)
                                ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                                ->implode('');
                        @endphp
                        <article class="kanban-card" data-id="{{ $candidate->id }}" data-url="{{ route('admin.candidates.stage', $candidate) }}" @if ($canUpdate) draggable="true" @endif>
                            <div class="kanban-card-top">
                                <span class="list-avatar list-avatar-{{ $avatarTones[crc32($candidate->email.$candidate->name) % count($avatarTones)] }}" aria-hidden="true">{{ $initials }}</span>
                                <div class="min-w-0 flex-grow-1">
                                    <div class="kanban-card-name">
                                        @if ($canUpdate)
                                            <a href="{{ route('admin.candidates.edit', $candidate) }}">{{ $candidate->name }}</a>
                                        @else
                                            {{ $candidate->name }}
                                        @endif
                                    </div>
                                    <div class="kanban-card-job">
                                        <i class="ti ti-briefcase"></i>
                                        {{ $candidate->jobPosition?->title ?: 'General application' }}
                                    </div>
                                </div>
                                @if ($canUpdate)
                                    <div class="dropdown">
                                        <button class="btn-icon kanban-card-menu" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" title="Move to stage">
                                            <i class="ti ti-dots-vertical font-16"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><h6 class="dropdown-header">Move to</h6></li>
                                            @foreach (\App\Models\Candidate::STAGES as $target)
                                                @continue($target === $stage)
                                                <li>
                                                    <form method="POST" action="{{ route('admin.candidates.stage', $candidate) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="stage" value="{{ $target }}">
                                                        <button type="submit" class="dropdown-item d-flex align-items-center gap-2">
                                                            <span class="kanban-dot tone-{{ $stageMeta[$target]['tone'] ?? 'slate' }}"></span>
                                                            {{ Str::headline($target) }}
                                                        </button>
                                                    </form>
                                                </li>
                                            @endforeach
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item" href="{{ route('admin.candidates.edit', $candidate) }}"><i class="ti ti-pencil me-1"></i> Edit candidate</a></li>
                                        </ul>
                                    </div>
                                @endif
                            </div>

                            <div class="kanban-card-meta">
                                @if ($candidate->source)
                                    <span class="tag tag-outline tone-sky">{{ $candidate->source }}</span>
                                @endif
                                @if ($candidate->interviews_count)
                                    <span class="tag tone-violet" title="Interviews"><i class="ti ti-calendar-event"></i>{{ $candidate->interviews_count }}</span>
                                @endif
                                @if ($candidate->resume_path)
                                    <a href="{{ asset('storage/'.$candidate->resume_path) }}" target="_blank" rel="noopener" class="tag tone-amber text-decoration-none" title="Open resume"><i class="ti ti-file-text"></i>CV</a>
                                @endif
                            </div>

                            <div class="kanban-card-foot">
                                <span title="{{ optional($candidate->applied_date)->format('M j, Y') }}">
                                    <i class="ti ti-clock"></i>
                                    {{ $candidate->applied_date ? 'Applied '.$candidate->applied_date->diffForHumans() : 'No applied date' }}
                                </span>
                                @if ($candidate->email)
                                    <a href="mailto:{{ $candidate->email }}" class="kanban-card-mail" title="Email {{ $candidate->email }}"><i class="ti ti-mail"></i></a>
                                @endif
                            </div>
                        </article>
                    @endforeach

                    <div class="kanban-empty" @if ($cards->isNotEmpty()) hidden @endif>
                        <i class="ti {{ $meta['icon'] }}"></i>
                        <span>{{ $canUpdate ? 'Drop candidates here' : 'No candidates' }}</span>
                    </div>
                </div>
            </section>
        @endforeach
    </div>

    <div class="kanban-toast" id="pipeline-toast" role="status" aria-live="polite" hidden></div>
@endsection

@push('scripts')
    <script>
        // Drag-and-drop stage changes: optimistic move, PATCH, revert on failure.
        (function () {
            var board = document.getElementById('pipeline-board');
            if (!board || !board.dataset.draggable) return;

            var toast = document.getElementById('pipeline-toast');
            var toastTimer;
            var dragged = null;
            var sourceBody = null;

            function showToast(message, isError) {
                toast.textContent = message;
                toast.classList.toggle('is-error', !!isError);
                toast.hidden = false;
                clearTimeout(toastTimer);
                toastTimer = setTimeout(function () { toast.hidden = true; }, 2600);
            }

            function refreshColumn(col) {
                var body = col.querySelector('[data-dropzone]');
                var count = body.querySelectorAll('.kanban-card').length;
                col.querySelector('[data-count]').textContent = count;
                body.querySelector('.kanban-empty').hidden = count > 0;
            }

            board.addEventListener('dragstart', function (event) {
                var card = event.target.closest('.kanban-card');
                if (!card) return;
                dragged = card;
                sourceBody = card.parentElement;
                card.classList.add('is-dragging');
                board.classList.add('is-dragging');
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', card.dataset.id);
            });

            board.addEventListener('dragend', function () {
                if (dragged) dragged.classList.remove('is-dragging');
                board.classList.remove('is-dragging');
                board.querySelectorAll('.is-over').forEach(function (el) { el.classList.remove('is-over'); });
                dragged = null;
            });

            board.addEventListener('dragover', function (event) {
                var body = event.target.closest('[data-dropzone]');
                if (!body || !dragged) return;
                event.preventDefault();
                event.dataTransfer.dropEffect = 'move';
                board.querySelectorAll('.is-over').forEach(function (el) { if (el !== body) el.classList.remove('is-over'); });
                body.classList.add('is-over');
            });

            board.addEventListener('drop', function (event) {
                var body = event.target.closest('[data-dropzone]');
                if (!body || !dragged) return;
                event.preventDefault();
                body.classList.remove('is-over');
                if (body === sourceBody) return;

                var card = dragged;
                var fromBody = sourceBody;
                var toCol = body.closest('.kanban-col');
                var fromCol = fromBody.closest('.kanban-col');
                var stage = toCol.dataset.stage;

                body.insertBefore(card, body.querySelector('.kanban-empty'));
                refreshColumn(toCol);
                refreshColumn(fromCol);
                card.classList.add('is-saving');

                fetch(card.dataset.url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': board.dataset.csrf,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ stage: stage })
                }).then(function (response) {
                    if (!response.ok) throw new Error(response.status);
                    return response.json();
                }).then(function (data) {
                    card.classList.remove('is-saving');
                    card.classList.add('is-moved');
                    setTimeout(function () { card.classList.remove('is-moved'); }, 900);
                    showToast(data.message || 'Stage updated.');
                }).catch(function () {
                    fromBody.insertBefore(card, fromBody.querySelector('.kanban-empty'));
                    card.classList.remove('is-saving');
                    refreshColumn(toCol);
                    refreshColumn(fromCol);
                    showToast('Could not move this candidate - please try again.', true);
                });
            });
        })();
    </script>
@endpush
