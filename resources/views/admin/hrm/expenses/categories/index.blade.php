@extends('layouts.admin')

@section('title', 'Expense Categories')

@php
    use Illuminate\Support\Str;

    $money = fn ($n) => number_format((float) $n, (float) $n == floor((float) $n) ? 0 : 2);
    $pageUrl = fn (array $overrides = []) => route('admin.expense-categories.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $tabs = [
        '' => ['label' => 'All', 'tone' => 'indigo', 'count' => $counts['all']],
        'active' => ['label' => 'Active', 'tone' => 'success', 'count' => $counts['active']],
        'inactive' => ['label' => 'Switched off', 'tone' => 'slate', 'count' => $counts['inactive']],
    ];
    // The same colour for the same category on every expense page.
    $tones = ['indigo', 'teal', 'amber', 'rose', 'success', 'slate'];
    $categoryTone = fn (?string $name) => $tones[abs(crc32((string) $name)) % count($tones)];
    // Common ones the company doesn't have yet, offered as one-click starting points.
    $suggestions = collect(['Travel', 'Meals', 'Fuel', 'Internet & Phone', 'Office Supplies', 'Client Entertainment', 'Software & Tools', 'Training', 'Courier'])
        ->reject(fn ($name) => $existing->contains(mb_strtolower($name)))->values();
    $showBranch = $branches->isNotEmpty();
    // A failed save in the edit dialog must not spill its values into the add form.
    $fromEdit = old('_method') === 'PUT';
@endphp

