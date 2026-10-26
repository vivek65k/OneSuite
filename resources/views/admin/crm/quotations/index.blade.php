@extends('layouts.admin')

@section('title', 'Quotations')

@php
    $statusTone = fn ($status) => match ($status) {
        'accepted' => 'success',
        'rejected', 'expired' => 'rose',
        'pending_approval' => 'amber',
        'sent' => 'sky',
        default => 'slate',
    };
@endphp

@section('content')
    <x-page-title title="Quotations" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Total Quotations" :value="$total" icon="ti-file-invoice" tone="indigo" />
        <x-kpi-card label="Pending Approval" :value="$pendingApprovalCount" icon="ti-percentage" tone="amber" :href="route('admin.crm.discount-approvals.index')" />
        <x-kpi-card label="Accepted Value" :value="number_format($acceptedValue)" icon="ti-currency-rupee" tone="success" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.crm.quotations.index') }}">
            @if ($sort !== 'created_at')<input type="hidden" name="sort" value="{{ $sort }}">@endif
            @if ($direction !== 'desc')<input type="hidden" name="direction" value="{{ $direction }}">@endif
            <div class="list-toolbar">
                <div class="list-toolbar-title"><i class="ti ti-file-invoice"></i> Quotations</div>
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $search }}" placeholder="Search number or account" aria-label="Search quotations">
                    </div>
                    <select name="status" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Status">
                        <option value="">Any status</option>
                        @foreach (\App\Models\Crm\Quotation::STATUSES as $key => $label)
                            <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <a href="{{ route('admin.crm.quotations.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> New Quotation</a>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <x-th-sort column="quotation_number" :sort="$sort" :direction="$direction" label="Number" />
                        <th>Account</th>
                        <th>Status</th>
                        <x-th-sort column="total" :sort="$sort" :direction="$direction" label="Total" />
                        <x-th-sort column="valid_until" :sort="$sort" :direction="$direction" label="Valid Until" />
                        <th>Owner</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($quotations as $quotation)
                        <tr>
                            <td><a href="{{ route('admin.crm.quotations.edit', $quotation) }}" class="fw-semibold">{{ $quotation->quotation_number }}</a></td>
                            <td>{{ $quotation->account?->name ?? '—' }}</td>
                            <td><span class="tag tone-{{ $statusTone($quotation->status) }}">{{ \App\Models\Crm\Quotation::STATUSES[$quotation->status] ?? $quotation->status }}</span></td>
                            <td class="salary-figure">{{ number_format($quotation->total, 2) }}</td>
                            <td>{{ $quotation->valid_until?->format('M j, Y') ?? '—' }}</td>
                            <td>{{ $quotation->owner?->name ?? '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.crm.quotations.edit', $quotation) }}" class="btn-icon btn-icon-accent" title="Edit"><i class="ti ti-edit font-16"></i></a>
                                <form action="{{ route('admin.crm.quotations.destroy', $quotation) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this quotation?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="ti ti-file-invoice font-24 d-block mb-2"></i>
                                No quotations yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($quotations->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $quotations->links() }}</div></div>
        @endif
    </div>
@endsection
