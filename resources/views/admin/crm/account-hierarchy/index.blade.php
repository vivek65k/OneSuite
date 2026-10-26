@extends('layouts.admin')

@section('title', 'Account Hierarchy')

@section('content')
    <x-page-title title="Account Hierarchy" />

    <div class="kpi-strip">
        <x-kpi-card label="Total Accounts" :value="$total" icon="ti-building" tone="indigo" />
        <x-kpi-card label="Top-Level Accounts" :value="$topLevelCount" icon="ti-sitemap" tone="violet" />
        <x-kpi-card label="Child Accounts" :value="$childCount" icon="ti-corner-down-right" tone="teal" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.crm.account-hierarchy.index') }}">
            <div class="list-toolbar">
                <div class="list-toolbar-title"><i class="ti ti-sitemap"></i> Structure</div>
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $search }}" placeholder="Search accounts" aria-label="Search accounts">
                    </div>
                    <a href="{{ route('admin.crm.accounts.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> Add Account</a>
                </div>
            </div>
        </form>

        @if ($search !== '')
            <div class="table-responsive">
                <table class="table table-hover list-table mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Account</th>
                            <th>Parent</th>
                            <th class="text-center">Contacts</th>
                            <th>Owner</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($matches as $account)
                            <tr>
                                <td><a href="{{ route('admin.crm.accounts.edit', $account) }}" class="fw-semibold">{{ $account->name }}</a></td>
                                <td>{{ $account->parent?->name ?? '— top level —' }}</td>
                                <td class="text-center"><span class="tag tone-indigo">{{ $account->contacts_count }}</span></td>
                                <td>{{ $account->owner?->name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-5">No accounts match "{{ $search }}".</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-3">
                @forelse ($roots as $root)
                    <x-crm.account-hierarchy-node :account="$root" />
                @empty
                    <div class="empty-state">
                        <span class="empty-state-icon tone-indigo"><i class="ti ti-sitemap"></i></span>
                        <h5>No accounts yet</h5>
                        <p>Add an account to start building the hierarchy.</p>
                        <a href="{{ route('admin.crm.accounts.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> Add Account</a>
                    </div>
                @endforelse
            </div>
        @endif
    </div>
@endsection
