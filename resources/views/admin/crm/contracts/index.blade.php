@extends('layouts.admin')

@section('title', 'Contracts')

@php
    $statusTone = fn ($status) => match ($status) {
        'active' => 'success',
        'expired' => 'amber',
        'terminated' => 'rose',
        default => 'slate',
    };
@endphp

@section('content')
    <x-page-title title="Contracts" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Total Contracts" :value="$total" icon="ti-file-invoice" tone="indigo" />
        <x-kpi-card label="Active" :value="$activeCount" icon="ti-circle-check" tone="success" />
        <x-kpi-card label="Active Value" :value="number_format($totalValue)" icon="ti-currency-rupee" tone="violet" />
        <x-kpi-card label="Up For Renewal" :value="$renewalCount" icon="ti-refresh" tone="amber" :href="route('admin.crm.renewal-management.index')" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.crm.contracts.index') }}">
            <div class="list-toolbar">
                <div class="list-toolbar-title"><i class="ti ti-file-invoice"></i> Contracts</div>
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $search }}" placeholder="Search title or number" aria-label="Search contracts">
                    </div>
                    <select name="status" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Status">
                        <option value="">Any status</option>
                        @foreach (\App\Models\Crm\Contract::STATUSES as $key => $label)
                            <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <a href="{{ route('admin.crm.contracts.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> New Contract</a>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr><th>Contract #</th><th>Title</th><th>Account</th><th>Status</th><th>End Date</th><th class="text-end">Value</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr>
                </thead>
                <tbody>
                    @forelse ($contracts as $contract)
                        <tr>
                            <td>{{ $contract->contract_number }}</td>
                            <td><a href="{{ route('admin.crm.contracts.edit', $contract) }}" class="fw-semibold">{{ $contract->title }}</a></td>
                            <td>{{ $contract->account?->name ?? '—' }}</td>
                            <td><span class="tag tone-{{ $statusTone($contract->status) }}">{{ \App\Models\Crm\Contract::STATUSES[$contract->status] ?? $contract->status }}</span></td>
                            <td>{{ $contract->end_date?->format('M j, Y') ?? '—' }}</td>
                            <td class="text-end salary-figure">{{ number_format($contract->value, 2) }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.crm.contracts.edit', $contract) }}" class="btn-icon btn-icon-accent" title="Edit"><i class="ti ti-edit font-16"></i></a>
                                <form action="{{ route('admin.crm.contracts.destroy', $contract) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this contract?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-5"><i class="ti ti-file-invoice font-24 d-block mb-2"></i>No contracts yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($contracts->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $contracts->links() }}</div></div>
        @endif
    </div>
@endsection
