<?php

namespace App\Support\Mail\Providers;

use App\Models\CompanyMailAccount;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Gmail and Google Workspace mailboxes, sent through the Gmail API.
 */
class GoogleProvider extends OAuthMailProvider
{
    // Send-only: we can never read a client's mailbox with this.
    public const SCOPE_SEND = 'https://www.googleapis.com/auth/gmail.send';

    public function key(): string
    {
        return 'google';
    }

    public function label(): string
    {
        return 'Google';
    }

    public function icon(): string
    {
        return 'ti ti-brand-google';
    }

    protected function authorizeEndpoint(): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth';
    }

    protected function tokenEndpoint(): string
    {
        return 'https://oauth2.googleapis.com/token';
    }

    protected function scopes(): array
    {
        return ['openid', 'email', self::SCOPE_SEND];
    }

    protected function sendScope(): string
    {
        return self::SCOPE_SEND;
    }

    protected function authorizeParams(): array
    {
        // offline + consent: Google only returns a refresh token when
        // consent is shown, and without one sending stops after an hour.
        return ['access_type' => 'offline', 'prompt' => 'consent'];
    }

    public function emailFor(string $accessToken): string
    {
        $response = Http::withToken($accessToken)->timeout(20)->get('https://openidconnect.googleapis.com/v1/userinfo');

        if ($response->failed() || empty($response->json('email'))) {
            throw new RuntimeException('Could not read the email address of the Google account.');
        }

        return $response->json('email');
    }

    public function send(string $accessToken, string $rawMime): void
    {
        $response = Http::withToken($accessToken)->timeout(30)
            ->post('https://gmail.googleapis.com/gmail/v1/users/me/messages/send', [
                'raw' => rtrim(strtr(base64_encode($rawMime), '+/', '-_'), '='),
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Gmail could not send the email: '.self::errorFrom($response->json()));
        }
    }

    public function revoke(CompanyMailAccount $account): void
    {
        try {
            Http::asForm()->timeout(10)->post('https://oauth2.googleapis.com/revoke', [
                'token' => $account->refresh_token,
            ]);
        } catch (\Throwable) {
            //
        }
    }
}
