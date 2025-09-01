<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $tables = [
            'products',
            'categories',
            'branches',
            'customers',
            'orders',
            'order_items',
            'order_reservations',
            'order_qoutations',
            'payment_methods',
            'payment_categories',
            'special_menus',
            'shipping_address',
            'departments',
            'employee_biometrics',
            'branch_order_intervals',
        ];

        foreach ($tables as $table) {
            if (!Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $t) use ($table) {
                    $t->softDeletes();
                });
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'products',
            'categories',
            'branches',
            'customers',
            'orders',
            'order_items',
            'order_reservations',
            'order_qoutations',
            'payment_methods',
            'payment_categories',
            'special_menus',
            'shipping_address',
            'departments',
            'employee_biometrics',
            'branch_order_intervals',
        ];

        foreach ($tables as $table) {
            if (Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $t) use ($table) {
                    $t->dropSoftDeletes();
                });
            }
        }
    }
};
