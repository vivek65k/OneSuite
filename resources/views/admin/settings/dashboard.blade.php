@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <div class="row align-items-center mb-2">
        <div class="col-sm-8">
            <h4 class="mb-1">Welcome back, {{ auth()->user()->name }}</h4>
            <p class="text-muted mb-0">Here's what's happening across your organization today.</p>
        </div>
        <div class="col-sm-4 text-sm-end">
            <span class="badge bg-soft-primary font-13 px-3 py-2">
                {{ auth()->user()->is_super_admin ? 'Super Admin' : 'Platform User' }}
            </span>
        </div>
    </div>

    <div class="row">
        @foreach ($stats as $stat)
            <div class="col-xl-3 col-sm-6">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div>
                                <p class="text-muted mb-1 font-13">{{ $stat['label'] }}</p>
                                <h3 class="mb-0">{{ $stat['value'] }}</h3>
                            </div>
                            <div class="avatar-box avatar-md bg-soft-{{ $stat['color'] }} rounded">
                                <span class="avatar-title bg-transparent">
                                    <i class="{{ $stat['icon'] }} font-20"></i>
                                </span>
                            </div>
                        </div>
                        @if ($stat['route'])
                            <a href="{{ route($stat['route']) }}" class="font-13 fw-semibold text-decoration-none">
                                Manage <i class="ti ti-arrow-right font-11"></i>
                            </a>
                        @else
                            <span class="font-13 text-muted">Not set up yet</span>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Your Apps</h5>
                    <div class="row">
                        @foreach ($apps as $key => $app)
                            <div class="col-sm-6 col-lg-4 mb-3">
                                <a href="{{ route($app['route']) }}" class="d-block text-decoration-none dashboard-app-tile">
                                    <div class="d-flex align-items-start">
                                        <div class="avatar-box avatar-md tile-icon rounded me-2 flex-shrink-0" data-tile-color="{{ $key }}">
                                            <span class="avatar-title bg-transparent">
                                                <i class="{{ $app['icon'] }} font-20"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 text-body">
                                                {{ $app['label'] }}
                                                @unless ($app['built'])
                                                    <span class="badge bg-soft-secondary font-10 ms-1">Coming soon</span>
                                                @endunless
                                            </h6>
                                            <p class="text-muted font-12 mb-0">{{ $app['description'] }}</p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Recently Added Users</h5>
                    @forelse ($recentUsers as $user)
                        <div class="d-flex align-items-center py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                            <img src="{{ $user->avatar ? asset('storage/'.$user->avatar) : 'https://ui-avatars.com/api/?name='.urlencode($user->name) }}"
                                class="rounded-circle thumb-sm me-2" alt="{{ $user->name }}">
                            <div class="flex-grow-1">
                                <h6 class="mb-0 font-13">{{ $user->name }}</h6>
                                <p class="text-muted mb-0 font-11">{{ $user->email }}</p>
                            </div>
                            <span class="badge {{ $user->is_active ? 'bg-soft-success' : 'bg-soft-danger' }} font-11">
                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No users yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
