<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\ClientProvisioner;
use Database\Seeders\PermissionSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;

/**
 * Turns an empty, migrated database into a working OneSuite: the
 * permission list, the platform admin (manages clients at /platform) and,
 * optionally, the first client with its own admin login. Safe to run
 * again - whatever already exists is left alone.
 *
 * Interactive by default; every answer can also be passed as an option,
 * so it can run unattended on a server.
 */
class InstallOneSuite extends Command
{
    protected $signature = 'onesuite:install
        {--platform-email= : Login that manages clients (Platform > Clients)}
        {--platform-password= : Password for that login (at least 8 characters)}
        {--company= : Name of the first client company}
        {--admin-name= : Name of that client\'s first admin}
        {--admin-email= : Login email of that client\'s first admin}
        {--admin-password= : Password for that admin (at least 8 characters)}
        {--skip-client : Only set up the platform admin, no client yet}';

    protected $description = 'Set up a fresh OneSuite database: permissions, the platform admin and the first client';

    public function handle(): int
    {
        if (! Schema::hasTable('companies') || ! Schema::hasTable('permissions')) {
            $this->error('The database is not set up yet. Run "php artisan migrate" first, then this command again.');

            return self::FAILURE;
        }

        // 1. Permissions (the list every role picks from)
        if (Permission::count() === 0) {
            $this->components->task('Creating permissions', fn () => $this->callSilently('db:seed', ['--class' => PermissionSeeder::class, '--force' => true]) === 0);
        } else {
            $this->components->info('Permissions already exist ('.Permission::count().').');
        }

        // 2. Platform admin
        if ($existing = User::where('is_platform_admin', true)->first()) {
            $this->components->info("Platform admin already exists: {$existing->email}");
        } else {
            $email = $this->askValid('platform-email', 'Platform admin email (manages your clients)', ['required', 'email', 'unique:users,email']);
            $password = $this->askPassword('platform-password', 'Platform admin password');

            // Without model events: the role/limit hooks on User are for client
            // logins, and a platform admin belongs to no client at all.
            $platformAdmin = User::withoutEvents(fn () => User::create([
                'name' => 'Platform Admin',
                'email' => $email,
                'password' => $password,
                'is_platform_admin' => true,
                'is_super_admin' => false,
                'is_active' => true,
            ]));
            $platformAdmin->forceFill(['email_verified_at' => now(), 'invitation_accepted_at' => now()])->saveQuietly();

            $this->components->info("Platform admin created: {$email}");
        }

        // 3. First client
        if ($this->option('skip-client') || (! $this->option('company') && ! $this->input->isInteractive())) {
            $this->components->warn('No client created. Add clients any time at /platform/clients.');
        } elseif (! $this->option('company') && ! $this->confirm('Create the first client company now?', true)) {
            $this->components->warn('No client created. Add clients any time at /platform/clients.');
        } else {
            $companyName = $this->askValid('company', 'Client company name', ['required', 'string', 'max:255']);
            $adminName = $this->askValid('admin-name', "Name of {$companyName}'s admin", ['required', 'string', 'max:255']);
            $adminEmail = $this->askValid('admin-email', "Login email for {$adminName}", ['required', 'email', 'unique:users,email']);
            $adminPassword = $this->askPassword('admin-password', "Password for {$adminName}");

            [$client] = ClientProvisioner::create(
                ['name' => $companyName, 'app_access' => null, 'max_users' => null],
                $adminName,
                $adminEmail,
                $adminPassword,
            );

            $this->components->info("Client \"{$client->name}\" created with all apps, a Head Office branch, and HR Manager / Manager / Employee roles. {$adminEmail} can sign in now.");
        }

        $this->newLine();
        $this->line('  Sign in at <fg=cyan>'.rtrim((string) config('app.url'), '/').'/login</>');
        $this->line('  Platform admin  -> manages clients at /platform/clients');
        $this->line('  Client admin    -> uses the apps (HRM, Settings, ...)');

        return self::SUCCESS;
    }

    /** An option if given (validated), otherwise a prompt that repeats until the answer is valid. */
    protected function askValid(string $option, string $question, array $rules): string
    {
        $value = $this->option($option);

        while (true) {
            $value ??= $this->input->isInteractive() ? $this->ask($question) : null;
            $validator = Validator::make(['value' => $value], ['value' => $rules]);

            if (! $validator->fails()) {
                return (string) $value;
            }

            $message = str_replace('value', $option, $validator->errors()->first('value'));

            if (! $this->input->isInteractive() || $this->option($option) !== null) {
                $this->error($message);

                exit(self::FAILURE);
            }

            $this->error($message);
            $value = null;
        }
    }

    protected function askPassword(string $option, string $question): string
    {
        if (($given = $this->option($option)) !== null) {
            if (strlen($given) < 8) {
                $this->error("--{$option} must be at least 8 characters.");

                exit(self::FAILURE);
            }

            return $given;
        }

        if (! $this->input->isInteractive()) {
            $this->error("--{$option} is required when running without prompts.");

            exit(self::FAILURE);
        }

        while (true) {
            $password = (string) $this->secret("{$question} (at least 8 characters)");

            if (strlen($password) < 8) {
                $this->error('At least 8 characters, please.');

                continue;
            }

            if ($password === (string) $this->secret('Type it again')) {
                return $password;
            }

            $this->error('The two passwords did not match.');
        }
    }
}
