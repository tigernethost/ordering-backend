<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure permissions are present
        if (Permission::count() === 0) {
            $this->call(PermissionsTableSeeder::class);
        }

        // Create roles
        $admin = Role::firstOrCreate(['name' => 'Administrator']);
        $manager = Role::firstOrCreate(['name' => 'Branch Manager']);

        // Administrator gets all permissions
        $admin->syncPermissions(Permission::all());

        // Branch Manager - limited permissions (Orders, Customers, Reports, Payments)
        $managerPermissions = [
            // Orders
            'read order', 'create order', 'update order',
            // Customers
            'read customer', 'create customer', 'update customer',
            // Reports
            'read order-report', 'read sales-report',
            // Payments (read-only by default)
            'read payment-category', 'read payment-method',
        ];

        $perms = Permission::whereIn('name', $managerPermissions)->get();
        $manager->syncPermissions($perms);

        $this->command->info('Roles seeded and permissions assigned.');
    }
}
