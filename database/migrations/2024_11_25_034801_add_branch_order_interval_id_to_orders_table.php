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
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_order_interval_id')->after('branch_id')->nullable();
            $table->foreign('branch_order_interval_id')->references('id')->on('branch_order_intervals')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['branch_order_interval_id']);
            $table->dropColumn('branch_order_interval_id');
        });
    }
};
