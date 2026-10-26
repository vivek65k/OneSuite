<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Mail\UserInvitationMail;
use App\Models\Company;
use App\Models\User;
use App\Support\AppLauncher;
use App\Support\ClientProvisioner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(Request $request): View
    {
        $clients = Company::query()
            ->withCount('users')
            ->withCount(['users as active_users_count' => fn ($q) => $q->where('is_active', true)])
            ->with('admins')
            // Also matches a login email, so "which client does this login
            // belong to?" is answered by pasting the email into search.
            ->when($request->query('search'), fn ($q, $search) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhereHas('users', fn ($q) => $q->where('email', 'like', "%{$search}%"))))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('platform.clients.index', [
            'clients' => $clients,
            'apps' => AppLauncher::apps(),
            'search' => $request->query('search', ''),
        ]);
    }

    /**
     * Every login belonging to one client - read-only oversight for the
     * platform admin (the client's own admin manages them in Settings >
     * Users). Roles are team-scoped by company, so the team id must be
     * this client's before roles are loaded, not the platform admin's.
     */
    public function users(Request $request, Company $client): View
    {
        setPermissionsTeamId($client->id);

        $users = $client->users()
            ->with(['roles', 'branches', 'employee'])
            ->when($request->query('search'), fn ($q, $search) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->when($request->query('status') === 'active', fn ($q) => $q->where('is_active', true))
            ->when($request->query('status') === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderByDesc('is_super_admin')
            ->orderBy('name')
            ->paginate(50)
            ->withQueryString();

        return view('platform.clients.users', [
            'client' => $client,
            'users' => $users,
            'apps' => AppLauncher::apps(),
            'search' => $request->query('search', ''),
            'status' => $request->query('status', ''),
            'totals' => [
                'all' => $client->users()->count(),
                'active' => $client->users()->where('is_active', true)->count(),
                'pending' => $client->users()->whereNull('invitation_accepted_at')->whereNull('email_verified_at')->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('platform.clients.create', [
            'client' => new Company(),
            'apps' => AppLauncher::apps(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, isCreate: true);

        // Company, its roles and its first admin - the same steps `php artisan
        // onesuite:install` uses. No password here: the admin sets their own
        // through the invite link below.
        [$client, $admin] = ClientProvisioner::create(
            [
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'app_access' => $data['app_access'],
                'max_users' => $data['max_users'],
            ],
            $data['admin_name'],
            $data['admin_email'],
        );

        $acceptUrl = URL::temporarySignedRoute('invitation.accept', now()->addDays(7), ['user' => $admin->id]);

        // Sent outside the transaction above - a mail failure shouldn't
        // roll back a client that was otherwise created successfully, and
        // a transaction shouldn't sit open across a network call anyway.
        Mail::to($admin)->send(new UserInvitationMail($admin, $acceptUrl));

        return redirect()->route('platform.clients.index')
            ->with('status', "Client \"{$client->name}\" created. An invite was sent to {$admin->email}.")
            ->with('invite_url', $acceptUrl)
            ->with('invite_email', $admin->email);
    }

    public function edit(Company $client): View
    {
        return view('platform.clients.edit', [
            'client' => $client,
            'apps' => AppLauncher::apps(),
        ]);
    }

    public function update(Request $request, Company $client): RedirectResponse
    {
        $data = $this->validated($request, isCreate: false, client: $client);

        $client->update([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'app_access' => $data['app_access'],
            'max_users' => $data['max_users'],
        ]);

        return redirect()->route('platform.clients.index')->with('status', "Client \"{$client->name}\" updated.");
    }

    /**
     * A fresh 7-day invite link for a client user who hasn't set their
     * password yet - the one shown right after creating the client is
     * only displayed once, and may have expired or never been delivered.
     */
    public function resendInvite(Company $client, User $user): RedirectResponse
    {
        abort_unless($user->company_id === $client->id, 404);

        if ($user->invitation_accepted_at) {
            return back()->with('status', "{$user->email} has already accepted their invite.");
        }

        $acceptUrl = URL::temporarySignedRoute('invitation.accept', now()->addDays(7), ['user' => $user->id]);

        Mail::to($user)->send(new UserInvitationMail($user, $acceptUrl));

        return back()
            ->with('status', "A new invite was sent to {$user->email}.")
            ->with('invite_url', $acceptUrl)
            ->with('invite_email', $user->email);
    }

    /**
     * The kill switch for non-payment - EnsureUserIsActive only checks the
     * USER's own is_active, so this also deactivates every user at the
     * client in one step, otherwise a still-active user could keep
     * signing in even after their employer's subscription lapses.
     */
    public function toggleActive(Company $client): RedirectResponse
    {
        $client->update(['is_active' => ! $client->is_active]);
        $client->users()->update(['is_active' => $client->is_active]);

        $status = $client->is_active ? 'reactivated' : 'suspended';

        return back()->with('status', "Client \"{$client->name}\" {$status}.");
    }

    protected function validated(Request $request, bool $isCreate, ?Company $client = null): array
    {
        // Can't set a limit below the logins this client already has active
        // - they'd be over it immediately. They deactivate users first.
        $minUsers = max(1, $client?->activeUserCount() ?? 1);

        $allAppKeys = array_keys(AppLauncher::apps());

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'app_access' => ['array'],
            'app_access.*' => [Rule::in($allAppKeys)],
            'user_limit_mode' => ['required', Rule::in(['unlimited', 'limited'])],
            // exclude_unless: a leftover number is ignored under "No limit".
            'max_users' => ['exclude_unless:user_limit_mode,limited', 'required', 'integer', "min:{$minUsers}", 'max:100000'],
        ];

        if ($isCreate) {
            $rules['admin_name'] = ['required', 'string', 'max:255'];
            $rules['admin_email'] = ['required', 'email', 'max:255', 'unique:users,email'];
        }

        $data = $request->validate($rules, [
            'max_users.min' => $client
                ? "{$client->name} already has {$minUsers} active ".Str::plural('user', $minUsers).' - the limit can\'t be lower than that. Deactivate users first to lower it.'
                : 'The limit must be at least 1 (the first admin counts as a user).',
            'max_users.required' => 'Enter how many users this client may have, or choose "No limit".',
        ]);

        $data['max_users'] = $data['user_limit_mode'] === 'limited' ? (int) $data['max_users'] : null;

        // Every currently known app checked means "unrestricted" (null),
        // the same convention as a user's own app_access - keeps a
        // client's access future-proof against new apps added later.
        $selected = $data['app_access'] ?? [];
        $data['app_access'] = empty(array_diff($allAppKeys, $selected)) ? null : array_values($selected);

        return $data;
    }
}
