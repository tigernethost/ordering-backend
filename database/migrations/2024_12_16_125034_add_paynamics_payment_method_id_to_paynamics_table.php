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
        Schema::table('paynamics_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_method_id')->after('id')->nullable();
            $table->foreign('payment_method_id')->references('id')->on('paynamics_payment_methods')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
    */
    public function down(): void
    {
        Schema::table('paynamics_payments', function (Blueprint $table) {
            $table->dropForeign(['payment_method_id']);
            $table->dropColumn('payment_method_id');
        });
    }
};