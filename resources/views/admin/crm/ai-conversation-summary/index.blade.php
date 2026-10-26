@extends('layouts.admin')

@section('title', 'Conversation Summaries')

@section('content')
    <x-page-title title="Conversation Summaries" />

    <div class="card list-view mb-3">
        <div class="list-filter-panel">
            <form method="GET" action="{{ route('admin.crm.ai-conversation-summary.index') }}" class="d-flex gap-2 align-items-end">
                <div class="flex-fill">
                    <label class="form-label">Account</label>
                    <select name="account_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Select account</option>
                        @foreach ($accounts as $account)
                            <option value="{{ $account->id }}" @selected((string) $accountId === (string) $account->id)>{{ $account->name }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    @if (! $summary)
        <div class="card"><div class="empty-state"><span class="empty-state-icon tone-indigo"><i class="ti ti-messages"></i></span><h5>No account selected</h5><p>Pick an account to see its interaction summary.</p></div></div>
    @else
        <div class="card mb-3">
            <div class="p-3">
                <div class="fw-semibold mb-2"><i class="ti ti-messages"></i> Summary for {{ $summary['account']->name }}</div>
                <ul class="mb-0">
                    @foreach ($summary['lines'] as $line)
                        <li class="font-13 text-muted">{{ $line }}</li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="card list-view">
            <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-history"></i> Recent Activity</div></div>
            <div class="table-responsive">
                <table class="table table-hover list-table mb-0">
                    <thead class="thead-light"><tr><th>Type</th><th>Subject</th><th>Owner</th><th>Date</th></tr></thead>
                    <tbody>
                        @forelse ($summary['activities'] as $activity)
                            <tr>
                                <td><span class="tag tone-indigo">{{ \App\Models\Crm\Activity::TYPES[$activity->type] ?? $activity->type }}</span></td>
                                <td>{{ $activity->subject }}</td>
                                <td class="text-muted font-13">{{ $activity->owner?->name ?? '—' }}</td>
                                <td class="text-muted font-13">{{ $activity->created_at->format('M j, Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">No activity yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
