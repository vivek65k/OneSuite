<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Only the permission list. Logins and the first client are created by
     * `php artisan onesuite:install`, which asks for real email addresses
     * and passwords - a seeded admin with a known password and no company
     * could not use the app anyway, and would be a risk on a live server.
     *
     * Demo data for a test system: `php artisan db:seed --class=DemoDataSeeder`.
     */
    public function run(): void
    {
        $this->call(PermissionSeeder::class);

        $this->command?->info('Next: run "php artisan onesuite:install" to create the platform admin and your first client.');
    }
}
