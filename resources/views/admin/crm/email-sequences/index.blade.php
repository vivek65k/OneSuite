@extends('layouts.admin')

@section('title', 'Email Sequences')

@section('content')
    <x-page-title title="Email Sequences" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card list-view">
        <div class="list-toolbar">
            <div class="list-toolbar-title"><i class="ti ti-mail-forward"></i> All Sequences <span class="list-tab-count">{{ $sequences->count() }}</span></div>
            <div class="list-toolbar-actions">
                <a href="{{ route('admin.crm.email-sequences.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> New Sequence</a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Name</th><th>Steps</th><th>Enrollments</th><th>Status</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($sequences as $sequence)
                        <tr>
                            <td class="fw-semibold"><a href="{{ route('admin.crm.email-sequences.edit', $sequence) }}">{{ $sequence->name }}</a></td>
                            <td><span class="tag tone-indigo">{{ $sequence->steps_count }}</span></td>
                            <td><span class="tag tone-violet">{{ $sequence->enrollments_count }}</span></td>
                            <td>
                                <form action="{{ route('admin.crm.email-sequences.toggle', $sequence) }}" method="POST" class="d-inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="switch-btn {{ $sequence->is_active ? 'is-on' : '' }}" role="switch" aria-checked="{{ $sequence->is_active ? 'true' : 'false' }}">
                                        <span class="visually-hidden">Toggle {{ $sequence->name }}</span>
                                    </button>
                                </form>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.crm.email-sequences.edit', $sequence) }}" class="btn-icon btn-icon-accent" title="Edit"><i class="ti ti-pencil font-16"></i></a>
                                <form action="{{ route('admin.crm.email-sequences.destroy', $sequence) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this sequence?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-5"><i class="ti ti-mail-forward font-24 d-block mb-2"></i>No sequences yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
