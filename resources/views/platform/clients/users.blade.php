@extends('layouts.platform')

@section('title', $client->name.' - Users')

@section('content')
    <x-page-title :title="$client->name.' - Users'" :breadcrumbs="['Clients' => route('platform.clients.index')]" />

    @include('platform.clients._invite-alert')

    <div class="client-users-summary">
        <div class="client-users-stat">
            <span class="client-users-stat-icon bg-soft-primary text-primary"><i class="ti ti-users"></i></span>
            <div>
                <div class="client-users-stat-num">{{ $totals['all'] }}</div>
                <div class="client-users-stat-label">Total users</div>
            </div>
        </div>
        <div class="client-users-stat">
            <span class="client-users-stat-icon bg-soft-success text-success"><i class="ti ti-user-check"></i></span>
            <div>
                <div class="client-users-stat-num">{{ $totals['active'] }}</div>
                <div class="client-users-stat-label">Active</div>
            </div>
        </div>
        <div class="client-users-stat">
            <span class="client-users-stat-icon bg-soft-warning text-warning"><i class="ti ti-mail"></i></span>
            <div>
                <div class="client-users-stat-num">{{ $totals['pending'] }}</div>
                <div class="client-users-stat-label">Invite not accepted</div>
            </div>
        </div>
        <div class="client-users-stat">
            <span class="client-users-stat-icon {{ $client->is_active ? 'bg-soft-success text-success' : 'bg-soft-danger text-danger' }}"><i class="ti ti-building"></i></span>
            <div>
                <div class="client-users-stat-num font-15">{{ $client->is_active ? 'Active' : 'Suspended' }}</div>
                <div class="client-users-stat-label">Client status</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <form method="GET" action="{{ route('platform.clients.users', $client) }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent"><i class="ti ti-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control" value="{{ $search }}" placeholder="Search name or email...">
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All statuses</option>
                        <option value="active" @selected($status === 'active')>Active</option>
                        <option value="inactive" @selected($status === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-de-primary text-light btn-sm"><i class="ti ti-filter font-14 me-1"></i> Filter</button>
                    <a href="{{ route('platform.clients.users', $client) }}" class="btn btn-light btn-sm"><i class="ti ti-x font-14 me-1"></i> Reset</a>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive table-responsive-sticky">
                <table class="table table-striped table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Apps</th>
                            <th>Branches</th>
                            <th>Status</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td>
                                    <div class="client-admin">
                                        <span class="client-admin-avatar">{{ Str::upper(Str::substr($user->name, 0, 1)) }}</span>
                                        <div class="client-admin-meta">
                                            <div class="client-admin-email">
                                                <span>{{ $user->email }}</span>
                                                <button type="button" class="client-admin-copy" data-copy="{{ $user->email }}" title="Copy login email"><i class="ti ti-copy"></i></button>
                                            </div>
                                            <div class="text-muted font-12">
                                                {{ $user->name }}
                                                @if ($user->designation)
                                                    &middot; {{ $user->designation }}
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if ($user->is_super_admin)
                                        <span class="badge badge-soft-primary"><i class="ti ti-crown me-1"></i>Super admin</span>
                                    @endif
                                    @foreach ($user->roles as $role)
                                        @continue($user->is_super_admin && $role->name === 'super_admin')
                                        <span class="badge badge-soft-secondary me-1">{{ Str::headline($role->name) }}</span>
                                    @endforeach
                                    @if (! $user->is_super_admin && $user->roles->isEmpty())
                                        <span class="text-muted font-12">No role</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($user->is_super_admin || $user->app_access === null)
                                        <span class="badge badge-soft-primary">All client apps</span>
                                    @else
                                        @foreach ($user->app_access as $key)
                                            <span class="badge badge-soft-secondary me-1">{{ $apps[$key]['label'] ?? $key }}</span>
                                        @endforeach
                                    @endif
                                </td>
                                <td>
                                    {{-- Mirrors App\Support\BranchContext: with no assignment a
                                         super admin picks among every branch, and anyone else is held
                                         to their employee's branch, else the default branch. --}}
                                    @forelse ($user->branches as $branch)
                                        <span class="badge badge-soft-secondary me-1">{{ $branch->name }}</span>
                                    @empty
                                        @if ($user->is_super_admin)
                                            <span class="badge badge-soft-primary">All branches</span>
                                        @elseif ($fallbackBranch = \App\Support\BranchContext::fallbackBranchFor($user))
                                            <span class="badge badge-soft-secondary" title="No branch assigned - limited to their employee record's branch, else the company's default branch">{{ $fallbackBranch->name }}</span>
                                            <span class="text-muted font-11">auto</span>
                                        @else
                                            <span class="text-muted font-12">&ndash;</span>
                                        @endif
                                    @endforelse
                                </td>
                                <td>
                                    <span class="badge {{ $user->is_active ? 'badge-soft-success' : 'badge-soft-danger' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span>
                                    {{-- invitation_accepted_at is set by the invite flow; seeded /
                                         pre-invite accounts only have email_verified_at. --}}
                                    @if (! $user->invitation_accepted_at && ! $user->email_verified_at)
                                        <div class="mt-1">
                                            <span class="badge badge-soft-warning">Invite not accepted</span>
                                            <form action="{{ route('platform.clients.resend-invite', [$client, $user]) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-link btn-sm p-0 ms-1 font-12 align-baseline">Resend invite</button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                                <td class="text-nowrap">{{ $user->created_at?->format('M j, Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="ti ti-users font-24 d-block mb-2"></i>
                                    {{ $search || $status ? 'No users match these filters.' : 'This client has no users yet.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $users->links() }}
            </div>
        </div>
    </div>

    @include('platform.clients._copy-email-script')
@endsection
