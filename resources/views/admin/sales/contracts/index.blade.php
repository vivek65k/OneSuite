@extends('layouts.admin')

@section('title', 'Contracts & Agreements')

@php
    $statusTone = fn ($status) => match ($status) {
        'active' => 'success',
        'expired' => 'amber',
        'terminated' => 'danger',
        default => 'slate',
    };
@endphp

@section('content')
    <x-page-title title="Contracts & Agreements" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip mb-3">
        <x-kpi-card label="Total Contracts" :value="$total" icon="ti-file-text" />
        <x-kpi-card label="Active" :value="$activeCount" icon="ti-circle-check" tone="success" />
        <x-kpi-card label="Active Value" :value="number_format($totalValue, 2)" icon="ti-currency-rupee" tone="violet" />
        <x-kpi-card label="Up For Renewal" :value="$renewalCount" icon="ti-refresh" tone="amber" />
        <x-kpi-card label="Due For Billing" :value="$dueForBilling" icon="ti-repeat" tone="sky" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.sales.contracts.index') }}">
            <div class="list-toolbar">
                <div class="list-toolbar-title"><i class="ti ti-file-text"></i> Contracts <span class="list-tab-count">{{ $contracts->total() }}</span></div>
                <div class="list-toolbar-actions">
                    <input type="search" name="search" class="form-control form-control-sm" value="{{ $search }}" placeholder="Search title or number">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()" style="width:auto;">
                        <option value="">Any status</option>
                        @foreach (\App\Models\Crm\Contract::STATUSES as $key => $label)
                            <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <a href="{{ route('admin.sales.contracts.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> New Contract</a>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Contract #</th><th>Title</th><th>Account</th><th>Status</th><th>Billing</th><th>End Date</th><th>Value</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($contracts as $contract)
                        <tr>
                            <td class="font-monospace font-13">{{ $contract->contract_number }}</td>
                            <td><a href="{{ route('admin.sales.contracts.edit', $contract) }}" class="fw-semibold font-13">{{ $contract->title }}</a></td>
                            <td class="text-muted font-13">{{ $contract->account?->name ?? '—' }}</td>
                            <td><span class="tag tone-{{ $statusTone($contract->status) }} font-11">{{ \App\Models\Crm\Contract::STATUSES[$contract->status] ?? $contract->status }}</span></td>
                            <td class="text-muted font-13">{{ \App\Models\Crm\Contract::BILLING_FREQUENCIES[$contract->billing_frequency] ?? $contract->billing_frequency }}</td>
                            <td class="text-muted font-13">{{ $contract->end_date?->format('M j, Y') ?? '—' }}</td>
                            <td class="text-muted font-13">{{ number_format($contract->value, 2) }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.sales.contracts.edit', $contract) }}" class="btn-icon btn-icon-accent" title="Edit"><i class="ti ti-edit font-16"></i></a>
                                <form action="{{ route('admin.sales.contracts.destroy', $contract) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this contract?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-5"><i class="ti ti-file-text font-24 d-block mb-2"></i>No contracts yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($contracts->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $contracts->links() }}</div></div>
        @endif
    </div>
@endsection
