<?php

namespace App\Support\Mail\Providers;

use App\Models\CompanyMailAccount;
use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * A mail provider clients connect to by signing in (OAuth2) rather than by
 * typing a host, port and password. The platform admin registers ONE OAuth
 * app per provider (client id + secret, /platform/mail-setup); each client
 * then signs in with their own account against it and we keep only the
 * resulting tokens.
 *
 * Adding a provider = one subclass + one line in MailProviders::all().
 */
abstract class OAuthMailProvider
{
    /** URL-safe id, also stored in company_mail_accounts.provider. */
    abstract public function key(): string;

    abstract public function label(): string;

    abstract public function icon(): string;

    abstract protected function authorizeEndpoint(): string;

    abstract protected function tokenEndpoint(): string;

    /** @return list<string> */
    abstract protected function scopes(): array;

    /** The one scope the connection is useless without. */
    abstract protected function sendScope(): string;

    /** Provider-specific extras for the sign-in URL. */
    abstract protected function authorizeParams(): array;

    abstract public function emailFor(string $accessToken): string;

    /** Send one fully rendered MIME message as the signed-in account. */
    abstract public function send(string $accessToken, string $rawMime): void;

    /**
     * Best effort - the client can also remove access from their own
     * account, so a failure here must not block disconnecting.
     */
    public function revoke(CompanyMailAccount $account): void
    {
        //
    }

    public function clientId(): ?string
    {
        return PlatformSetting::get("mail.{$this->key()}.client_id");
    }

    public function clientSecret(): ?string
    {
        return PlatformSetting::getSecret("mail.{$this->key()}.client_secret");
    }

    public function saveCredentials(string $clientId, ?string $clientSecret): void
    {
        PlatformSetting::set("mail.{$this->key()}.client_id", $clientId);

        if (filled($clientSecret)) {
            PlatformSetting::setSecret("mail.{$this->key()}.client_secret", $clientSecret);
        }
    }

    public function isConfigured(): bool
    {
        return filled($this->clientId()) && filled($this->clientSecret());
    }

    /**
     * Must match a redirect URI registered on the provider's OAuth app
     * exactly - it's the same URL for every client, so the platform admin
     * registers it once.
     */
    public function redirectUri(): string
    {
        return route('admin.mail-setup.callback', ['provider' => $this->key()]);
    }

    public function authUrl(string $state, ?string $loginHint = null): string
    {
        return $this->authorizeEndpoint().'?'.http_build_query(array_filter([
            'client_id' => $this->clientId(),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => implode(' ', $this->scopes()),
            'state' => $state,
            'login_hint' => $loginHint,
            ...$this->authorizeParams(),
        ]));
    }

    /**
     * @return array{access_token: string, refresh_token: string, expires_in?: int}
     */
    public function exchangeCode(string $code): array
    {
        $response = Http::asForm()->timeout(20)->post($this->tokenEndpoint(), [
            'code' => $code,
            'client_id' => $this->clientId(),
            'client_secret' => $this->clientSecret(),
            'redirect_uri' => $this->redirectUri(),
            'grant_type' => 'authorization_code',
        ]);

        if ($response->failed()) {
            throw new RuntimeException("{$this->label()} rejected the sign-in: ".self::errorFrom($response->json()));
        }

        $tokens = $response->json();

        // The user can decline individual permissions on the consent
        // screen - without this one the connection is useless.
        if (stripos($tokens['scope'] ?? '', $this->sendScope()) === false) {
            throw new RuntimeException('Permission to send email was not granted. Please connect again and allow sending email on your behalf.');
        }

        if (empty($tokens['refresh_token'])) {
            throw new RuntimeException("{$this->label()} did not return a long-lived token. Please connect again.");
        }

        return $tokens;
    }

    /**
     * A currently valid access token for this mailbox, refreshed first if
     * the stored one has (nearly) expired - they only last about an hour.
     */
    public function accessTokenFor(CompanyMailAccount $account): string
    {
        if ($account->token_expires_at?->isAfter(now()->addMinute())) {
            return $account->access_token;
        }

        $response = Http::asForm()->timeout(20)->post($this->tokenEndpoint(), [
            'client_id' => $this->clientId(),
            'client_secret' => $this->clientSecret(),
            'refresh_token' => $account->refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        if ($response->failed()) {
            // invalid_grant = the client revoked access (or changed their
            // password); only signing in again fixes that.
            if ($response->json('error') === 'invalid_grant') {
                $account->update(['needs_reconnect' => true]);
            }

            throw new RuntimeException("{$this->label()} no longer accepts the connection for {$account->email}: ".self::errorFrom($response->json()));
        }

        $account->update([
            'access_token' => $response->json('access_token'),
            // Microsoft rotates the refresh token on every use; Google keeps the same one.
            'refresh_token' => $response->json('refresh_token') ?: $account->refresh_token,
            'token_expires_at' => now()->addSeconds((int) $response->json('expires_in', 3600)),
            'needs_reconnect' => false,
        ]);

        return $account->access_token;
    }

    public static function errorFrom(?array $body): string
    {
        $error = $body['error'] ?? null;

        return (string) ($body['error_description']
            ?? (is_array($error) ? ($error['message'] ?? null) : $error)
            ?? 'unknown error');
    }
}
