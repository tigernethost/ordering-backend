<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Core permissions and roles
        $this->call([
            PermissionsTableSeeder::class,
            RolesTableSeeder::class,
        ]);

        // Ensure Administrator user exists and has the Administrator role
        $adminEmail = 'dev@tigernethost.com';
        $defaultPassword = env('ADMIN_PASSWORD', 'password');
        $adminUser = User::firstOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'Administrator',
                'password' => Hash::make($defaultPassword),
            ]
        );
        if (!$adminUser->hasRole('Administrator')) {
            $adminUser->assignRole('Administrator');
        }
    }
}
