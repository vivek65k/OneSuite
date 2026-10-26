@extends('layouts.admin')

@section('title', 'Customer Timeline')

@section('content')
    <x-page-title title="Customer Timeline" />

    <div class="card list-view mb-3">
        <div class="list-filter-panel">
            <form method="GET" action="{{ route('admin.crm.customer-timeline.index') }}" class="d-flex gap-2 align-items-end">
                <div class="flex-fill">
                    <label class="form-label">Account</label>
                    <select name="account_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        @forelse ($accounts as $id => $name)
                            <option value="{{ $id }}" @selected($account && $account->id === $id)>{{ $name }}</option>
                        @empty
                            <option value="">No accounts yet</option>
                        @endforelse
                    </select>
                </div>
            </form>
        </div>
    </div>

    @if (! $account)
        <div class="card">
            <div class="empty-state">
                <span class="empty-state-icon tone-indigo"><i class="ti ti-clock"></i></span>
                <h5>No account to show</h5>
                <p>Add an account first, then its timeline will appear here.</p>
                <a href="{{ route('admin.crm.accounts.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> Add Account</a>
            </div>
        </div>
    @else
        <div class="card list-view">
            <form method="GET" action="{{ route('admin.crm.customer-timeline.index') }}">
                <input type="hidden" name="account_id" value="{{ $account->id }}">
                <div class="list-toolbar">
                    <div class="segmented" role="group" aria-label="Event type">
                        <a href="{{ route('admin.crm.customer-timeline.index', ['account_id' => $account->id]) }}" class="segmented-item {{ $type === '' ? 'active' : '' }}">All</a>
                        <a href="{{ route('admin.crm.customer-timeline.index', ['account_id' => $account->id, 'type' => 'account']) }}" class="segmented-item {{ $type === 'account' ? 'active' : '' }}">Account</a>
                        <a href="{{ route('admin.crm.customer-timeline.index', ['account_id' => $account->id, 'type' => 'contact']) }}" class="segmented-item {{ $type === 'contact' ? 'active' : '' }}">Contacts</a>
                        <a href="{{ route('admin.crm.customer-timeline.index', ['account_id' => $account->id, 'type' => 'conversion']) }}" class="segmented-item {{ $type === 'conversion' ? 'active' : '' }}">Conversions</a>
                    </div>
                    <div class="list-toolbar-actions">
                        <a href="{{ route('admin.crm.customer-360.index', ['account_id' => $account->id]) }}" class="btn btn-light btn-sm"><i class="ti ti-eye font-14 me-1"></i> 360° View</a>
                    </div>
                </div>
            </form>
            <div class="p-3">
                @forelse ($events as $event)
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="avatar-box avatar-md bg-soft-{{ $event['tone'] }} rounded-circle flex-shrink-0"><span class="avatar-title bg-transparent"><i class="ti {{ $event['icon'] }} font-18"></i></span></span>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">{{ $event['title'] }}</div>
                            <div class="text-muted font-13">{{ $event['detail'] }}</div>
                        </div>
                        <div class="text-muted font-13 flex-shrink-0">{{ $event['date']?->format('M j, Y g:i A') }}</div>
                    </div>
                @empty
                    <div class="empty-state">
                        <span class="empty-state-icon tone-indigo"><i class="ti ti-clock"></i></span>
                        <h5>No activity yet</h5>
                        <p>Nothing has happened on this account yet.</p>
                    </div>
                @endforelse
            </div>
        </div>
    @endif
@endsection
