@extends('layouts.admin')

@section('title', 'Customer Feedback')

@section('content')
    <x-page-title title="Customer Feedback" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Average Rating" value="{{ $avgRating }} / 5" icon="ti-star" tone="amber" />
        <x-kpi-card label="Total Responses" :value="$totalFeedback" icon="ti-message-report" tone="indigo" />
    </div>

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> Log feedback</div></div>
        <div class="list-filter-panel">
            <form method="POST" action="{{ route('admin.crm.customer-feedback.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-3">
                    <label class="form-label">Account</label>
                    <select name="account_id" class="form-select form-select-sm">
                        <option value="">None</option>
                        @foreach ($accounts as $account)
                            <option value="{{ $account->id }}">{{ $account->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Rating</label>
                    <select name="rating" class="form-select form-select-sm" required>
                        @for ($i = 5; $i >= 1; $i--)
                            <option value="{{ $i }}">{{ $i }} star{{ $i > 1 ? 's' : '' }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Source</label>
                    <input type="text" name="source" class="form-control form-control-sm" placeholder="Survey, Email...">
                </div>
                <div class="col-md-4"><label class="form-label">Comment</label><input type="text" name="comment" class="form-control form-control-sm"></div>
                <div class="col-md-1"><button type="submit" class="btn btn-de-primary btn-sm text-light w-100">Add</button></div>
            </form>
        </div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar">
            <div class="list-toolbar-title"><i class="ti ti-message-report"></i> All Feedback <span class="list-tab-count">{{ $feedback->total() }}</span></div>
            <div class="list-toolbar-actions">
                <div class="segmented" role="group" aria-label="Rating">
                    <a href="{{ request()->fullUrlWithQuery(['rating' => null]) }}" class="segmented-item {{ $ratingFilter === '' ? 'active' : '' }}">All</a>
                    @for ($i = 5; $i >= 1; $i--)
                        <a href="{{ request()->fullUrlWithQuery(['rating' => $i]) }}" class="segmented-item {{ $ratingFilter === (string) $i ? 'active' : '' }}">{{ $i }}★</a>
                    @endfor
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Rating</th><th>Account / Contact</th><th>Source</th><th>Comment</th><th>Date</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($feedback as $item)
                        <tr>
                            <td><span class="tag tone-{{ $item->rating >= 4 ? 'success' : ($item->rating <= 2 ? 'rose' : 'amber') }}">{{ $item->rating }}★</span></td>
                            <td class="text-muted font-13">{{ $item->account?->name ?? $item->contact?->name ?? '—' }}</td>
                            <td class="text-muted font-13">{{ $item->source ?? '—' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($item->comment, 60) ?: '—' }}</td>
                            <td class="text-muted font-13">{{ $item->created_at->format('M j, Y') }}</td>
                            <td class="text-end">
                                <form action="{{ route('admin.crm.customer-feedback.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this feedback?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Remove"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5"><i class="ti ti-message-report font-24 d-block mb-2"></i>No feedback yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($feedback->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $feedback->links() }}</div></div>
        @endif
    </div>
@endsection
