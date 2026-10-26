<?php

namespace Tests\Feature;

use App\Mail\UserInvitationMail;
use App\Models\Company;
use App\Models\CompanyMailAccount;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Support\Mail\CompanyMailer;
use App\Support\Mail\MailProviders;
use App\Support\Mail\Providers\GoogleProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Tests\TestCase;

class MailSetupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // User::saved() syncs this role from is_super_admin, so it must exist.
        Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    }

    protected function platformAdmin(): User
    {
        return User::create([
            'name' => 'Platform', 'email' => 'platform@example.test', 'password' => 'secret-pass',
            'is_active' => true, 'is_platform_admin' => true,
        ]);
    }

    protected function clientAdmin(string $email = 'admin@client.test'): User
    {
        $company = Company::create(['name' => 'Acme '.$email, 'is_active' => true]);
        setPermissionsTeamId($company->id);

        return User::create([
            'company_id' => $company->id, 'name' => 'Client Admin', 'email' => $email,
            'password' => 'secret-pass', 'is_active' => true, 'is_super_admin' => true,
        ]);
    }

    protected function configure(string $provider): void
    {
        MailProviders::find($provider)->saveCredentials("{$provider}-client-id", 'the-secret');
    }

    /** Starts the sign-in and returns the query string the provider would receive. */
    protected function startConnect(string $provider): array
    {
        $location = $this->post("/settings/mail-setup/{$provider}/connect")->headers->get('Location');
        parse_str(parse_url($location, PHP_URL_QUERY), $query);

        return [$location, $query];
    }

    public function test_platform_admin_saves_each_providers_credentials_with_the_secret_encrypted(): void
    {
        $this->actingAs($this->platformAdmin());

        $this->put('/platform/mail-setup/google', ['client_id' => 'g.apps.googleusercontent.com', 'client_secret' => 'the-secret'])
            ->assertRedirect('/platform/mail-setup');

        $google = MailProviders::find('google');
        $this->assertTrue($google->isConfigured());
        $this->assertSame('the-secret', $google->clientSecret());
        $this->assertStringNotContainsString('the-secret', PlatformSetting::get('mail.google.client_secret'));
        $this->assertFalse(MailProviders::find('microsoft')->isConfigured());

        // Blank secret keeps the saved one, and it is never rendered back.
        $this->put('/platform/mail-setup/google', ['client_id' => 'other.apps.googleusercontent.com', 'client_secret' => ''])
            ->assertSessionHasNoErrors();
        $this->assertSame('the-secret', $google->clientSecret());
        $this->assertSame('other.apps.googleusercontent.com', $google->clientId());

        $this->put('/platform/mail-setup/microsoft', ['client_id' => 'azure-app-id', 'client_secret' => 'azure-secret'])
            ->assertSessionHasNoErrors();
        $this->assertSame(['google', 'microsoft'], array_keys(MailProviders::configured()));

        // A missing secret is reported against that provider's own form only.
        PlatformSetting::query()->where('key', 'mail.microsoft.client_secret')->delete();
        $this->put('/platform/mail-setup/microsoft', ['client_id' => 'azure-app-id'])
            ->assertSessionHasErrorsIn('microsoft', ['client_secret']);

        $this->get('/platform/mail-setup')->assertOk()
            ->assertSee('settings/mail-setup/google/callback')
            ->assertSee('settings/mail-setup/microsoft/callback')
            ->assertDontSee('the-secret')->assertDontSee('azure-secret');

        $this->put('/platform/mail-setup/yahoo', ['client_id' => 'x', 'client_secret' => 'y'])->assertNotFound();
    }

    public function test_a_client_cannot_reach_the_platform_mail_setup(): void
    {
        $this->actingAs($this->clientAdmin())->get('/platform/mail-setup')->assertForbidden();
        $this->put('/platform/mail-setup/google', ['client_id' => 'x', 'client_secret' => 'y'])->assertForbidden();
    }

    public function test_client_only_sees_connect_buttons_for_configured_providers(): void
    {
        $this->actingAs($this->clientAdmin());

        $this->get('/settings/mail-setup')->assertOk()
            ->assertDontSee('Connect Google account')->assertDontSee('Connect Microsoft account')
            ->assertSee('Connect with SMTP');
        $this->post('/settings/mail-setup/google/connect')->assertRedirect('/settings/mail-setup')->assertSessionHas('error');

        $this->configure('microsoft');
        $this->get('/settings/mail-setup')->assertOk()
            ->assertSee('Connect Microsoft account')->assertDontSee('Connect Google account');
    }

    public function test_client_connects_a_google_account_and_mail_is_sent_through_gmail(): void
    {
        $this->configure('google');
        $admin = $this->clientAdmin();
        $this->actingAs($admin);

        [$location, $query] = $this->startConnect('google');

        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/v2/auth?', $location);
        $this->assertSame('google-client-id', $query['client_id']);
        $this->assertSame('offline', $query['access_type']);
        $this->assertStringContainsString(GoogleProvider::SCOPE_SEND, $query['scope']);
        $this->assertStringEndsWith('/settings/mail-setup/google/callback', $query['redirect_uri']);

        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'access-1', 'refresh_token' => 'refresh-1', 'expires_in' => 3600,
                'scope' => 'openid https://www.googleapis.com/auth/userinfo.email '.GoogleProvider::SCOPE_SEND,
            ]),
            'openidconnect.googleapis.com/*' => Http::response(['email' => 'hr@acme.test']),
            'gmail.googleapis.com/*' => Http::response(['id' => 'msg-1']),
        ]);

        $this->get('/settings/mail-setup/google/callback?code=abc&state='.$query['state'])
            ->assertRedirect('/settings/mail-setup')->assertSessionHas('status');

        $account = CompanyMailAccount::sole();
        $this->assertSame($admin->company_id, $account->company_id);
        $this->assertSame('google', $account->provider);
        $this->assertSame('hr@acme.test', $account->email);
        $this->assertSame('refresh-1', $account->refresh_token);
        $this->assertNotSame('refresh-1', $account->getRawOriginal('refresh_token'));

        $this->get('/settings/mail-setup')->assertOk()->assertSee('hr@acme.test')->assertSee('Send test email');

        $this->post('/settings/mail-setup/test')->assertSessionHas('status');

        Http::assertSent(function (Request $request) {
            if (! str_contains($request->url(), 'gmail.googleapis.com')) {
                return false;
            }

            $raw = base64_decode(strtr($request['raw'], '-_', '+/'));

            return $request->hasHeader('Authorization', 'Bearer access-1')
                && str_contains($raw, 'To: admin@client.test')
                && str_contains($raw, 'hr@acme.test');
        });

        // A real mailable goes the same way.
        CompanyMailer::for($admin->company->fresh())->to('new@client.test')->send(new UserInvitationMail($admin, 'https://example.test/accept'));
        Http::assertSentCount(4);
    }

    public function test_client_connects_a_microsoft_account_and_mail_is_sent_through_graph(): void
    {
        $this->configure('microsoft');
        $admin = $this->clientAdmin();
        $this->actingAs($admin);

        [$location, $query] = $this->startConnect('microsoft');

        $this->assertStringStartsWith('https://login.microsoftonline.com/common/oauth2/v2.0/authorize?', $location);
        $this->assertSame('microsoft-client-id', $query['client_id']);
        $this->assertStringContainsString('offline_access', $query['scope']);
        $this->assertStringContainsString('Mail.Send', $query['scope']);
        $this->assertStringEndsWith('/settings/mail-setup/microsoft/callback', $query['redirect_uri']);

        Http::fake([
            'login.microsoftonline.com/*' => Http::sequence()
                ->push(['access_token' => 'ms-access-1', 'refresh_token' => 'ms-refresh-1', 'expires_in' => 3600,
                    'scope' => 'openid email https://graph.microsoft.com/User.Read https://graph.microsoft.com/Mail.Send'])
                ->push(['access_token' => 'ms-access-2', 'refresh_token' => 'ms-refresh-2', 'expires_in' => 3600]),
            'graph.microsoft.com/v1.0/me?*' => Http::response(['mail' => null, 'userPrincipalName' => 'hr@contoso.test']),
            'graph.microsoft.com/v1.0/me/sendMail' => Http::response('', 202),
        ]);

        $this->get('/settings/mail-setup/microsoft/callback?code=abc&state='.$query['state'])
            ->assertRedirect('/settings/mail-setup')->assertSessionHas('status');

        $account = CompanyMailAccount::sole();
        $this->assertSame('microsoft', $account->provider);
        $this->assertSame('hr@contoso.test', $account->email);

        $this->post('/settings/mail-setup/test')->assertSessionHas('status');

        Http::assertSent(function (Request $request) {
            if (! str_ends_with($request->url(), '/me/sendMail')) {
                return false;
            }

            $raw = base64_decode($request->body());

            return $request->hasHeader('Authorization', 'Bearer ms-access-1')
                && $request->hasHeader('Content-Type', 'text/plain')
                && str_contains($raw, 'To: admin@client.test')
                && str_contains($raw, 'hr@contoso.test');
        });

        // Microsoft hands out a new refresh token on every refresh - the old one stops working.
        $account->update(['token_expires_at' => now()->subMinute()]);
        $this->assertSame('ms-access-2', MailProviders::find('microsoft')->accessTokenFor($account));
        $this->assertSame('ms-refresh-2', $account->fresh()->refresh_token);
    }

    public function test_a_callback_for_a_different_provider_than_was_started_connects_nothing(): void
    {
        $this->configure('google');
        $this->configure('microsoft');
        $this->actingAs($this->clientAdmin());
        Http::fake();

        [, $query] = $this->startConnect('google');
        $this->get('/settings/mail-setup/microsoft/callback?code=abc&state='.$query['state'])->assertSessionHas('error');

        $this->startConnect('google');
        $this->get('/settings/mail-setup/google/callback?code=abc&state=forged')->assertSessionHas('error');

        $this->assertSame(0, CompanyMailAccount::count());
        Http::assertNothingSent();
    }

    public function test_declining_the_send_permission_connects_nothing(): void
    {
        $this->configure('google');
        $this->actingAs($this->clientAdmin());

        [, $query] = $this->startConnect('google');

        Http::fake(['oauth2.googleapis.com/token' => Http::response([
            'access_token' => 'a', 'refresh_token' => 'r', 'expires_in' => 3600, 'scope' => 'openid email',
        ])]);

        $this->get('/settings/mail-setup/google/callback?code=abc&state='.$query['state'])->assertSessionHas('error');
        $this->assertSame(0, CompanyMailAccount::count());
    }

    public function test_expired_token_is_refreshed_and_a_revoked_one_is_flagged(): void
    {
        $this->configure('google');
        $google = MailProviders::find('google');
        $admin = $this->clientAdmin();
        $account = CompanyMailAccount::create([
            'company_id' => $admin->company_id, 'provider' => 'google', 'email' => 'hr@acme.test',
            'access_token' => 'old', 'refresh_token' => 'refresh-1', 'token_expires_at' => now()->subMinute(),
        ]);

        Http::fake(['oauth2.googleapis.com/token' => Http::sequence()
            ->push(['access_token' => 'new', 'expires_in' => 3600])
            ->push(['error' => 'invalid_grant', 'error_description' => 'Token has been expired or revoked.'], 400)]);

        $this->assertSame('new', $google->accessTokenFor($account));
        $this->assertSame('new', $google->accessTokenFor($account));
        $this->assertSame('refresh-1', $account->fresh()->refresh_token);

        $account->update(['token_expires_at' => now()->subMinute()]);

        try {
            $google->accessTokenFor($account);
            $this->fail('Expected the revoked token to throw.');
        } catch (\RuntimeException) {
            $this->assertTrue($account->fresh()->needs_reconnect);
            $this->assertFalse(CompanyMailer::usable($account->fresh()));
        }
    }

    public function test_smtp_is_not_saved_when_the_server_rejects_the_login(): void
    {
        $this->actingAs($this->clientAdmin());

        $this->put('/settings/mail-setup/smtp', ['email' => 'hr@acme.test', 'host' => 'bad host!', 'port' => 587, 'username' => 'hr', 'password' => 'pw'])
            ->assertSessionHasErrorsIn('smtp', ['host']);
        $this->put('/settings/mail-setup/smtp', ['email' => 'hr@acme.test', 'host' => 'smtp.acme.test', 'port' => 22, 'username' => 'hr', 'password' => 'pw'])
            ->assertSessionHasErrorsIn('smtp', ['port']);

        // Nothing listens here, so the sign-in check fails.
        $this->put('/settings/mail-setup/smtp', ['email' => 'hr@acme.test', 'host' => '127.0.0.1', 'port' => 2525, 'username' => 'hr', 'password' => 'pw'])
            ->assertSessionHasErrorsIn('smtp', ['host']);

        $this->assertSame(0, CompanyMailAccount::count());
    }

    public function test_an_smtp_account_sends_through_its_own_server(): void
    {
        $admin = $this->clientAdmin();
        $account = CompanyMailAccount::create([
            'company_id' => $admin->company_id, 'provider' => 'smtp', 'email' => 'hr@acme.test',
            'settings' => ['host' => 'smtp.acme.test', 'port' => 465, 'username' => 'hr@acme.test', 'password' => 'zq-mailbox-pw'],
        ]);

        $this->assertStringNotContainsString('zq-mailbox-pw', $account->getRawOriginal('settings'));
        $this->assertTrue(CompanyMailer::usable($account));

        $transport = CompanyMailer::for($admin->company)->getSymfonyTransport();
        $this->assertInstanceOf(EsmtpTransport::class, $transport);
        $this->assertSame('smtps://smtp.acme.test', (string) $transport);
        $this->assertSame('hr@acme.test', $transport->getUsername());

        $this->actingAs($admin)->get('/settings/mail-setup')->assertOk()
            ->assertSee('hr@acme.test')->assertSee('smtp.acme.test')->assertDontSee('zq-mailbox-pw');
    }

    public function test_one_client_never_sees_or_disconnects_anothers_account(): void
    {
        $this->configure('google');
        $other = $this->clientAdmin('other@client.test');
        CompanyMailAccount::create([
            'company_id' => $other->company_id, 'provider' => 'google', 'email' => 'secret@other.test',
            'access_token' => 'a', 'refresh_token' => 'r', 'token_expires_at' => now()->addHour(),
        ]);
        Http::fake();

        $this->actingAs($this->clientAdmin());
        $this->get('/settings/mail-setup')->assertOk()->assertDontSee('secret@other.test');
        $this->delete('/settings/mail-setup')->assertRedirect('/settings/mail-setup');

        $this->assertSame(1, CompanyMailAccount::count());
    }
}
