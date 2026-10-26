@extends('layouts.admin')

@section('title', 'Escalations')

@section('content')
    <x-page-title title="Escalations" />

    <div class="kpi-strip">
        <x-kpi-card label="Escalated" :value="$escalatedCount" icon="ti-arrow-up-circle" tone="rose" />
        <x-kpi-card label="Overdue" :value="$overdueCount" icon="ti-alert-triangle" tone="amber" />
    </div>

    <div class="card list-view">
        <div class="list-toolbar">
            <div class="segmented" role="group" aria-label="View">
                <a href="{{ route('admin.crm.escalations.index', ['view' => 'escalated']) }}" class="segmented-item {{ $view === 'escalated' ? 'active' : '' }}">Escalated</a>
                <a href="{{ route('admin.crm.escalations.index', ['view' => 'overdue']) }}" class="segmented-item {{ $view === 'overdue' ? 'active' : '' }}">SLA Overdue</a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Case</th><th>Account</th><th>Owner</th><th>Escalated To</th><th>When</th></tr></thead>
                <tbody>
                    @forelse ($cases as $case)
                        <tr>
                            <td class="fw-semibold"><a href="{{ route('admin.crm.cases.edit', $case) }}">{{ $case->subject }}</a></td>
                            <td class="text-muted font-13">{{ $case->account?->name ?? '—' }}</td>
                            <td class="text-muted font-13">{{ $case->assignedTo?->name ?? '—' }}</td>
                            <td class="text-muted font-13">{{ $case->escalatedTo?->name ?? '—' }}</td>
                            <td class="text-muted font-13">{{ $view === 'overdue' ? $case->resolution_due_at?->format('M j, Y') : $case->escalated_at?->format('M j, Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-5"><i class="ti ti-arrow-up-circle font-24 d-block mb-2"></i>Nothing here.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($cases->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $cases->links() }}</div></div>
        @endif
    </div>
@endsection
