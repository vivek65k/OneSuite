<?php

namespace App\Models;

use App\Support\Mail\MailProviders;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The mailbox a client connected in Settings > Mail Setup - by signing in
 * with Google or Microsoft, or with their own SMTP server. One per client;
 * every mail that client's users trigger is sent from it.
 */
class CompanyMailAccount extends Model
{
    protected $fillable = [
        'company_id', 'provider', 'email', 'access_token', 'refresh_token',
        'token_expires_at', 'settings', 'connected_by', 'needs_reconnect',
    ];

    protected $hidden = ['access_token', 'refresh_token', 'settings'];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            // SMTP only: host, port, username, password.
            'settings' => 'encrypted:array',
            'needs_reconnect' => 'boolean',
        ];
    }

    public function isSmtp(): bool
    {
        return $this->provider === MailProviders::SMTP;
    }

    public function providerLabel(): string
    {
        return $this->isSmtp() ? 'SMTP' : (MailProviders::find($this->provider)?->label() ?? ucfirst($this->provider));
    }

    public function providerIcon(): string
    {
        return $this->isSmtp() ? 'ti ti-server' : (MailProviders::find($this->provider)?->icon() ?? 'ti ti-mail');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function connectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'connected_by');
    }
}
