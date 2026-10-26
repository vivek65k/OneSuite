@extends('layouts.admin')

@section('title', $appLabel.' Notifications')

@section('content')
    <x-page-title title="{{ $appLabel }} Notifications" />

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h4 class="card-title mb-0">All Notifications</h4>
            @if ($notifications->contains(fn ($n) => $n->read_at === null))
                <form method="POST" action="{{ route('admin.notifications.mark-all-read') }}">
                    @csrf
                    <input type="hidden" name="app" value="{{ \App\Support\AppLauncher::currentKey() }}">
                    <button type="submit" class="btn btn-light btn-sm">Mark all read</button>
                </form>
            @endif
        </div>
        <div class="card-body p-0">
            @forelse ($notifications as $notification)
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
            @empty
                <p class="text-muted text-center py-5 mb-0">
                    <i class="ti ti-bell-off font-24 d-block mb-2"></i>
                    No notifications yet.
                </p>
            @endforelse
        </div>
        @if ($notifications->hasPages())
            <div class="card-body">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
@endsection
