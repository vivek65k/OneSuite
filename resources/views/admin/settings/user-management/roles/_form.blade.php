@php
    $rolePermissionIds = array_map('intval', old('permissions', $role->exists ? $role->permissions->pluck('id')->all() : []));
    $totalPermissionsCount = collect($permissionMatrix)->sum('total');
    $firstAppKey = array_key_first($permissionMatrix);
    // Each app paints its own card/pane with its identity hue from the
    // theme (--hue-settings, --hue-hrm, ...); unknown apps use the accent.
    $appHue = fn ($key) => "--app-hue: var(--hue-{$key}, var(--accent)); --app-hue-subtle: var(--hue-{$key}-subtle, var(--accent-subtle));";
    // A brand-new role starts on the narrowest access; widening it is a deliberate choice.
    $dataScope = old('data_scope', $role->exists ? $role->data_scope : \App\Support\DataScope::OWN);
@endphp

<div class="card">
    <div class="card-header">
        <h4 class="card-title">Role</h4>
    </div>
    <div class="card-body">
        <div class="col-md-6">
            <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $role->name) }}" required autofocus>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h4 class="card-title">Data access</h4>
    </div>
    <div class="card-body">
        <p class="text-muted font-13 mb-3">
            Permissions below decide <strong>what</strong> this role can do. This decides <strong>whose records</strong> it can do it to -
            leave, attendance, payslips, documents and everything else that belongs to one employee.
        </p>

        <div class="row g-3">
            @foreach (\App\Support\DataScope::OPTIONS as $value => $option)
                <div class="col-md-4">
                    <label class="limit-option" for="data-scope-{{ $value }}" data-scope-option>
                        <input class="form-check-input" type="radio" name="data_scope" id="data-scope-{{ $value }}" value="{{ $value }}" data-scope-radio @checked($dataScope === $value)>
                        <span class="limit-option-icon"><i class="{{ $option['icon'] }}"></i></span>
                        <span>
                            <span class="d-block fw-semibold font-13 text-body">{{ $option['label'] }}</span>
                            <span class="d-block text-muted font-12">{{ $option['description'] }}</span>
                        </span>
                    </label>
                </div>
            @endforeach
        </div>
        @error('data_scope')<div class="text-danger font-12 mt-2">{{ $message }}</div>@enderror

        <p class="text-muted font-12 mt-3 mb-0">
            <i class="ti ti-info-circle me-1"></i>
            A team is built from the <strong>Reports to</strong> field on each employee, all the way down the chain.
            "Their team" and "Only themselves" need the person's login to be linked to their employee record.
            Setup screens such as leave types, shifts or holidays aren't about one employee, so they look the same for every role that can open them.
        </p>
    </div>
</div>

