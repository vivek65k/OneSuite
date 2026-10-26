@extends('layouts.admin')

@section('title', 'Failed Jobs')

@section('content')
    <x-page-title title="Failed Jobs" :breadcrumbs="['Workflows' => route('admin.crm.workflows.index')]" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Failed Runs" :value="$executions->total()" icon="ti-alert-triangle" tone="rose" />
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-alert-triangle"></i> Failed Executions</div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Workflow</th><th>When</th><th>Target</th><th>Error</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($executions as $execution)
                        <tr>
                            <td class="fw-semibold"><a href="{{ route('admin.crm.workflows.executions', $execution->workflow) }}">{{ $execution->workflow->name }}</a></td>
                            <td class="text-muted font-13" style="white-space:nowrap;">{{ $execution->started_at?->format('M j, Y g:i A') }}</td>
                            <td class="text-muted font-13">{{ $execution->context_label ?? '—' }}</td>
                            <td class="text-danger font-13">{{ $execution->error_message }}</td>
                            <td class="text-end">
                                <form action="{{ route('admin.crm.workflows.retry', $execution) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-light btn-sm"><i class="ti ti-refresh font-14 me-1"></i> Retry</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-5"><i class="ti ti-circle-check font-24 d-block mb-2"></i>No failed workflow runs. Everything's healthy.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($executions->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $executions->links() }}</div></div>
        @endif
    </div>
@endsection
