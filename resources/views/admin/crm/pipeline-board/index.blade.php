@extends('layouts.admin')

@section('title', 'Pipeline Board')

@section('content')
    <x-page-title title="Pipeline Board" />

    <div class="card list-view mb-3">
        <form method="GET" action="{{ route('admin.crm.pipeline-board.index') }}">
            <div class="list-toolbar">
                <div class="list-toolbar-title"><i class="ti ti-adjustments-horizontal"></i> Filters</div>
                <div class="list-toolbar-actions">
                    <select name="owner_employee_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Owner">
                        <option value="">All owners</option>
                        @foreach ($owners as $id => $name)
                            <option value="{{ $id }}" @selected((string) $ownerId === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                    <select name="account_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Account">
                        <option value="">All accounts</option>
                        @foreach ($accounts as $id => $name)
                            <option value="{{ $id }}" @selected((string) $accountId === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                    <a href="{{ route('admin.crm.sales-pipeline.index') }}" class="btn btn-light btn-sm"><i class="ti ti-chart-line font-14 me-1"></i> Summary View</a>
                </div>
            </div>
        </form>
    </div>

    @if ($stages->isEmpty())
        <div class="card">
            <div class="empty-state">
                <span class="empty-state-icon tone-indigo"><i class="ti ti-layout-kanban"></i></span>
                <h5>No sales stages configured</h5>
                <p>Set up your pipeline stages first, then opportunities will show up here as cards.</p>
                <a href="{{ route('admin.crm.sales-stages.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> Add Stage</a>
            </div>
        </div>
    @else
        <div class="kanban-board" id="pipeline-board">
            @foreach ($stages as $stage)
                @php $cards = $opportunities->get($stage->id, collect()); @endphp
                <div class="kanban-column">
                    <div class="kanban-column-head">
                        <span class="fw-semibold">{{ $stage->name }}</span>
                        <span class="list-tab-count">{{ $cards->count() }}</span>
                    </div>
                    <div class="font-12 text-muted px-2 pt-1">Weighted: {{ number_format($weightedByStage[$stage->id] ?? 0) }}</div>
                    <div class="kanban-column-body" data-stage-id="{{ $stage->id }}">
                        @foreach ($cards as $opportunity)
                            <div class="kanban-card" draggable="true" data-opportunity-id="{{ $opportunity->id }}">
                                <a href="{{ route('admin.crm.opportunities.edit', $opportunity) }}" class="fw-semibold d-block mb-1">{{ $opportunity->name }}</a>
                                <div class="text-muted font-13 mb-1">{{ $opportunity->account?->name ?? '—' }}</div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="salary-figure">{{ number_format((float) $opportunity->amount) }}</span>
                                    @if ($opportunity->owner)
                                        <span class="list-avatar list-avatar-indigo" style="width: 26px; height: 26px; font-size: 0.6875rem;" title="{{ $opportunity->owner->name }}">{{ \Illuminate\Support\Str::of($opportunity->owner->name)->explode(' ')->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('') }}</span>
                                    @endif
                                </div>
                                @if ($opportunity->expected_close_date)
                                    <div class="text-muted font-12 mt-1"><i class="ti ti-calendar font-12"></i> {{ $opportunity->expected_close_date->format('M j') }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection

@push('styles')
    <style>
        .kanban-board { display: flex; gap: 0.75rem; overflow-x: auto; padding-bottom: 0.5rem; }
        .kanban-column { flex: 0 0 280px; background: var(--surface-2); border-radius: 10px; display: flex; flex-direction: column; max-height: calc(100vh - 220px); }
        .kanban-column-head { display: flex; justify-content: space-between; align-items: center; padding: 0.625rem 0.75rem; border-bottom: 1px solid var(--border-subtle); }
        .kanban-column-body { flex: 1; overflow-y: auto; padding: 0.5rem; min-height: 80px; }
        .kanban-column-body.is-drop-target { background-color: var(--accent-subtle); }
        .kanban-card { background: var(--surface); border: 1px solid var(--border-subtle); border-radius: 8px; padding: 0.625rem 0.75rem; margin-bottom: 0.5rem; cursor: grab; }
        .kanban-card.is-dragging { opacity: 0.4; }
    </style>
@endpush

@push('scripts')
    <script>
        (function () {
            var board = document.getElementById('pipeline-board');
            if (!board) return;
            var token = document.querySelector('meta[name="csrf-token"]').content;
            var dragged = null;

            board.querySelectorAll('.kanban-card').forEach(function (card) {
                card.addEventListener('dragstart', function () {
                    dragged = card;
                    card.classList.add('is-dragging');
                });
                card.addEventListener('dragend', function () {
                    card.classList.remove('is-dragging');
                });
            });

            board.querySelectorAll('.kanban-column-body').forEach(function (column) {
                column.addEventListener('dragover', function (e) {
                    e.preventDefault();
                    column.classList.add('is-drop-target');
                });
                column.addEventListener('dragleave', function () {
                    column.classList.remove('is-drop-target');
                });
                column.addEventListener('drop', function (e) {
                    e.preventDefault();
                    column.classList.remove('is-drop-target');
                    if (!dragged) return;

                    var opportunityId = dragged.dataset.opportunityId;
                    var stageId = column.dataset.stageId;
                    column.appendChild(dragged);

                    fetch('{{ url("crm/pipeline-board") }}/' + opportunityId + '/move', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                        body: JSON.stringify({ sales_stage_id: stageId })
                    }).then(function (res) {
                        if (!res.ok) window.location.reload();
                    }).catch(function () { window.location.reload(); });
                });
            });
        })();
    </script>
@endpush
