@extends('layouts.admin')

@section('title', 'Notes & Attachments')

@section('content')
    <x-page-title title="Notes & Attachments" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Total Notes" :value="$total" icon="ti-notes" tone="indigo" />
        <x-kpi-card label="With Attachment" :value="$withAttachment" icon="ti-paperclip" tone="violet" />
    </div>

    <div class="card list-view mb-3">
        <div class="list-toolbar">
            <div class="list-toolbar-title"><i class="ti ti-plus"></i> Add a note</div>
        </div>
        <div class="list-filter-panel">
            <form method="POST" action="{{ route('admin.crm.notes.store') }}" enctype="multipart/form-data" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-3">
                    <label class="form-label">Related to <span class="text-danger">*</span></label>
                    <select name="relatable" class="form-select form-select-sm" required>
                        <option value="">Select record</option>
                        @foreach ($relatable as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5"><label class="form-label">Note</label><textarea name="body" rows="1" class="form-control form-control-sm" required></textarea></div>
                <div class="col-md-3"><label class="form-label">Attachment (optional)</label><input type="file" name="file" class="form-control form-control-sm"></div>
                <div class="col-md-1"><button type="submit" class="btn btn-de-primary btn-sm text-light w-100">Add</button></div>
            </form>
        </div>
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.crm.notes.index') }}">
            <div class="list-toolbar">
                <div class="list-toolbar-title"><i class="ti ti-notes"></i> All notes</div>
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $search }}" placeholder="Search note text" aria-label="Search notes">
                    </div>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr><th>Note</th><th>Related To</th><th>Attachment</th><th>Owner</th><th>Date</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr>
                </thead>
                <tbody>
                    @forelse ($notes as $note)
                        <tr>
                            <td>{{ \Illuminate\Support\Str::limit($note->body, 80) }}</td>
                            <td>{{ $note->relatedLabel ?? '—' }}</td>
                            <td>
                                @if ($note->file_path)
                                    <a href="{{ asset('storage/'.$note->file_path) }}" target="_blank" class="tag tone-violet"><i class="ti ti-paperclip"></i> {{ \Illuminate\Support\Str::limit($note->file_name, 20) }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $note->owner?->name ?? '—' }}</td>
                            <td>{{ $note->created_at->format('M j, Y') }}</td>
                            <td class="text-end">
                                <form action="{{ route('admin.crm.notes.destroy', $note) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this note?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5"><i class="ti ti-notes font-24 d-block mb-2"></i>No notes yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($notes->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $notes->links() }}</div></div>
        @endif
    </div>
@endsection