<div class="card perm-matrix-card" data-perm-matrix>
    <div class="perm-hero">
        <div class="perm-hero-text">
            <span class="perm-hero-icon"><i class="ti ti-shield-check"></i></span>
            <div>
                <h4 class="perm-hero-title">Permissions</h4>
                <p class="perm-hero-sub">Choose what this role can do in each app. Only apps assigned to your company are listed.</p>
            </div>
        </div>
        <div class="perm-hero-total">
            <span class="perm-hero-total-num" data-total-selected>0</span>
            <span class="perm-hero-total-label">of {{ $totalPermissionsCount }} selected</span>
        </div>
    </div>

    @if (empty($permissionMatrix))
        <div class="card-body">
            <p class="text-muted text-center py-4 mb-0">No apps are assigned to your company yet, so there are no permissions to grant.</p>
        </div>
    @else
        <div class="perm-apps" role="tablist">
            @foreach ($permissionMatrix as $appKey => $app)
                <button type="button" class="perm-app-card @if ($appKey === $firstAppKey) is-active @endif" role="tab" style="{{ $appHue($appKey) }}" data-perm-tab="{{ $appKey }}" aria-selected="{{ $appKey === $firstAppKey ? 'true' : 'false' }}">
                    <span class="perm-app-icon"><i class="{{ $app['icon'] }}"></i></span>
                    <span class="perm-app-meta">
                        <span class="perm-app-name">{{ $app['label'] }}</span>
                        <span class="perm-app-stat"><strong data-tab-count="{{ $appKey }}">0</strong> of {{ $app['total'] }} permissions</span>
                    </span>
                    <span class="perm-app-percent" data-tab-percent="{{ $appKey }}">0%</span>
                    <span class="perm-app-progress"><span data-tab-progress="{{ $appKey }}"></span></span>
                </button>
            @endforeach
        </div>

        <div class="perm-toolbar">
            <div class="perm-legend" aria-hidden="true">
                @foreach ($matrixColumns as $column => $definition)
                    @unless ($definition['advanced'])
                        <span class="perm-legend-item perm-col-{{ $column }}"><i class="{{ $definition['icon'] }}"></i>{{ $definition['label'] }}</span>
                    @endunless
                @endforeach
            </div>

            <div class="perm-toolbar-actions">
                <div class="input-group input-group-sm perm-search">
                    <span class="input-group-text bg-transparent"><i class="ti ti-search text-muted"></i></span>
                    <input type="text" class="form-control" id="permission-search" placeholder="Search modules...">
                </div>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" id="show-advanced-permissions" data-toggle-advanced>
                    <label class="form-check-label font-13" for="show-advanced-permissions">Advanced actions</label>
                </div>
            </div>
        </div>

        @foreach ($permissionMatrix as $appKey => $app)
            <div class="perm-pane @if ($appKey !== $firstAppKey) d-none @endif" style="{{ $appHue($appKey) }}" data-perm-pane="{{ $appKey }}" role="tabpanel">
                <div class="table-responsive perm-table-wrap">
                    <table class="table perm-table mb-0">
                        <thead>
                            <tr>
                                <th class="perm-module-col">Module</th>
                                @foreach ($matrixColumns as $column => $definition)
                                    <th class="perm-cell-col perm-col-{{ $column }} @if ($definition['advanced']) perm-advanced @endif">
                                        <label class="perm-col-head">
                                            <span class="perm-col-pill"><i class="{{ $definition['icon'] }}"></i>{{ $definition['label'] }}</span>
                                            <input class="form-check-input" type="checkbox" data-select-column="{{ $column }}" aria-label="Allow {{ $definition['label'] }} for every {{ $app['label'] }} module">
                                        </label>
                                    </th>
                                @endforeach
                                <th class="perm-cell-col perm-all-col">
                                    <span class="perm-col-pill"><i class="ti ti-check"></i>All</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($app['categories'] as $category => $rows)
                                @php $style = \App\Support\PermissionGroups::categoryStyle($category); @endphp
                                <tr class="perm-category-row perm-tone-{{ $style['tone'] }}" data-perm-category>
                                    <td colspan="{{ count($matrixColumns) + 2 }}">
                                        <label class="perm-category-head">
                                            <input class="form-check-input" type="checkbox" data-select-category aria-label="Select every {{ $category }} permission">
                                            <span class="perm-cat-icon"><i class="{{ $style['icon'] }}"></i></span>
                                            <span class="perm-cat-label">{{ $category }}</span>
                                            <span class="perm-cat-count">{{ count($rows) }} {{ Str::plural('module', count($rows)) }}</span>
                                        </label>
                                    </td>
                                </tr>
                                @foreach ($rows as $row)
                                    <tr class="perm-row perm-tone-{{ $style['tone'] }}" data-perm-row data-search="{{ strtolower($row['label'].' '.$category) }}">
                                        <td class="perm-module-col"><span class="perm-module-dot"></span>{{ $row['label'] }}</td>
                                        @foreach ($matrixColumns as $column => $definition)
                                            <td class="perm-cell-col perm-col-{{ $column }} @if ($definition['advanced']) perm-advanced @endif">
                                                @if (isset($row['cells'][$column]))
                                                    <span class="perm-cell" data-perm-cell data-column="{{ $column }}">
                                                        <input class="form-check-input" type="checkbox" data-cell-toggle aria-label="{{ $definition['label'] }} {{ $row['label'] }}">
                                                        @foreach ($row['cells'][$column] as $permissionId)
                                                            <input type="checkbox" class="d-none" name="permissions[]" value="{{ $permissionId }}" data-permission-checkbox @checked(in_array($permissionId, $rolePermissionIds, true))>
                                                        @endforeach
                                                    </span>
                                                @else
                                                    <span class="perm-cell-empty" aria-hidden="true">&ndash;</span>
                                                @endif
                                            </td>
                                        @endforeach
                                        <td class="perm-cell-col perm-all-col">
                                            <div class="form-check form-switch mb-0 d-inline-block">
                                                <input class="form-check-input" type="checkbox" data-select-row aria-label="Allow everything for {{ $row['label'] }}">
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach

        <p class="text-muted text-center py-4 mb-0 d-none" data-no-results>
            <i class="ti ti-search font-20 d-block mb-1"></i>
            No modules match your search.
        </p>
    @endif
