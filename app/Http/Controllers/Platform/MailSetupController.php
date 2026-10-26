<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\CompanyMailAccount;
use App\Support\Mail\MailProviders;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The one OAuth app per provider (Google, Microsoft) that every client
 * connects their mailbox through. Clients never see these credentials -
 * they only get a "Connect ... account" button in their own Settings >
 * Mail Setup.
 */
class MailSetupController extends Controller
{
    public function edit(): View
    {
        return view('platform.mail-setup.edit', [
            'providers' => MailProviders::all(),
            'accounts' => CompanyMailAccount::with('company')->orderBy('email')->get(),
        ]);
    }

    public function update(Request $request, string $provider): RedirectResponse
    {
        $provider = MailProviders::find($provider);
        $hasSecret = filled($provider->clientSecret());

        // Each provider has its own form on the page - a named bag keeps
        // one provider's errors from showing under another's fields.
        $data = $request->validateWithBag($provider->key(), [
            'client_id' => ['required', 'string', 'max:255'],
            // Never echoed back into the form, so blank means "keep the saved one".
            'client_secret' => [Rule::requiredIf(! $hasSecret), 'nullable', 'string', 'max:255'],
        ], [
            'client_secret.required' => 'Enter the client secret.',
        ]);

        $provider->saveCredentials(trim($data['client_id']), isset($data['client_secret']) ? trim($data['client_secret']) : null);

        return redirect()->route('platform.mail-setup.edit')
            ->with('status', "{$provider->label()} mail setup saved. Clients can now connect their {$provider->label()} account.");
    }
}
