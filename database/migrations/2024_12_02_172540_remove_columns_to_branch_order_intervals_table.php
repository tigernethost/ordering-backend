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
        Schema::table('branch_order_intervals', function (Blueprint $table) {
            $table->dropColumn('slots');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branch_order_intervals', function (Blueprint $table) {
            $table->integer('slots')->default(0)->after('operating_days');
        });
    }
};
