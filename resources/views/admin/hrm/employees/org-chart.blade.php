@extends('layouts.admin')

@section('title', 'Org Chart')

@php
    // Everyone drawn on the chart, by id - a card uses it to tell whether its manager is on the picture too.
    $onChart = collect();
    $collect = function ($nodes) use (&$collect, &$onChart) {
        foreach ($nodes as $n) {
            $onChart->put($n['employee']->id, true);
            $collect($n['children']);
        }
    };
    $collect($tree);
    // The permission itself - each card still checks its own person (branch + data access).
    $canMoveAny = auth()->user()->can('update_employee');
@endphp

@section('content')
    <x-page-title title="Org Chart" :breadcrumbs="['Employees' => route('admin.employees.index')]" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @foreach (['manager_id', 'to_manager_id', 'from_manager_id'] as $field)
        @error($field)
            <div class="alert alert-danger"><i class="ti ti-alert-triangle me-1"></i>{{ $message }}</div>
        @enderror
    @endforeach

    <div class="kpi-strip">
        <x-kpi-card label="People" :value="$stats['people']" icon="ti-users" tone="indigo" />
        <x-kpi-card label="Managers" :value="$stats['managers']" icon="ti-hierarchy" tone="success" hint="have someone reporting to them" />
        <x-kpi-card label="No manager set" :value="$stats['unassigned']" icon="ti-user-off" :tone="$stats['unassigned'] > 1 ? 'warning' : 'indigo'" hint="shown at the top level" />
        <x-kpi-card label="Levels" :value="$stats['levels']" icon="ti-sitemap" tone="pink" hint="top to bottom" />
    </div>

    @if ($orphaned->isNotEmpty())
        <div class="alert alert-warning d-flex align-items-center flex-wrap gap-2">
            <span><i class="ti ti-user-off me-1"></i>
                <strong>{{ $orphaned->count() }} {{ Str::plural('person', $orphaned->count()) }}</strong>
                still {{ $orphaned->count() === 1 ? 'reports' : 'report' }} to someone who is no longer active:
                {{ $orphaned->pluck('name')->take(4)->implode(', ') }}{{ $orphaned->count() > 4 ? ' and '.($orphaned->count() - 4).' more' : '' }}.
            </span>
            @if ($canMoveAny)
                <button type="button" class="btn btn-sm btn-light ms-auto" data-org-reassign data-from="{{ $orphaned->first()->manager_id }}">Hand their team to someone else</button>
            @endif
        </div>
    @endif

    <div class="card org-card">
        <div class="org-toolbar">
            <div class="input-group input-group-sm org-search">
                <span class="input-group-text bg-transparent"><i class="ti ti-search text-muted"></i></span>
                <input type="text" class="form-control" id="org-search" placeholder="Find a person, role or department..." autocomplete="off">
            </div>
            <span class="org-search-count text-muted font-12" data-org-search-count></span>

            <div class="org-toolbar-actions">
                <div class="btn-group btn-group-sm" role="group" aria-label="Expand or collapse">
                    <button type="button" class="btn btn-light" data-org-expand><i class="ti ti-chevrons-down font-14 me-1"></i>Expand all</button>
                    <button type="button" class="btn btn-light" data-org-collapse><i class="ti ti-chevrons-up font-14 me-1"></i>Collapse all</button>
                </div>
                <div class="btn-group btn-group-sm" role="group" aria-label="Zoom">
                    <button type="button" class="btn btn-light" data-org-zoom="-1" title="Zoom out"><i class="ti ti-zoom-out font-14"></i></button>
                    <button type="button" class="btn btn-light" data-org-zoom="1" title="Zoom in"><i class="ti ti-zoom-in font-14"></i></button>
                </div>
                @if ($canMoveAny && $teamLeads->isNotEmpty())
                    <button type="button" class="btn btn-de-primary btn-sm text-light" data-org-reassign><i class="ti ti-switch-horizontal font-14 me-1"></i>Reassign a team</button>
                @endif
            </div>
        </div>

        @if ($tree->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="ti ti-hierarchy font-24 d-block mb-2"></i>
                No active employees to show yet.
            </div>
        @else
            @if ($canMoveAny)
                <p class="org-hint">
                    <i class="ti ti-grip-vertical"></i>
                    Drag a person onto their manager to change who they report to, or use the
                    <i class="ti ti-switch-horizontal"></i> button on their card. Everyone under them moves with them.
                </p>
                <div class="org-top-drop" data-org-top-drop><i class="ti ti-arrow-up me-1"></i>Drop here to remove their manager (top level)</div>
            @endif

            <div class="org-canvas" data-org-canvas>
                <div class="org-tree" data-org-tree>
                    <ul class="org-roots">
                        @foreach ($tree as $node)
                            @include('admin.hrm.employees._org-node', ['node' => $node])
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif
    </div>

    @if ($canMoveAny)
        {{-- Change one person's manager (button on a card, or a drag-and-drop) --}}
        <div class="modal fade" id="org-change-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <form method="POST" class="modal-content" data-org-change-form data-action-template="{{ route('admin.employees.manager.update', '__ID__') }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title">Who does <span data-org-change-name></span> report to?</h5>
                            <p class="text-muted font-12 mb-0" data-org-change-sub></p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text bg-transparent"><i class="ti ti-search text-muted"></i></span>
                            <input type="text" class="form-control" placeholder="Search by name or role..." data-org-pick-search autocomplete="off">
                        </div>
                        <div class="org-pick-list">
                            <label class="org-pick" data-org-pick data-search="no manager top level">
                                <input type="radio" name="manager_id" value="">
                                <span class="org-pick-icon"><i class="ti ti-arrow-up"></i></span>
                                <span class="org-pick-text"><strong>No manager</strong><small>Top of the chart</small></span>
                            </label>
                            @foreach ($managerOptions as $option)
                                <label class="org-pick" data-org-pick data-id="{{ $option->id }}" data-search="{{ Str::lower($option->name.' '.$option->designation.' '.$option->employee_code) }}">
                                    <input type="radio" name="manager_id" value="{{ $option->id }}">
                                    <x-avatar :person="$option" />
                                    <span class="org-pick-text">
                                        <strong>{{ $option->name }}</strong>
                                        <small>{{ collect([$option->designation, $option->branch?->name])->filter()->implode(' - ') ?: 'No designation' }}</small>
                                    </span>
                                </label>
                            @endforeach
                            <p class="text-muted text-center font-13 py-3 mb-0 d-none" data-org-pick-empty>Nobody matches that search.</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-de-primary text-light">Save</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Move a whole team from one manager to another --}}
        <div class="modal fade" id="org-reassign-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('admin.employees.org-chart.reassign') }}" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title">Reassign a team</h5>
                            <p class="text-muted font-12 mb-0">For when a manager leaves, moves or is promoted: everyone reporting directly to them moves to the new manager.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label" for="from_manager_id">Move everyone who reports to</label>
                            <select class="form-select" id="from_manager_id" name="from_manager_id" required>
                                <option value="">Choose a manager</option>
                                @foreach ($teamLeads as $lead)
                                    <option value="{{ $lead['id'] }}">{{ $lead['name'] }} - {{ $lead['count'] }} {{ Str::plural('person', $lead['count']) }}{{ $lead['left'] ? ' (no longer active)' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label" for="to_manager_id">So that they report to</label>
                            <select class="form-select" id="to_manager_id" name="to_manager_id">
                                <option value="">No manager (top level)</option>
                                @foreach ($managerOptions as $option)
                                    <option value="{{ $option->id }}">{{ $option->name }}{{ $option->designation ? ' - '.$option->designation : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-de-primary text-light">Move the team</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var tree = document.querySelector('[data-org-tree]');
            if (!tree) return;

            var canvas = document.querySelector('[data-org-canvas]');
            var nodes = Array.from(tree.querySelectorAll('[data-org-node]'));
            var itemOf = function (node) { return node.closest('[data-org-item]'); };

            // ---- expand / collapse ----
            tree.querySelectorAll('[data-org-toggle]').forEach(function (button) {
                button.addEventListener('click', function () {
                    itemOf(button.closest('[data-org-node]')).classList.toggle('is-collapsed');
                });
            });

            function setAll(collapsed) {
                tree.querySelectorAll('[data-org-item]').forEach(function (item) {
                    if (item.querySelector(':scope > ul')) item.classList.toggle('is-collapsed', collapsed);
                });
            }

            var expandButton = document.querySelector('[data-org-expand]');
            var collapseButton = document.querySelector('[data-org-collapse]');
            if (expandButton) expandButton.addEventListener('click', function () { setAll(false); });
            if (collapseButton) collapseButton.addEventListener('click', function () { setAll(true); });

            // ---- zoom ----
            var zoom = 1;
            document.querySelectorAll('[data-org-zoom]').forEach(function (button) {
                button.addEventListener('click', function () {
                    zoom = Math.min(1.3, Math.max(0.5, zoom + Number(button.dataset.orgZoom) * 0.1));
                    tree.style.zoom = zoom;
                });
            });

            // ---- search: highlight matches, open the branches they sit in ----
            var search = document.getElementById('org-search');
            var searchCount = document.querySelector('[data-org-search-count]');

            function reveal(node) {
                var item = itemOf(node).parentElement.closest('[data-org-item]');
                while (item) {
                    item.classList.remove('is-collapsed');
                    item = item.parentElement.closest('[data-org-item]');
                }
            }

            search.addEventListener('input', function () {
                var term = search.value.trim().toLowerCase();
                var matches = [];

                nodes.forEach(function (node) {
                    var hit = term !== '' && node.dataset.search.indexOf(term) !== -1;
                    node.classList.toggle('is-match', hit);
                    if (hit) { matches.push(node); reveal(node); }
                });

                tree.classList.toggle('is-searching', term !== '');
                searchCount.textContent = term === '' ? '' : (matches.length ? matches.length + ' found' : 'No one found');
                if (matches.length) matches[0].scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });
            });

            // ---- the person just moved: bring them into view ----
            var moved = tree.querySelector('.org-node.is-moved');
            if (moved) {
                reveal(moved);
                moved.scrollIntoView({ block: 'center', inline: 'center' });
            }

            // ---- changing a manager ----
            var modalElement = document.getElementById('org-change-modal');
            if (!modalElement) return;

            var modal = bootstrap.Modal.getOrCreateInstance(modalElement);
            var form = modalElement.querySelector('[data-org-change-form]');
            var picks = Array.from(modalElement.querySelectorAll('[data-org-pick]'));
            var pickSearch = modalElement.querySelector('[data-org-pick-search]');
            var pickEmpty = modalElement.querySelector('[data-org-pick-empty]');

            // Nobody can report to themselves or to someone below them.
            function blockedIdsFor(node) {
                var ids = [node.dataset.id];
                itemOf(node).querySelectorAll('[data-org-node]').forEach(function (n) { ids.push(n.dataset.id); });
                return ids;
            }

            function filterPicks() {
                var term = pickSearch.value.trim().toLowerCase();
                var visible = 0;
                picks.forEach(function (pick) {
                    var show = !pick.classList.contains('is-blocked') && (term === '' || pick.dataset.search.indexOf(term) !== -1);
                    pick.classList.toggle('d-none', !show);
                    if (show) visible++;
                });
                pickEmpty.classList.toggle('d-none', visible > 0);
            }

            function openChange(node, managerId) {
                var blocked = blockedIdsFor(node);
                var total = Number(node.dataset.total);
                var target = managerId === undefined ? (node.dataset.managerId || '') : String(managerId);

                form.action = form.dataset.actionTemplate.replace('__ID__', node.dataset.id);
                modalElement.querySelector('[data-org-change-name]').textContent = node.dataset.name;
                modalElement.querySelector('[data-org-change-sub]').textContent = total
                    ? total + (total === 1 ? ' person' : ' people') + ' under them will move with them.'
                    : '';

                picks.forEach(function (pick) {
                    var id = pick.dataset.id || '';
                    pick.classList.toggle('is-blocked', id !== '' && blocked.indexOf(id) !== -1);
                    pick.querySelector('input').checked = id === target;
                    pick.classList.toggle('is-chosen', id === target);
                });

                pickSearch.value = '';
                filterPicks();
                modal.show();

                var chosen = modalElement.querySelector('.org-pick.is-chosen');
                if (chosen) setTimeout(function () { chosen.scrollIntoView({ block: 'center' }); }, 250);
            }

            pickSearch.addEventListener('input', filterPicks);
            picks.forEach(function (pick) {
                pick.querySelector('input').addEventListener('change', function () {
                    picks.forEach(function (p) { p.classList.toggle('is-chosen', p.querySelector('input').checked); });
                });
            });

            tree.querySelectorAll('[data-org-change]').forEach(function (button) {
                button.addEventListener('click', function () { openChange(button.closest('[data-org-node]')); });
            });

            // ---- drag a person onto their new manager ----
            var dragging = null;
            var topDrop = document.querySelector('[data-org-top-drop]');

            function canDropOn(target) {
                return dragging && target !== dragging && blockedIdsFor(dragging).indexOf(target.dataset.id) === -1
                    && target.dataset.id !== dragging.dataset.managerId;
            }

            nodes.forEach(function (node) {
                if (node.hasAttribute('data-can-move')) {
                    node.addEventListener('dragstart', function (e) {
                        dragging = node;
                        e.dataTransfer.effectAllowed = 'move';
                        e.dataTransfer.setData('text/plain', node.dataset.id);
                        node.classList.add('is-dragging');
                        tree.classList.add('is-dragging');
                        if (topDrop && node.dataset.managerId) topDrop.classList.add('is-visible');
                    });
                    node.addEventListener('dragend', function () {
                        node.classList.remove('is-dragging');
                        tree.classList.remove('is-dragging');
                        nodes.forEach(function (n) { n.classList.remove('is-drop-target'); });
                        if (topDrop) topDrop.classList.remove('is-visible', 'is-drop-target');
                        dragging = null;
                    });
                }

                node.addEventListener('dragover', function (e) {
                    if (!canDropOn(node)) return;
                    e.preventDefault();
                    node.classList.add('is-drop-target');
                });
                node.addEventListener('dragleave', function () { node.classList.remove('is-drop-target'); });
                node.addEventListener('drop', function (e) {
                    if (!canDropOn(node)) return;
                    e.preventDefault();
                    var person = dragging;
                    node.classList.remove('is-drop-target');
                    openChange(person, node.dataset.id);
                });
            });

            if (topDrop) {
                topDrop.addEventListener('dragover', function (e) { if (dragging) { e.preventDefault(); topDrop.classList.add('is-drop-target'); } });
                topDrop.addEventListener('dragleave', function () { topDrop.classList.remove('is-drop-target'); });
                topDrop.addEventListener('drop', function (e) {
                    if (!dragging) return;
                    e.preventDefault();
                    openChange(dragging, '');
                });
            }

            // ---- reassign a whole team ----
            var reassignElement = document.getElementById('org-reassign-modal');
            document.querySelectorAll('[data-org-reassign]').forEach(function (button) {
                button.addEventListener('click', function () {
                    if (button.dataset.from) reassignElement.querySelector('#from_manager_id').value = button.dataset.from;
                    bootstrap.Modal.getOrCreateInstance(reassignElement).show();
                });
            });
        });
    </script>
@endpush
