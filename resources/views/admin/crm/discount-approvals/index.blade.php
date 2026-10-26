@extends('layouts.admin')

@section('title', 'Discount Approvals')

@section('content')
    <x-page-title title="Discount Approvals" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Pending" :value="$pendingCount" icon="ti-percentage" tone="amber" />
        <x-kpi-card label="Approved" :value="$approvedCount" icon="ti-circle-check" tone="success" />
        <x-kpi-card label="Rejected" :value="$rejectedCount" icon="ti-x" tone="rose" />
    </div>

    @if ($approvals->isEmpty())
        <div class="card">
            <div class="empty-state">
                <span class="empty-state-icon tone-success"><i class="ti ti-circle-check"></i></span>
                <h5>No discount approvals</h5>
                <p>Quotes with a discount over 15% will show up here for sign-off.</p>
            </div>
        </div>
    @else
        <div class="approval-list">
            @foreach ($approvals as $approval)
                <article class="approval-card tone-{{ $approval->status === 'approved' ? 'success' : ($approval->status === 'rejected' ? 'rose' : 'amber') }}">
                    <div class="approval-who">
                        <span class="list-avatar list-avatar-amber" aria-hidden="true"><i class="ti ti-percentage font-18"></i></span>
                        <div class="min-w-0">
                            <div class="list-user-name">{{ $approval->quotation?->quotation_number ?? '—' }}</div>
                            <div class="list-user-meta">{{ $approval->quotation?->account?->name ?? '—' }}</div>
                            <div class="list-subtext mt-1"><i class="ti ti-clock"></i> requested {{ $approval->created_at->diffForHumans() }} by {{ $approval->requestedBy?->name ?? 'Unknown' }}</div>
                        </div>
                    </div>

                    <div class="approval-leave">
                        <span class="tag tone-amber"><i class="ti ti-percentage"></i>{{ $approval->requested_discount_percent }}% discount</span>
                        <div class="approval-dates">
                            <span class="approval-days">{{ number_format($approval->quotation?->total ?? 0) }}<small>quote total</small></span>
                        </div>
                    </div>

                    <div class="approval-context">
                        <span class="tag tone-{{ $approval->status === 'approved' ? 'success' : ($approval->status === 'rejected' ? 'rose' : 'slate') }}">{{ \App\Models\Crm\DiscountApproval::STATUSES[$approval->status] ?? $approval->status }}</span>
                    </div>

                    @if ($approval->status === 'pending')
                        <div class="approval-actions">
                            <form action="{{ route('admin.crm.discount-approvals.approve', $approval) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-sm review-btn is-approve w-100"><i class="ti ti-check"></i> Approve</button>
                            </form>
                            <div class="dropdown">
                                <button type="button" class="btn btn-sm review-btn is-reject w-100" data-bs-toggle="dropdown" data-bs-auto-close="outside" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                    <i class="ti ti-x"></i> Reject
                                </button>
                                <div class="dropdown-menu dropdown-menu-end p-3 reject-menu">
                                    <form action="{{ route('admin.crm.discount-approvals.reject', $approval) }}" method="POST">
                                        @csrf
                                        <label class="form-label font-12 fw-semibold">Reason (optional)</label>
                                        <textarea name="reason" rows="2" class="form-control form-control-sm mb-2" maxlength="1000"></textarea>
                                        <button type="submit" class="btn btn-sm btn-danger w-100"><i class="ti ti-x font-14 me-1"></i> Reject</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="approval-actions">
                            <a href="{{ route('admin.crm.quotations.edit', $approval->quotation) }}" class="btn btn-light btn-sm w-100">View Quote</a>
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
        @if ($approvals->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $approvals->links() }}</div></div>
        @endif
    @endif
@endsection
