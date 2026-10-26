<?php

namespace App\Support\Mail\Providers;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Microsoft 365 / Outlook mailboxes, signed in through an Azure (Entra ID)
 * app registration and sent through Microsoft Graph.
 *
 * Uses the "common" endpoint: every client is a different organisation, so
 * the app registration must be multi-tenant.
 */
class MicrosoftProvider extends OAuthMailProvider
{
    public function key(): string
    {
        return 'microsoft';
    }

    public function label(): string
    {
        return 'Microsoft';
    }

    public function icon(): string
    {
        return 'ti ti-brand-windows';
    }

    protected function authorizeEndpoint(): string
    {
        return 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize';
    }

    protected function tokenEndpoint(): string
    {
        return 'https://login.microsoftonline.com/common/oauth2/v2.0/token';
    }

    protected function scopes(): array
    {
        // offline_access is what makes Microsoft return a refresh token.
        return ['offline_access', 'openid', 'email', 'https://graph.microsoft.com/User.Read', 'https://graph.microsoft.com/Mail.Send'];
    }

    protected function sendScope(): string
    {
        return 'Mail.Send';
    }

    protected function authorizeParams(): array
    {
        return ['response_mode' => 'query', 'prompt' => 'select_account'];
    }

    public function emailFor(string $accessToken): string
    {
        $response = Http::withToken($accessToken)->timeout(20)->get('https://graph.microsoft.com/v1.0/me', [
            '$select' => 'mail,userPrincipalName',
        ]);

        // "mail" is empty for some personal/unlicensed accounts; the sign-in name is the address then.
        $email = $response->json('mail') ?: $response->json('userPrincipalName');

        if ($response->failed() || empty($email)) {
            throw new RuntimeException('Could not read the email address of the Microsoft account.');
        }

        return $email;
    }

    public function send(string $accessToken, string $rawMime): void
    {
        // Graph accepts a whole MIME message when it is base64 in a text/plain body.
        $response = Http::withToken($accessToken)->timeout(30)
            ->withBody(base64_encode($rawMime), 'text/plain')
            ->post('https://graph.microsoft.com/v1.0/me/sendMail');

        if ($response->failed()) {
            throw new RuntimeException('Microsoft could not send the email: '.self::errorFrom($response->json()));
        }
    }
}
