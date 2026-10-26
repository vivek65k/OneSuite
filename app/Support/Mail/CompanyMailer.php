<?php

namespace App\Support\Mail;

use App\Models\Company;
use App\Models\CompanyMailAccount;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Support\Facades\Mail;

/**
 * The mailer to use for mail sent on behalf of one client: their connected
 * account (Google / Microsoft sign-in, or their own SMTP server) when they
 * have one, otherwise the application default (.env), so nothing stops
 * working for a client that hasn't connected yet.
 *
 *     CompanyMailer::for(Company::current())->to($user)->send(new SomeMail());
 */
class CompanyMailer
{
    public static function for(Company $company): Mailer
    {
        $account = $company->mailAccount;

        if (! $account || ! self::usable($account)) {
            return Mail::mailer();
        }

        $mailer = $account->provider === MailProviders::SMTP
            ? Mail::build(self::smtpConfig($account->settings ?? []))
            : Mail::build(['transport' => 'oauth-mail', 'account' => $account]);

        // Providers reject or rewrite any other From, so it is always the connected address.
        $mailer->alwaysFrom($account->email, $company->name);

        return $mailer;
    }

    /**
     * Whether mail would really go out through this account right now,
     * rather than quietly falling back to the default mailer.
     */
    public static function usable(CompanyMailAccount $account): bool
    {
        if ($account->provider === MailProviders::SMTP) {
            return filled($account->settings['host'] ?? null);
        }

        return ! $account->needs_reconnect && (MailProviders::find($account->provider)?->isConfigured() ?? false);
    }

    public static function smtpConfig(array $settings): array
    {
        $port = (int) ($settings['port'] ?? 587);

        return [
            'transport' => 'smtp',
            // 465 is TLS from the first byte; every other port upgrades with STARTTLS when offered.
            'scheme' => $port === 465 ? 'smtps' : 'smtp',
            'host' => $settings['host'] ?? null,
            'port' => $port,
            'username' => $settings['username'] ?? null,
            'password' => $settings['password'] ?? null,
            'timeout' => 15,
        ];
    }
}
