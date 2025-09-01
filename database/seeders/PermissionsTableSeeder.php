<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions = [
            // Permissions
            ['name' => 'create permission', 'group' => 'Permission'],
            ['name' => 'read permission', 'group' => 'Permission'],
            ['name' => 'update permission', 'group' => 'Permission'],
            ['name' => 'delete permission', 'group' => 'Permission'],

            // Roles
            ['name' => 'create role', 'group' => 'Role'],
            ['name' => 'read role', 'group' => 'Role'],
            ['name' => 'update role', 'group' => 'Role'],
            ['name' => 'delete role', 'group' => 'Role'],

            // Users
            ['name' => 'create user', 'group' => 'User'],
            ['name' => 'read user', 'group' => 'User'],
            ['name' => 'update user', 'group' => 'User'],
            ['name' => 'delete user', 'group' => 'User'],

            // Employees
            ['name' => 'create employee', 'group' => 'Employee'],
            ['name' => 'read employee', 'group' => 'Employee'],
            ['name' => 'update employee', 'group' => 'Employee'],
            ['name' => 'delete employee', 'group' => 'Employee'],

            // Employee Biometrics
            ['name' => 'create employee-biometric', 'group' => 'Employee Biometric'],
            ['name' => 'read employee-biometric', 'group' => 'Employee Biometric'],
            ['name' => 'update employee-biometric', 'group' => 'Employee Biometric'],
            ['name' => 'delete employee-biometric', 'group' => 'Employee Biometric'],

            // Attendance Logs
            ['name' => 'create attendance-log', 'group' => 'Attendance Log'],
            ['name' => 'read attendance-log', 'group' => 'Attendance Log'],
            ['name' => 'update attendance-log', 'group' => 'Attendance Log'],
            ['name' => 'delete attendance-log', 'group' => 'Attendance Log'],

            // Departments
            ['name' => 'create department', 'group' => 'Department'],
            ['name' => 'read department', 'group' => 'Department'],
            ['name' => 'update department', 'group' => 'Department'],
            ['name' => 'delete department', 'group' => 'Department'],

            // Devices
            ['name' => 'create device', 'group' => 'Device'],
            ['name' => 'read device', 'group' => 'Device'],
            ['name' => 'update device', 'group' => 'Device'],
            ['name' => 'delete device', 'group' => 'Device'],

            // Branches
            ['name' => 'create branch', 'group' => 'Branch'],
            ['name' => 'read branch', 'group' => 'Branch'],
            ['name' => 'update branch', 'group' => 'Branch'],
            ['name' => 'delete branch', 'group' => 'Branch'],

            // Branch Order Intervals
            ['name' => 'create branch-order-intervals', 'group' => 'Branch Order Interval'],
            ['name' => 'read branch-order-intervals', 'group' => 'Branch Order Interval'],
            ['name' => 'update branch-order-intervals', 'group' => 'Branch Order Interval'],
            ['name' => 'delete branch-order-intervals', 'group' => 'Branch Order Interval'],

            // Products
            ['name' => 'create product', 'group' => 'Product'],
            ['name' => 'read product', 'group' => 'Product'],
            ['name' => 'update product', 'group' => 'Product'],
            ['name' => 'delete product', 'group' => 'Product'],

            // Categories
            ['name' => 'create category', 'group' => 'Category'],
            ['name' => 'read category', 'group' => 'Category'],
            ['name' => 'update category', 'group' => 'Category'],
            ['name' => 'delete category', 'group' => 'Category'],

            // Special Menus
            ['name' => 'create special-menu', 'group' => 'Special Menu'],
            ['name' => 'read special-menu', 'group' => 'Special Menu'],
            ['name' => 'update special-menu', 'group' => 'Special Menu'],
            ['name' => 'delete special-menu', 'group' => 'Special Menu'],

            // Orders
            ['name' => 'create order', 'group' => 'Order'],
            ['name' => 'read order', 'group' => 'Order'],
            ['name' => 'update order', 'group' => 'Order'],
            ['name' => 'delete order', 'group' => 'Order'],

            // Customers
            ['name' => 'create customer', 'group' => 'Customer'],
            ['name' => 'read customer', 'group' => 'Customer'],
            ['name' => 'update customer', 'group' => 'Customer'],
            ['name' => 'delete customer', 'group' => 'Customer'],

            // Payment Categories
            ['name' => 'create payment-category', 'group' => 'Payment Category'],
            ['name' => 'read payment-category', 'group' => 'Payment Category'],
            ['name' => 'update payment-category', 'group' => 'Payment Category'],
            ['name' => 'delete payment-category', 'group' => 'Payment Category'],

            // Payment Methods
            ['name' => 'create payment-method', 'group' => 'Payment Method'],
            ['name' => 'read payment-method', 'group' => 'Payment Method'],
            ['name' => 'update payment-method', 'group' => 'Payment Method'],
            ['name' => 'delete payment-method', 'group' => 'Payment Method'],

            // Paynamics Payment Categories
            ['name' => 'create paynamics-payment-category', 'group' => 'Paynamics Payment Category'],
            ['name' => 'read paynamics-payment-category', 'group' => 'Paynamics Payment Category'],
            ['name' => 'update paynamics-payment-category', 'group' => 'Paynamics Payment Category'],
            ['name' => 'delete paynamics-payment-category', 'group' => 'Paynamics Payment Category'],

            // Paynamics Payment Methods
            ['name' => 'create paynamics-payment-method', 'group' => 'Paynamics Payment Method'],
            ['name' => 'read paynamics-payment-method', 'group' => 'Paynamics Payment Method'],
            ['name' => 'update paynamics-payment-method', 'group' => 'Paynamics Payment Method'],
            ['name' => 'delete paynamics-payment-method', 'group' => 'Paynamics Payment Method'],

            // Attendance (admin CRUD available but not shown in menu)
            ['name' => 'create attendance', 'group' => 'Attendance'],
            ['name' => 'read attendance', 'group' => 'Attendance'],
            ['name' => 'update attendance', 'group' => 'Attendance'],
            ['name' => 'delete attendance', 'group' => 'Attendance'],

            // Paybiz Wallets
            ['name' => 'create paybiz-wallet', 'group' => 'Paybiz Wallet'],
            ['name' => 'read paybiz-wallet', 'group' => 'Paybiz Wallet'],
            ['name' => 'update paybiz-wallet', 'group' => 'Paybiz Wallet'],
            ['name' => 'delete paybiz-wallet', 'group' => 'Paybiz Wallet'],

            // Reports (read-only)
            ['name' => 'read order-report', 'group' => 'Report'],
            ['name' => 'read sales-report', 'group' => 'Report'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission['name']], ['group' => $permission['group']]);
        }

        $this->command->info('Permissions seeded successfully with specific groups!');
    }
}
