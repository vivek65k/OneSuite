@extends('layouts.admin')

@section('title', 'Opportunities')

@php
    use Illuminate\Support\Str;

    $statusTone = fn ($status) => match ($status) {
        'won' => 'success',
        'lost' => 'rose',
        default => 'sky',
    };

    $scopeRoute = $scope === 'mine' ? 'admin.crm.opportunities.mine' : 'admin.crm.opportunities.index';
    $panelFilterCount = collect([$stageId, $status])->filter(fn ($v) => filled($v))->count();
    $listUrl = fn (array $overrides = []) => route($scopeRoute, array_filter(
        array_merge(['search' => $search, 'sales_stage_id' => $stageId, 'status' => $status], $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
@endphp

@section('content')
    <x-page-title title="Opportunities" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="All Opportunities" :value="$counts['all']" icon="ti-briefcase" tone="indigo" :href="route('admin.crm.opportunities.index')" />
        <x-kpi-card label="My Opportunities" :value="$counts['mine']" icon="ti-user" tone="violet" :href="route('admin.crm.opportunities.mine')" />
        <x-kpi-card label="Open Pipeline Value" :value="number_format($totalValue)" icon="ti-currency-rupee" tone="sky" :href="route('admin.crm.sales-pipeline.index')" />
        <x-kpi-card label="Won Value" :value="number_format($wonValue)" icon="ti-trophy" tone="success" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route($scopeRoute) }}">
            @if ($sort !== 'expected_close_date')<input type="hidden" name="sort" value="{{ $sort }}">@endif
            @if ($direction !== 'asc')<input type="hidden" name="direction" value="{{ $direction }}">@endif
            <div class="list-toolbar">
                <div class="segmented" role="group" aria-label="Opportunity scope">
                    <a href="{{ route('admin.crm.opportunities.index') }}" class="segmented-item {{ $scope === 'all' ? 'active' : '' }}">All</a>
                    <a href="{{ route('admin.crm.opportunities.mine') }}" class="segmented-item {{ $scope === 'mine' ? 'active' : '' }}">Mine</a>
                </div>
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $search }}" placeholder="Search name" aria-label="Search opportunities">
                    </div>
                    <button type="button" class="btn btn-light btn-sm list-filter-toggle {{ $panelFilterCount ? 'has-filters' : '' }}"
                            data-bs-toggle="collapse" data-bs-target="#opportunities-filter-panel" aria-expanded="false" aria-controls="opportunities-filter-panel">
                        <i class="ti ti-adjustments-horizontal font-15"></i> Filters
                        @if ($panelFilterCount)
                            <span class="list-filter-count">{{ $panelFilterCount }}</span>
                        @endif
                    </button>
                    <a href="{{ route('admin.crm.opportunities.export', array_merge(request()->query(), ['scope' => $scope])) }}" class="btn btn-light btn-sm"><i class="ti ti-download font-14 me-1"></i> Export</a>
                    <a href="{{ route('admin.crm.opportunities.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> Add Opportunity</a>
                </div>
            </div>

            <div class="collapse" id="opportunities-filter-panel">
                <div class="list-filter-panel">
                    <div class="row g-3">
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label">Stage</label>
                            <select name="sales_stage_id" class="form-select form-select-sm">
                                <option value="">Any stage</option>
                                @foreach ($stages as $id => $name)
                                    <option value="{{ $id }}" @selected((string) $stageId === (string) $id)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="">Any status</option>
                                @foreach (\App\Models\Crm\Opportunity::STATUSES as $key => $label)
                                    <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6 col-lg-3 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-de-primary btn-sm text-light flex-fill">Apply</button>
                            <a href="{{ $listUrl(['sales_stage_id' => null, 'status' => null]) }}" class="btn btn-light btn-sm">Clear</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <x-th-sort column="name" :sort="$sort" :direction="$direction" label="Opportunity" />
                        <th>Account</th>
                        <th>Stage</th>
                        <x-th-sort column="amount" :sort="$sort" :direction="$direction" label="Amount" />
                        <x-th-sort column="expected_close_date" :sort="$sort" :direction="$direction" label="Expected Close" />
                        <th>Owner</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($opportunities as $opportunity)
                        <tr>
                            <td>
                                <a href="{{ route('admin.crm.opportunities.edit', $opportunity) }}" class="fw-semibold">{{ $opportunity->name }}</a>
                                <div><span class="tag tone-{{ $statusTone($opportunity->status) }}">{{ \App\Models\Crm\Opportunity::STATUSES[$opportunity->status] ?? $opportunity->status }}</span></div>
                            </td>
                            <td>{{ $opportunity->account?->name ?? '—' }}</td>
                            <td>{{ $opportunity->stage?->name ?? '—' }}</td>
                            <td class="salary-figure">{{ number_format((float) $opportunity->amount) }}</td>
                            <td>{{ $opportunity->expected_close_date?->format('M j, Y') ?? '—' }}</td>
                            <td>{{ $opportunity->owner?->name ?? '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.crm.opportunities.edit', $opportunity) }}" class="btn-icon btn-icon-accent" title="Edit"><i class="ti ti-edit font-16"></i></a>
                                <form action="{{ route('admin.crm.opportunities.destroy', $opportunity) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this opportunity?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="ti ti-briefcase font-24 d-block mb-2"></i>
                                No opportunities found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($opportunities->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $opportunities->links() }}</div></div>
        @endif
    </div>
@endsection
