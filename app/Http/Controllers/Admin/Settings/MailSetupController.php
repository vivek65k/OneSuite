<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Support\Mail\CompanyMailer;
use App\Support\Mail\MailProviders;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * A client's own mailbox. Normally connected by signing in with Google or
 * Microsoft (OAuth2) against the app the platform admin registered, so
 * there is no host, port, username or password to enter. SMTP is the
 * fallback for providers that have no sign-in option.
 */
class MailSetupController extends Controller
{
    public function show(): View
    {
        $this->authorizePage();

        return view('admin.settings.organization.mail-setup.show', [
            'company' => Company::current(),
            'account' => Company::current()->mailAccount()->with('connectedBy')->first(),
            'providers' => MailProviders::configured(),
        ]);
    }

    public function connect(Request $request, string $provider): RedirectResponse
    {
        $this->authorizePage();

        $provider = MailProviders::find($provider);

        if (! $provider->isConfigured()) {
            return redirect()->route('admin.mail-setup.show')->with('error', "{$provider->label()} sign-in for mail is not available yet. Please contact support.");
        }

        // Ties the provider's redirect back to this browser session - without
        // it someone could trick an admin into connecting an attacker's mailbox.
        $state = Str::random(40);
        $request->session()->put('mail_setup_state', $provider->key().'|'.$state);

        $current = Company::current()->mailAccount;

        return redirect()->away($provider->authUrl($state, $current?->provider === $provider->key() ? $current->email : null));
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        $this->authorizePage();

        $provider = MailProviders::find($provider);
        $expected = $request->session()->pull('mail_setup_state');

        if (! $expected || ! hash_equals($expected, $provider->key().'|'.$request->query('state'))) {
            return redirect()->route('admin.mail-setup.show')->with('error', 'That sign-in could not be verified. Please try connecting again.');
        }

        if ($request->query('error') || ! $request->query('code')) {
            return redirect()->route('admin.mail-setup.show')->with('error', "{$provider->label()} sign-in was cancelled - nothing was changed.");
        }

        try {
            $tokens = $provider->exchangeCode($request->query('code'));
            $email = $provider->emailFor($tokens['access_token']);
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('admin.mail-setup.show')->with('error', $e instanceof \RuntimeException ? $e->getMessage() : "Could not reach {$provider->label()}. Please try again.");
        }

        Company::current()->mailAccount()->updateOrCreate([], [
            'provider' => $provider->key(),
            'email' => $email,
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'token_expires_at' => now()->addSeconds((int) ($tokens['expires_in'] ?? 3600)),
            'settings' => null,
            'connected_by' => $request->user()->id,
            'needs_reconnect' => false,
        ]);

        return redirect()->route('admin.mail-setup.show')->with('status', "Connected. Emails will now be sent from {$email}.");
    }

    /**
     * For mail providers with no sign-in option. Nothing is saved unless
     * the server actually accepts the login, so a typo can't silently
     * break every email this client sends.
     */
    public function saveSmtp(Request $request): RedirectResponse
    {
        $this->authorizePage();

        $company = Company::current();
        $existing = $company->mailAccount?->isSmtp() ? $company->mailAccount : null;

        $data = $request->validateWithBag('smtp', [
            'host' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9.\-]+$/'],
            'port' => ['required', 'integer', Rule::in([25, 465, 587, 2525])],
            'username' => ['required', 'string', 'max:255'],
            // Never echoed back into the form, so blank means "keep the saved one".
            'password' => [Rule::requiredIf(! $existing), 'nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ], [
            'host.regex' => 'Enter just the server name, e.g. smtp.zoho.com.',
            'port.in' => 'Use port 587 (recommended), 465, 25 or 2525.',
        ]);

        $settings = [
            'host' => $data['host'],
            'port' => (int) $data['port'],
            'username' => $data['username'],
            'password' => $data['password'] ?? $existing->settings['password'],
        ];

        try {
            $transport = Mail::build(CompanyMailer::smtpConfig($settings))->getSymfonyTransport();
            $transport->start();
            $transport->stop();
        } catch (\Throwable $e) {
            return back()->withInput($request->except('password'))
                ->withErrors(['host' => 'Could not sign in to that mail server: '.Str::limit($e->getMessage(), 200)], 'smtp');
        }

        $company->mailAccount()->updateOrCreate([], [
            'provider' => MailProviders::SMTP,
            'email' => $data['email'],
            'access_token' => null,
            'refresh_token' => null,
            'token_expires_at' => null,
            'settings' => $settings,
            'connected_by' => $request->user()->id,
            'needs_reconnect' => false,
        ]);

        return redirect()->route('admin.mail-setup.show')->with('status', "Connected. Emails will now be sent from {$data['email']}.");
    }

    public function test(Request $request): RedirectResponse
    {
        $this->authorizePage();

        $company = Company::current();

        // CompanyMailer quietly falls back to the default mailer otherwise -
        // a "test" that went out some other way would prove nothing.
        if (! $company->mailAccount || ! CompanyMailer::usable($company->mailAccount)) {
            return redirect()->route('admin.mail-setup.show')->with('error', 'Connect a mail account first.');
        }

        $to = $request->user()->email;

        try {
            CompanyMailer::for($company)->raw(
                "This is a test email from {$company->name}. Your mail setup is working.",
                fn ($message) => $message->to($to)->subject("Test email from {$company->name}")
            );
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('admin.mail-setup.show')->with('error', 'The test email could not be sent: '.Str::limit($e->getMessage(), 300));
        }

        return redirect()->route('admin.mail-setup.show')->with('status', "Test email sent to {$to}.");
    }

    public function disconnect(): RedirectResponse
    {
        $this->authorizePage();

        $account = Company::current()->mailAccount;

        if ($account) {
            MailProviders::find($account->provider)?->revoke($account);
            $account->delete();
        }

        return redirect()->route('admin.mail-setup.show')->with('status', 'Mail account disconnected.');
    }

    // An organization-wide setting, so it follows the same permission as General Settings.
    protected function authorizePage(): void
    {
        abort_unless(auth()->user()->can('page_GeneralSettings'), 403);
    }
}
