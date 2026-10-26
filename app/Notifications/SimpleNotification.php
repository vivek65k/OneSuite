<?php

namespace App\Notifications;

use App\Support\AppLauncher;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * One flexible, database-only notification for every in-app alert this
 * app sends (leave requests, approvals, etc.) instead of a near-identical
 * subclass per trigger - each call site just supplies its own title,
 * message, optional link, and icon. Tagged with the app it came from
 * (defaulting to whichever app-tagged route is currently handling the
 * request) so the header bell can show only the current app's alerts,
 * and the full notifications list can label each one by its app.
 */
class SimpleNotification extends Notification
{
    use Queueable;

    protected string $app;

    public function __construct(
        protected string $title,
        protected string $message,
        protected ?string $url = null,
        protected string $icon = 'ti ti-bell',
        ?string $app = null,
    ) {
        $this->app = $app ?? AppLauncher::currentKey();
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'icon' => $this->icon,
            'app' => $this->app,
        ];
    }
}
