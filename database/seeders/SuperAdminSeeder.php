<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Creates the local development super admin account.
 *
 * WARNING: the default credentials in config/revalue.php are development-only
 * placeholders. Set SUPER_ADMIN_EMAIL and SUPER_ADMIN_PASSWORD in your local
 * .env, and change them before this project is exposed to anyone else.
 */
class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $config = config('revalue.super_admin');

        if (app()->environment('production')) {
            $this->command?->error('SuperAdminSeeder is a development-only seeder and will not run in production.');

            return;
        }

        $user = User::updateOrCreate(
            ['email' => $config['email']],
            [
                'name' => $config['name'],
                'password' => Hash::make($config['password']),
                'email_verified_at' => now(),
                'role' => User::ROLE_SUPER_ADMIN,
            ]
        );

        $this->command?->warn('DEV super admin seeded: '.$user->email);
        $this->command?->warn('These are development-only credentials. Change them before any real deployment.');
    }
}