</div>

<div class="mb-4">
    <button type="submit" class="btn btn-de-primary text-light px-4">Save</button>
    <a href="{{ route('admin.roles.index') }}" class="btn btn-de-danger text-light px-4">Cancel</a>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var scopeRadios = document.querySelectorAll('[data-scope-radio]');

            function syncScope() {
                scopeRadios.forEach(function (radio) {
                    radio.closest('[data-scope-option]').classList.toggle('is-checked', radio.checked);
                });
            }

            scopeRadios.forEach(function (radio) { radio.addEventListener('change', syncScope); });
            syncScope();

            var matrix = document.querySelector('[data-perm-matrix]');
            if (!matrix) return;

            var tabs = Array.from(matrix.querySelectorAll('[data-perm-tab]'));
            var panes = Array.from(matrix.querySelectorAll('[data-perm-pane]'));
            var cells = Array.from(matrix.querySelectorAll('[data-perm-cell]'));
            var advancedToggle = matrix.querySelector('[data-toggle-advanced]');
            var searchInput = document.getElementById('permission-search');
            var totalSelected = matrix.querySelector('[data-total-selected]');
            var noResults = matrix.querySelector('[data-no-results]');
            var activePane = panes[0];

            function hiddenBoxes(cell) {
                return Array.from(cell.querySelectorAll('[data-permission-checkbox]'));
            }

            // Cells the bulk toggles act on: advanced columns only count
            // while they're shown, and search-hidden rows never count -
            // "select all" should only change what the admin can see.
            function isActionable(cell) {
                if (!matrix.classList.contains('show-advanced') && cell.closest('.perm-advanced')) return false;
                return !cell.closest('[data-perm-row]').classList.contains('d-none');
            }

            function setCell(cell, on) {
                hiddenBoxes(cell).forEach(function (box) { box.checked = on; });
            }

            function setToggle(toggle, scopeCells) {
                if (!toggle) return;
                var boxes = scopeCells.reduce(function (all, cell) { return all.concat(hiddenBoxes(cell)); }, []);
                var checked = boxes.filter(function (box) { return box.checked; }).length;
                toggle.checked = boxes.length > 0 && checked === boxes.length;
                toggle.indeterminate = checked > 0 && checked < boxes.length;
                toggle.disabled = boxes.length === 0;
            }

            function rowsOfCategory(categoryRow) {
                var rows = [];
                var next = categoryRow.nextElementSibling;
                while (next && next.hasAttribute('data-perm-row')) {
                    rows.push(next);
                    next = next.nextElementSibling;
                }
                return rows;
            }

            function cellsIn(rows) {
                return rows.reduce(function (all, row) {
                    return all.concat(Array.from(row.querySelectorAll('[data-perm-cell]')));
                }, []).filter(isActionable);
            }

            function sync() {
                cells.forEach(function (cell) { setToggle(cell.querySelector('[data-cell-toggle]'), [cell]); });

                panes.forEach(function (pane) {
                    pane.querySelectorAll('[data-perm-row]').forEach(function (row) {
                        var rowToggle = row.querySelector('[data-select-row]');
                        setToggle(rowToggle, cellsIn([row]));
                        row.classList.toggle('is-granted', row.querySelectorAll('[data-permission-checkbox]:checked').length > 0);
                    });
                    pane.querySelectorAll('[data-perm-category]').forEach(function (categoryRow) {
                        setToggle(categoryRow.querySelector('[data-select-category]'), cellsIn(rowsOfCategory(categoryRow)));
                    });
                    pane.querySelectorAll('[data-select-column]').forEach(function (toggle) {
                        var column = toggle.dataset.selectColumn;
                        setToggle(toggle, Array.from(pane.querySelectorAll('[data-perm-cell][data-column="' + column + '"]')).filter(isActionable));
                    });

                    var key = pane.dataset.permPane;
                    var boxes = pane.querySelectorAll('[data-permission-checkbox]').length;
                    var checked = pane.querySelectorAll('[data-permission-checkbox]:checked').length;
                    var percent = boxes ? Math.round(checked / boxes * 100) : 0;
                    var count = matrix.querySelector('[data-tab-count="' + key + '"]');
                    var percentLabel = matrix.querySelector('[data-tab-percent="' + key + '"]');
                    var progress = matrix.querySelector('[data-tab-progress="' + key + '"]');
                    if (count) count.textContent = checked;
                    if (percentLabel) percentLabel.textContent = percent + '%';
                    if (progress) progress.style.width = percent + '%';
                });

                if (totalSelected) {
                    totalSelected.textContent = matrix.querySelectorAll('[data-permission-checkbox]:checked').length;
                }
            }

            cells.forEach(function (cell) {
                cell.querySelector('[data-cell-toggle]').addEventListener('change', function (e) {
                    setCell(cell, e.target.checked);
                    sync();
                });
            });

            matrix.querySelectorAll('[data-select-row]').forEach(function (toggle) {
                toggle.addEventListener('change', function () {
                    cellsIn([toggle.closest('[data-perm-row]')]).forEach(function (cell) { setCell(cell, toggle.checked); });
                    sync();
                });
            });

            matrix.querySelectorAll('[data-select-category]').forEach(function (toggle) {
                toggle.addEventListener('change', function () {
                    cellsIn(rowsOfCategory(toggle.closest('[data-perm-category]'))).forEach(function (cell) { setCell(cell, toggle.checked); });
                    sync();
                });
            });

            matrix.querySelectorAll('[data-select-column]').forEach(function (toggle) {
                toggle.addEventListener('change', function () {
                    var pane = toggle.closest('[data-perm-pane]');
                    pane.querySelectorAll('[data-perm-cell][data-column="' + toggle.dataset.selectColumn + '"]').forEach(function (cell) {
                        if (isActionable(cell)) setCell(cell, toggle.checked);
                    });
                    sync();
                });
            });

            tabs.forEach(function (tab) {
                tab.addEventListener('click', function () {
                    tabs.forEach(function (t) {
                        var active = t === tab;
                        t.classList.toggle('is-active', active);
                        t.setAttribute('aria-selected', active ? 'true' : 'false');
                    });
                    panes.forEach(function (pane) {
                        var active = pane.dataset.permPane === tab.dataset.permTab;
                        pane.classList.toggle('d-none', !active);
                        if (active) activePane = pane;
                    });
                    applySearch();
                });
            });

            if (advancedToggle) {
                advancedToggle.addEventListener('change', function () {
                    matrix.classList.toggle('show-advanced', advancedToggle.checked);
                    sync();
                });

                // Editing a role that already has an advanced permission:
                // show those columns so nothing it grants is invisible.
                var hasAdvanced = Array.from(matrix.querySelectorAll('.perm-advanced [data-permission-checkbox]')).some(function (box) { return box.checked; });
                if (hasAdvanced) {
                    advancedToggle.checked = true;
                    matrix.classList.add('show-advanced');
                }
            }

            function applySearch() {
                var term = searchInput ? searchInput.value.trim().toLowerCase() : '';

                panes.forEach(function (pane) {
                    pane.querySelectorAll('[data-perm-row]').forEach(function (row) {
                        row.classList.toggle('d-none', term !== '' && row.dataset.search.indexOf(term) === -1);
                    });
                    pane.querySelectorAll('[data-perm-category]').forEach(function (categoryRow) {
                        var anyVisible = rowsOfCategory(categoryRow).some(function (row) { return !row.classList.contains('d-none'); });
                        categoryRow.classList.toggle('d-none', !anyVisible);
                    });
                });

                if (noResults && activePane) {
                    noResults.classList.toggle('d-none', activePane.querySelectorAll('[data-perm-row]:not(.d-none)').length > 0);
                }

                sync();
            }

            if (searchInput) searchInput.addEventListener('input', applySearch);

            sync();
        });
    </script>
@endpush
