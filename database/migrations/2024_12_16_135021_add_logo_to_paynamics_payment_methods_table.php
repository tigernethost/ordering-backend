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
        Schema::table('paynamics_payment_methods', function (Blueprint $table) {
            $table->longText('logo')->after('icon')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paynamics_payment_methods', function (Blueprint $table) {
            $table->dropColumn('logo');
        });
    }
};
