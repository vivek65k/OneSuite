@extends('layouts.admin')

@section('title', 'Web Forms')

@section('content')
    <x-page-title title="Web Forms" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Total Forms" :value="$forms->total()" icon="ti-forms" tone="indigo" />
        <x-kpi-card label="Total Submissions" :value="$totalSubmissions" icon="ti-send" tone="success" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.crm.web-forms.index') }}">
            <div class="list-toolbar">
                <div class="list-toolbar-title"><i class="ti ti-forms"></i> All Web Forms</div>
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $search }}" placeholder="Search forms" aria-label="Search">
                    </div>
                    <a href="{{ route('admin.crm.web-forms.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> New Form</a>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr><th>Name</th><th>Campaign</th><th>Submissions</th><th>Status</th><th>Public Link</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr>
                </thead>
                <tbody>
                    @forelse ($forms as $form)
                        <tr>
                            <td class="fw-semibold"><a href="{{ route('admin.crm.web-forms.edit', $form) }}">{{ $form->name }}</a></td>
                            <td class="text-muted font-13">{{ $form->campaign?->name ?? '—' }}</td>
                            <td><span class="tag tone-violet">{{ $form->submissions_count }}</span></td>
                            <td><span class="tag tone-{{ $form->is_active ? 'success' : 'slate' }}">{{ $form->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td>
                                <a href="{{ route('crm.public.web-forms.show', $form->slug) }}" target="_blank" class="font-12 text-truncate d-inline-block" style="max-width:220px;">{{ route('crm.public.web-forms.show', $form->slug) }}</a>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.crm.web-forms.edit', $form) }}" class="btn-icon btn-icon-accent" title="Edit"><i class="ti ti-pencil font-16"></i></a>
                                <form action="{{ route('admin.crm.web-forms.destroy', $form) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this form?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5"><i class="ti ti-forms font-24 d-block mb-2"></i>No web forms yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($forms->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $forms->links() }}</div></div>
        @endif
    </div>
@endsection
