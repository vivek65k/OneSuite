<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\AppLauncher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class InvitationController extends Controller
{
    /**
     * GET and POST share one signed URL (the form's action is simply the
     * current request's own URL) so the submitted password is checked
     * against the exact same signature as the page that served the form -
     * no second signed link to keep in sync with the first.
     */
    public function accept(Request $request, User $user): View|RedirectResponse
    {
        if ($user->invitation_accepted_at) {
            return redirect()->route('login')->with('status', 'That invitation has already been used - please sign in.');
        }

        if ($request->isMethod('get')) {
            return view('auth.accept-invitation', ['invitedUser' => $user]);
        }

        $data = $request->validate([
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user->forceFill([
            'password' => $data['password'],
            'invitation_accepted_at' => now(),
            'email_verified_at' => now(),
        ])->save();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->to(AppLauncher::homeRoute($user))->with('status', 'Welcome! Your password is set.');
    }
}
