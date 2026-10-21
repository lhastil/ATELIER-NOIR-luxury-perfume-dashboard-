<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Creates the local demonstration administrator described in the README.
 *
 * The address uses the reserved .test domain and the password is publicly
 * documented, so this account is for local demos only and is never created
 * in production. Running the seeder again updates the same account.
 */
class DemoAdminSeeder extends Seeder
{
    public const EMAIL = 'admin@atelier-noir.test';

    public const PASSWORD = 'atelier-demo';

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('Skipped: the demo administrator is never created in production.');

            return;
        }

        User::updateOrCreate(
            ['email' => self::EMAIL],
            ['name' => 'Demo Administrator', 'password' => Hash::make(self::PASSWORD)],
        );
    }
}
