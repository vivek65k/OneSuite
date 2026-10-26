<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AppLauncher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * The current app's own notifications, e.g. /hrm/notifications -
     * registered once inside every app's own route group so
     * AppLauncher::currentKey() resolves correctly for whichever app
     * this request is tagged with.
     */
    public function index(): View
    {
        $app = AppLauncher::currentKey();

        $notifications = Auth::user()->notifications()
            ->where('data->app', $app)
            ->paginate(25);

        return view('admin.notifications.index', [
            'notifications' => $notifications,
            'appLabel' => AppLauncher::apps()[$app]['label'] ?? ucfirst($app),
        ]);
    }

    /**
     * Every app's notifications in one place, grouped by app - only a
     * super admin manages across every app, so this cross-app view (the
     * bare /notifications URL, not tied to any single app's route group)
     * is restricted to them.
     */
    public function allApps(): View
    {
        abort_unless(Auth::user()->is_super_admin, 403);

        $grouped = Auth::user()->notifications()->latest()->take(200)->get()
            ->groupBy(fn ($n) => $n->data['app'] ?? 'other');

        $apps = AppLauncher::apps();

        return view('admin.notifications.all-apps', [
            'grouped' => $grouped,
            'apps' => $apps,
        ]);
    }

    public function markRead(string $notification): RedirectResponse
    {
        $record = Auth::user()->notifications()->findOrFail($notification);
        $record->markAsRead();

        $url = $record->data['url'] ?? null;

        return $url ? redirect()->to($url) : back();
    }

    /**
     * With an "app" field (every per-app bell always sends one), only
     * that app's unread notifications are marked read - otherwise
     * clicking "mark all read" while working in HRM would silently
     * clear unrelated alerts from every other app too.
     */
    public function markAllRead(Request $request): RedirectResponse
    {
        $app = $request->input('app');
        $notifications = Auth::user()->unreadNotifications;

        if ($app) {
            $notifications = $notifications->filter(fn ($n) => ($n->data['app'] ?? null) === $app);
        }

        $notifications->markAsRead();

        return back()->with('status', 'Notifications marked as read.');
    }
}
