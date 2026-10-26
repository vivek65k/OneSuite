@extends('layouts.admin')

@section('title', 'Workflow Designer')

@section('content')
    <x-page-title title="Workflow Designer" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card list-view">
        <div class="list-toolbar">
            <div class="list-toolbar-title"><i class="ti ti-git-branch"></i> All Workflows <span class="list-tab-count">{{ $workflows->count() }}</span></div>
            <div class="list-toolbar-actions">
                <a href="{{ route('admin.crm.workflows.failed-jobs') }}" class="btn btn-light btn-sm"><i class="ti ti-alert-triangle font-14 me-1"></i> Failed Jobs</a>
                <a href="{{ route('admin.crm.workflows.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> New Workflow</a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Name</th><th>Trigger</th><th>Steps</th><th>Runs</th><th>Status</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($workflows as $workflow)
                        <tr>
                            <td class="fw-semibold"><a href="{{ route('admin.crm.workflows.edit', $workflow) }}">{{ $workflow->name }}</a></td>
                            <td class="text-muted font-13">{{ \App\Models\Crm\AutomationRule::TRIGGER_EVENTS[$workflow->trigger_event] ?? ($workflow->trigger_event ?: '—') }}</td>
                            <td><span class="tag tone-indigo">{{ $workflow->steps_count }}</span></td>
                            <td><a href="{{ route('admin.crm.workflows.executions', $workflow) }}" class="tag tone-violet">{{ $workflow->executions_count }}</a></td>
                            <td>
                                <form action="{{ route('admin.crm.workflows.toggle', $workflow) }}" method="POST" class="d-inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="switch-btn {{ $workflow->is_active ? 'is-on' : '' }}" role="switch" aria-checked="{{ $workflow->is_active ? 'true' : 'false' }}">
                                        <span class="visually-hidden">Toggle {{ $workflow->name }}</span>
                                    </button>
                                </form>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.crm.workflows.executions', $workflow) }}" class="btn-icon" title="Execution history"><i class="ti ti-history font-16"></i></a>
                                <a href="{{ route('admin.crm.workflows.edit', $workflow) }}" class="btn-icon btn-icon-accent" title="Edit"><i class="ti ti-pencil font-16"></i></a>
                                <form action="{{ route('admin.crm.workflows.destroy', $workflow) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this workflow?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5"><i class="ti ti-git-branch font-24 d-block mb-2"></i>No workflows yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
