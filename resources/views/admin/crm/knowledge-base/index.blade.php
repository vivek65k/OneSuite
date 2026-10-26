@extends('layouts.admin')

@section('title', 'Knowledge Base')

@section('content')
    <x-page-title title="Knowledge Base" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Published Articles" :value="$publishedCount" icon="ti-book" tone="indigo" />
        <x-kpi-card label="Total Views" :value="$totalViews" icon="ti-eye" tone="sky" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.crm.knowledge-base.index') }}">
            <div class="list-toolbar">
                <div class="list-toolbar-title"><i class="ti ti-book"></i> All Articles</div>
                <div class="list-toolbar-actions">
                    @if ($categories->isNotEmpty())
                        <select name="category" class="form-select form-select-sm" onchange="this.form.submit()" style="width:auto;">
                            <option value="">All Categories</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category }}" @selected($categoryFilter === $category)>{{ $category }}</option>
                            @endforeach
                        </select>
                    @endif
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $search }}" placeholder="Search articles" aria-label="Search">
                    </div>
                    <a href="{{ route('admin.crm.knowledge-base.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> New Article</a>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Title</th><th>Category</th><th>Status</th><th>Views</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($articles as $article)
                        <tr>
                            <td class="fw-semibold"><a href="{{ route('admin.crm.knowledge-base.edit', $article) }}">{{ $article->title }}</a></td>
                            <td class="text-muted font-13">{{ $article->category ?? '—' }}</td>
                            <td><span class="tag tone-{{ $article->is_published ? 'success' : 'slate' }}">{{ $article->is_published ? 'Published' : 'Draft' }}</span></td>
                            <td>{{ $article->views_count }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.crm.knowledge-base.edit', $article) }}" class="btn-icon btn-icon-accent" title="Edit"><i class="ti ti-pencil font-16"></i></a>
                                <form action="{{ route('admin.crm.knowledge-base.destroy', $article) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this article?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-5"><i class="ti ti-book font-24 d-block mb-2"></i>No articles yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($articles->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $articles->links() }}</div></div>
        @endif
    </div>
@endsection
