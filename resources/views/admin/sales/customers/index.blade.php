@extends('layouts.admin')

@section('title', 'Customers & Accounts')

@section('content')
    <x-page-title title="Customers & Accounts" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <p class="text-muted font-13 mb-3"><i class="ti ti-info-circle"></i> Customers are the same accounts managed in CRM - this screen adds the commercial layer (credit limit, payment terms, price list, salesperson) on top.</p>

    <div class="card list-view">
        <div class="list-toolbar">
            <div class="list-toolbar-title"><i class="ti ti-building"></i> Customers <span class="list-tab-count">{{ $accounts->total() }}</span></div>
            <form method="GET" class="list-toolbar-actions">
                <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm" placeholder="Search customers...">
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Account</th><th>Price List</th><th>Salesperson</th><th>Credit Limit</th><th>Outstanding</th><th>Status</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($accounts as $account)
                        <tr>
                            <td class="font-13">{{ $account->name }}</td>
                            <td class="text-muted font-13">{{ $account->salesProfile?->priceList?->name ?? '—' }}</td>
                            <td class="text-muted font-13">{{ $account->salesProfile?->salesperson?->name ?? '—' }}</td>
                            <td class="text-muted font-13">{{ number_format($account->salesProfile?->credit_limit ?? 0, 2) }}</td>
                            <td class="text-muted font-13">{{ number_format($account->salesProfile?->outstanding_balance ?? 0, 2) }}</td>
                            <td>
                                @php($status = $account->salesProfile?->status ?? 'active')
                                <span class="tag tone-{{ $status === 'active' ? 'success' : ($status === 'blocked' ? 'danger' : 'amber') }} font-11">{{ \App\Models\Sales\CustomerProfile::STATUSES[$status] ?? $status }}</span>
                            </td>
                            <td class="text-end"><a href="{{ route('admin.sales.customers.edit', $account) }}" class="btn btn-light btn-sm">Manage</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-5"><i class="ti ti-building font-24 d-block mb-2"></i>No customer accounts yet - add one from CRM → Accounts.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($accounts->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $accounts->links() }}</div></div>
        @endif
    </div>
@endsection
