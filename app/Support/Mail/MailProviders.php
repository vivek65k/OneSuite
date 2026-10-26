<?php

namespace App\Support\Mail;

use App\Support\Mail\Providers\GoogleProvider;
use App\Support\Mail\Providers\MicrosoftProvider;
use App\Support\Mail\Providers\OAuthMailProvider;

/**
 * Every sign-in (OAuth2) mail provider the platform supports. Anything not
 * listed here is covered by the plain SMTP option (provider "smtp").
 */
class MailProviders
{
    public const SMTP = 'smtp';

    /**
     * @return array<string, OAuthMailProvider>
     */
    public static function all(): array
    {
        return [
            'google' => new GoogleProvider(),
            'microsoft' => new MicrosoftProvider(),
        ];
    }

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function find(?string $key): ?OAuthMailProvider
    {
        return self::all()[$key] ?? null;
    }

    /**
     * Only the providers the platform admin has entered credentials for -
     * the ones a client can actually connect to.
     *
     * @return array<string, OAuthMailProvider>
     */
    public static function configured(): array
    {
        return array_filter(self::all(), fn (OAuthMailProvider $provider) => $provider->isConfigured());
    }
}
