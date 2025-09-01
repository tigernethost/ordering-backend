<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lalamove_place_orders', function (Blueprint $table) {
            $table->json('driver_details')->nullable()->after('lalamove_driver_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lalamove_place_orders', function (Blueprint $table) {
            $table->dropColumn('driver_details');
        });
    }
};
