@extends('layouts.admin')

@section('title', 'All Notifications')

@section('content')
    <x-page-title title="All Notifications" />

    @if ($grouped->isEmpty())
        <div class="card">
            <div class="card-body">
                <p class="text-muted text-center py-5 mb-0">
                    <i class="ti ti-bell-off font-24 d-block mb-2"></i>
                    No notifications yet, across any app.
                </p>
            </div>
        </div>
    @endif

    @foreach ($apps as $key => $app)
        @continue(! isset($grouped[$key]))
        <div class="card">
            <div class="card-header d-flex align-items-center gap-2">
                <span class="avatar-box avatar-sm tile-icon rounded flex-shrink-0" data-tile-color="{{ $key }}">
                    <span class="avatar-title bg-transparent"><i class="{{ $app['icon'] }} font-16"></i></span>
                </span>
                <h4 class="card-title mb-0">{{ $app['label'] }}</h4>
                <span class="badge badge-soft-secondary ms-auto">{{ $grouped[$key]->count() }}</span>
            </div>
            <div class="card-body p-0">
                @foreach ($grouped[$key] as $notification)
                    <form method="POST" action="{{ route('admin.notifications.read', $notification->id) }}" class="notification-item-form">
                        @csrf
                        <button type="submit" class="notification-item {{ $notification->read_at ? '' : 'is-unread' }}">
                            <span class="notification-item-icon"><i class="{{ $notification->data['icon'] ?? 'ti ti-bell' }}"></i></span>
                            <span class="notification-item-body">
                                <span class="d-block notification-item-title">{{ $notification->data['title'] ?? 'Notification' }}</span>
                                <span class="d-block notification-item-message">{{ $notification->data['message'] ?? '' }}</span>
                                <span class="d-block notification-item-time">{{ $notification->created_at->diffForHumans() }} &middot; {{ $notification->created_at->format('M j, Y g:ia') }}</span>
                            </span>
                        </button>
                    </form>
                @endforeach
            </div>
        </div>
    @endforeach
@endsection
