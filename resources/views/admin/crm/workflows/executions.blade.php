@extends('layouts.admin')

@section('title', 'Execution History')

@section('content')
    <x-page-title title="Execution History" :breadcrumbs="['Workflows' => route('admin.crm.workflows.index')]" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-player-play"></i> Run "{{ $workflow->name }}" now</div></div>
        <div class="list-filter-panel">
            <form method="POST" action="{{ route('admin.crm.workflows.run', $workflow) }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-6">
                    <label class="form-label">Target record (optional)</label>
                    <select name="relatable" class="form-select form-select-sm">
                        <option value="">No specific record</option>
                        @foreach ($relatable as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><button type="submit" class="btn btn-de-primary btn-sm text-light w-100"><i class="ti ti-player-play font-14 me-1"></i> Run</button></div>
            </form>
        </div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-history"></i> Runs <span class="list-tab-count">{{ $executions->total() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>When</th><th>Trigger</th><th>Target</th><th>Status</th><th>Steps</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($executions as $execution)
                        <tr>
                            <td class="text-muted font-13" style="white-space:nowrap;">{{ $execution->started_at?->format('M j, Y g:i A') }}</td>
                            <td><span class="tag tone-indigo">{{ ucfirst($execution->trigger_type) }}</span></td>
                            <td class="text-muted font-13">{{ $execution->context_label ?? '—' }}</td>
                            <td><span class="tag tone-{{ $execution->status === 'success' ? 'success' : 'rose' }}">{{ \App\Models\Crm\WorkflowExecution::STATUSES[$execution->status] ?? $execution->status }}</span></td>
                            <td>
                                @foreach ($execution->steps_log ?? [] as $step)
                                    <span class="tag tone-{{ $step['outcome'] === 'success' ? 'success' : ($step['outcome'] === 'simulated' ? 'amber' : 'slate') }} me-1" title="{{ $step['message'] ?? '' }}">{{ $step['step_type'] }}</span>
                                @endforeach
                                @if ($execution->error_message)
                                    <div class="text-danger font-12 mt-1">{{ $execution->error_message }}</div>
                                @endif
                            </td>
                            <td class="text-end">
                                @if ($execution->status === 'failed')
                                    <form action="{{ route('admin.crm.workflows.retry', $execution) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn-icon" title="Retry"><i class="ti ti-refresh font-16"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5"><i class="ti ti-history font-24 d-block mb-2"></i>This workflow hasn't run yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($executions->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $executions->links() }}</div></div>
        @endif
    </div>
@endsection
