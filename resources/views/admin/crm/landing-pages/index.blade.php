@extends('layouts.admin')

@section('title', 'Landing Pages')

@section('content')
    <x-page-title title="Landing Pages" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Total Pages" :value="$pages->total()" icon="ti-browser" tone="indigo" />
        <x-kpi-card label="Total Views" :value="$totalViews" icon="ti-eye" tone="sky" />
        <x-kpi-card label="Conversions" :value="$totalConversions" icon="ti-target" tone="success" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.crm.landing-pages.index') }}">
            <div class="list-toolbar">
                <div class="segmented" role="group" aria-label="Status">
                    <a href="{{ request()->fullUrlWithQuery(['status' => null]) }}" class="segmented-item {{ $statusFilter === '' ? 'active' : '' }}">All</a>
                    @foreach ($statuses as $key => $label)
                        <a href="{{ request()->fullUrlWithQuery(['status' => $key]) }}" class="segmented-item {{ $statusFilter === $key ? 'active' : '' }}">{{ $label }}</a>
                    @endforeach
                </div>
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $search }}" placeholder="Search pages" aria-label="Search">
                    </div>
                    <a href="{{ route('admin.crm.landing-pages.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> New Page</a>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr><th>Name</th><th>Campaign</th><th>Status</th><th>Views</th><th>Conversions</th><th>Conv. Rate</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr>
                </thead>
                <tbody>
                    @forelse ($pages as $page)
                        <tr>
                            <td class="fw-semibold"><a href="{{ route('admin.crm.landing-pages.edit', $page) }}">{{ $page->name }}</a></td>
                            <td class="text-muted font-13">{{ $page->campaign?->name ?? '—' }}</td>
                            <td><span class="tag tone-{{ $page->status === 'published' ? 'success' : 'slate' }}">{{ \App\Models\Crm\LandingPage::STATUSES[$page->status] ?? $page->status }}</span></td>
                            <td>{{ $page->views_count }}</td>
                            <td>{{ $page->conversions_count }}</td>
                            <td>{{ $page->conversion_rate }}%</td>
                            <td class="text-end">
                                @if ($page->status === 'published')
                                    <a href="{{ route('crm.public.landing-pages.show', $page->slug) }}" target="_blank" class="btn-icon" title="View live"><i class="ti ti-external-link font-16"></i></a>
                                @endif
                                <a href="{{ route('admin.crm.landing-pages.edit', $page) }}" class="btn-icon btn-icon-accent" title="Edit"><i class="ti ti-pencil font-16"></i></a>
                                <form action="{{ route('admin.crm.landing-pages.destroy', $page) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this page?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-5"><i class="ti ti-browser font-24 d-block mb-2"></i>No landing pages yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($pages->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $pages->links() }}</div></div>
        @endif
    </div>
@endsection
