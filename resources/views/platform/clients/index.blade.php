@extends('layouts.platform')

@section('title', 'Clients')

@section('content')
    <x-page-title title="Clients" />

    @include('platform.clients._invite-alert')

    <div class="card">
        <div class="card-header">
            <form method="GET" action="{{ route('platform.clients.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent"><i class="ti ti-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control" value="{{ $search }}" placeholder="Search client name or login email...">
                    </div>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-de-primary text-light btn-sm"><i class="ti ti-filter font-14 me-1"></i> Filter</button>
                    <a href="{{ route('platform.clients.index') }}" class="btn btn-light btn-sm"><i class="ti ti-x font-14 me-1"></i> Reset</a>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="d-flex align-items-center flex-wrap gap-2 mb-3">
                <a href="{{ route('platform.clients.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> New client</a>
            </div>

            <div class="table-responsive table-responsive-sticky">
                <table class="table table-striped table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Name</th>
                            <th>Admin login</th>
                            <th>Apps</th>
                            <th>Users</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($clients as $client)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $client->name }}</div>
                                    @if ($client->email)
                                        <div class="text-muted font-12">{{ $client->email }}</div>
                                    @endif
                                </td>
                                <td>
                                    @forelse ($client->admins as $admin)
                                        <div class="client-admin @unless ($loop->last) mb-2 @endunless">
                                            <span class="client-admin-avatar">{{ Str::upper(Str::substr($admin->name, 0, 1)) }}</span>
                                            <div class="client-admin-meta">
                                                <div class="client-admin-email">
                                                    <span>{{ $admin->email }}</span>
                                                    <button type="button" class="client-admin-copy" data-copy="{{ $admin->email }}" title="Copy login email"><i class="ti ti-copy"></i></button>
                                                </div>
                                                <div class="text-muted font-12">
                                                    {{ $admin->name }}
                                                    {{-- invitation_accepted_at is set by the invite flow; seeded /
                                                         pre-invite accounts only have email_verified_at. --}}
                                                    @if ($admin->invitation_accepted_at || $admin->email_verified_at)
                                                        <span class="badge badge-soft-success ms-1">Password set</span>
                                                    @else
                                                        <span class="badge badge-soft-warning ms-1">Invite not accepted</span>
                                                        <form action="{{ route('platform.clients.resend-invite', [$client, $admin]) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-link btn-sm p-0 ms-1 font-12 align-baseline">Resend invite</button>
                                                        </form>
                                                    @endif
                                                    @unless ($admin->is_active)
                                                        <span class="badge badge-soft-danger ms-1">Inactive</span>
                                                    @endunless
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <span class="text-muted font-12">No admin login</span>
                                    @endforelse
                                </td>
                                <td>
                                    @if ($client->app_access === null)
                                        <span class="badge badge-soft-primary">All apps</span>
                                    @else
                                        @foreach ($client->app_access as $key)
                                            <span class="badge badge-soft-secondary me-1">{{ $apps[$key]['label'] ?? $key }}</span>
                                        @endforeach
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('platform.clients.users', $client) }}" class="client-users-link" title="View all users of {{ $client->name }}">
                                        <i class="ti ti-users"></i>{{ $client->users_count }}
                                    </a>
                                    <div class="font-11 mt-1 text-nowrap {{ $client->max_users !== null && $client->active_users_count >= $client->max_users ? 'text-danger fw-semibold' : 'text-muted' }}">
                                        @if ($client->max_users === null)
                                            No limit
                                        @else
                                            {{ $client->active_users_count }} / {{ $client->max_users }} active
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="badge {{ $client->is_active ? 'badge-soft-success' : 'badge-soft-danger' }}">
                                        {{ $client->is_active ? 'Active' : 'Suspended' }}
                                    </span>
                                </td>
                                <td>{{ $client->created_at->format('M j, Y') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('platform.clients.users', $client) }}" class="btn-icon" title="Users"><i class="ti ti-users font-16"></i></a>
                                    <a href="{{ route('platform.clients.edit', $client) }}" class="btn-icon" title="Edit"><i class="ti ti-pencil font-16"></i></a>
                                    <form action="{{ route('platform.clients.toggle-active', $client) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ $client->is_active ? 'Suspend' : 'Reactivate' }} this client? {{ $client->is_active ? 'This also deactivates all of their users.' : 'This reactivates all of their previously-active users.' }}');">
                                        @csrf
                                        <button type="submit" class="btn-icon" title="{{ $client->is_active ? 'Suspend' : 'Reactivate' }}">
                                            <i class="ti {{ $client->is_active ? 'ti-player-pause' : 'ti-player-play' }} font-16"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="ti ti-building-store font-24 d-block mb-2"></i>
                                    No clients yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $clients->links() }}
            </div>
        </div>
    </div>

    @include('platform.clients._copy-email-script')

@endsection