@section('content')
    <x-page-title title="Expense Categories" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Active categories" :value="$kpis['active']" icon="ti-tag" tone="indigo" :hint="$kpis['active'] ? 'What people can claim under' : 'Nobody can submit a claim until one is added'" />
        <x-kpi-card label="Most spent on" :value="$kpis['top'] ? Str::limit($kpis['top']->name, 18) : '—'" icon="ti-chart-bar" tone="teal" :hint="$kpis['top'] ? $money($kpis['top']->accepted_total).' accepted'.($spendTotal > 0 ? ' · '.round((float) $kpis['top']->accepted_total / $spendTotal * 100).'% of all spend' : '') : 'No accepted claims yet'" />
        <x-kpi-card label="Never used" :value="$kpis['unused']" icon="ti-circle-dashed" :tone="$kpis['unused'] ? 'amber' : 'success'" :hint="$kpis['unused'] ? 'Active, but no claim has been made under '.($kpis['unused'] === 1 ? 'it' : 'them') : 'Every active category has been used'" />
        <x-kpi-card label="Switched off" :value="$kpis['inactive']" icon="ti-toggle-left" tone="slate" :hint="$kpis['inactive'] ? 'Kept for their past claims' : 'None'" />
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="card list-view mb-0">
                <form method="GET" action="{{ route('admin.expense-categories.index') }}">
                    @if ($state['tab'] !== '')
                        <input type="hidden" name="tab" value="{{ $state['tab'] }}">
                    @endif
                    <div class="list-toolbar">
                        <nav class="list-tabs" aria-label="Category status">
                            @foreach ($tabs as $value => $tab)
                                @if ($value !== 'inactive' || $tab['count'] || $state['tab'] === 'inactive')
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
                                <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] }}" placeholder="Search categories" aria-label="Search categories">
                            </div>
                        </div>
                    </div>
                </form>
                <div class="table-responsive table-responsive-sticky">
                    <table class="table table-hover list-table mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Category</th>
                                <th>Claims</th>
                                <th style="min-width: 170px">Accepted spend</th>
                                <th>Status</th>
                                <th class="text-end"><span class="visually-hidden">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($categories as $category)
                                @php
                                    $spend = (float) $category->accepted_total;
                                    $share = $spendTotal > 0 ? (int) round($spend / $spendTotal * 100) : 0;
                                    $lastUsed = $category->last_used ? \Illuminate\Support\Carbon::parse($category->last_used) : null;
                                @endphp
                                <tr class="{{ $category->is_active ? '' : 'text-muted' }}">
                                    <td>
                                        <span class="tag tone-{{ $category->is_active ? $categoryTone($category->name) : 'slate' }}">{{ $category->name }}</span>
                                        <div class="payslip-parts text-wrap">{{ collect([$showBranch ? ($category->branch_id ? ($branches[$category->branch_id] ?? 'Branch') : 'All branches') : null, $category->description ? Str::limit($category->description, 70) : null])->filter()->implode(' · ') }}</div>
                                    </td>
                                    <td class="text-nowrap">
                                        @if ($category->claims_count)
                                            <a href="{{ route('admin.expense-claims.index', ['category' => $category->name]) }}" class="text-body fw-semibold" title="See the claims">{{ $category->claims_count }}</a>
                                            <div class="payslip-parts">{{ collect([$category->pending_count ? $category->pending_count.' pending' : null, $lastUsed ? 'last '.$lastUsed->format('M j, Y') : null])->filter()->implode(' · ') }}</div>
                                        @else
                                            <span class="text-muted">Never used</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($spend > 0)
                                            <div class="mini-progress tone-{{ $categoryTone($category->name) }}">
                                                <div class="mini-progress-bar is-toned"><span style="width: {{ $share }}%"></span></div>
                                                <span class="mini-progress-label">{{ $money($spend) }}</span>
                                            </div>
                                            <div class="payslip-parts">{{ $share }}% of all spend</div>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($category->is_active)
                                            <span class="status-dot status-dot-success">Active</span>
                                        @else
                                            <span class="status-dot status-dot-warning">Switched off</span>
                                        @endif
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <div class="list-row-actions">
                                            @can('update', $category)
                                                <form method="POST" action="{{ route('admin.expense-categories.toggle', $category) }}" class="d-inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn-icon" title="{{ $category->is_active ? 'Switch off: no new claims under it' : 'Switch back on' }}"><i class="ti {{ $category->is_active ? 'ti-toggle-right' : 'ti-toggle-left' }} font-18"></i></button>
                                                </form>
                                                <button type="button" class="btn-icon btn-icon-accent" title="Change category" data-bs-toggle="modal" data-bs-target="#category-modal" data-edit="{{ $category->id }}"
                                                        data-action="{{ route('admin.expense-categories.update', $category) }}" data-name="{{ $category->name }}" data-description="{{ $category->description }}" data-active="{{ $category->is_active ? 1 : 0 }}" data-claims="{{ $category->claims_count }}"><i class="ti ti-pencil font-16"></i></button>
                                            @endcan
                                            @unless ($category->claims_count)
                                                @can('delete', $category)
                                                    <form action="{{ route('admin.expense-categories.destroy', $category) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this category? No claim has been made under it.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn-icon btn-icon-danger" title="Delete category"><i class="ti ti-trash font-16"></i></button>
                                                    </form>
                                                @endcan
                                            @endunless
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5">
                                        <i class="ti ti-tag font-24 d-block mb-2"></i>
                                        @if ($state['search'] !== '' || $state['tab'] !== '')
                                            <div class="fw-semibold text-body mb-1">No categories match</div>
                                            <span class="font-13"><a href="{{ route('admin.expense-categories.index') }}">Show all categories</a></span>
                                        @else
                                            <div class="fw-semibold text-body mb-1">No expense categories yet</div>
                                            <span class="font-13">{{ $canCreate ? 'Add the first one with the form on the right. Until then nobody can submit a claim.' : 'Categories set up by HR will appear here.' }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            @if ($canCreate)
                <form method="POST" action="{{ route('admin.expense-categories.store') }}" class="card form-section tone-indigo mb-0" id="category-form">
                    @csrf
                    <div class="form-section-head">
                        <span class="form-section-icon"><i class="ti ti-plus"></i></span>
                        <div class="flex-grow-1">
                            <h5 class="form-section-title">Add a category</h5>
                            <p class="form-section-sub">A kind of expense people can claim, such as travel or meals.</p>
                        </div>
                    </div>
                    <div class="form-section-body">
                        <label class="form-label" for="category-name">Name</label>
                        <input type="text" name="name" id="category-name" value="{{ $fromEdit ? '' : old('name') }}" maxlength="255" class="form-control @error('name') is-invalid @enderror" placeholder="e.g. Meals" required>
                        @if ($suggestions->isNotEmpty())
                            <div class="quick-chips">
                                @foreach ($suggestions->take(6) as $name)
                                    <button type="button" class="quick-chip" data-suggest="{{ $name }}">{{ $name }}</button>
                                @endforeach
                            </div>
                        @endif
                        <label class="form-label mt-3" for="category-description">What it covers <span class="text-muted fw-normal">(optional)</span></label>
                        <textarea name="description" id="category-description" rows="2" maxlength="1000" class="form-control" placeholder="Shown to help people pick the right one">{{ $fromEdit ? '' : old('description') }}</textarea>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-de-primary text-light px-4"><i class="ti ti-check font-14 me-1"></i> Add category</button>
                        </div>
                        <div class="run-note tone-indigo mt-3"><i class="ti ti-info-circle"></i><span>A category that has claims can't be deleted; switch it off instead. Its past claims and reports stay as they are.</span></div>
                    </div>
                </form>
            @endif
        </div>
    </div>

    <div class="modal fade" id="category-modal" tabindex="-1" aria-labelledby="category-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="#" class="modal-content" id="category-edit">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="category-modal-title">Change category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label" for="edit-name">Name</label>
                    <input type="text" name="name" id="edit-name" maxlength="255" class="form-control" required>
                    <label class="form-label mt-3" for="edit-description">What it covers</label>
                    <textarea name="description" id="edit-description" rows="2" maxlength="1000" class="form-control"></textarea>
                    <div class="form-check form-switch form-switch-success mt-3">
                        <input class="form-check-input" type="checkbox" id="edit-active" name="is_active" value="1">
                        <label class="form-check-label" for="edit-active">Active: people can claim under it</label>
                    </div>
                    <div class="font-12 text-muted mt-2" id="edit-note"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-de-primary text-light"><i class="ti ti-check font-14 me-1"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var el = function (id) { return document.getElementById(id); };
            var modal = el('category-modal');
            var fill = function (b) {
                el('category-edit').action = b.dataset.action;
                el('edit-name').value = b.dataset.name; el('edit-description').value = b.dataset.description || '';
                el('edit-active').checked = b.dataset.active === '1';
                var claims = parseInt(b.dataset.claims, 10) || 0;
                el('edit-note').textContent = claims ? 'Renaming it also renames it on the ' + claims + (claims === 1 ? ' claim' : ' claims') + ' already made under it.' : '';
            };
            modal.addEventListener('show.bs.modal', function (event) { if (event.relatedTarget) fill(event.relatedTarget); });

            // Arriving from an old "edit" link opens the dialog for that category.
            var auto = @json($editId);
            var button = auto ? document.querySelector('[data-edit="' + auto + '"]') : null;
            if (button) { fill(button); bootstrap.Modal.getOrCreateInstance(modal).show(); }

            document.querySelectorAll('[data-suggest]').forEach(function (chip) {
                chip.addEventListener('click', function () { el('category-name').value = chip.dataset.suggest; el('category-description').focus(); });
            });
        })();
    </script>
@endpush
