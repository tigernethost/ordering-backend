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
        Schema::table('productables', function (Blueprint $table) {
            $table->integer('default_slots')->default(0)->after('slots');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productables', function (Blueprint $table) {
            $table->dropColumn('default_slots');
        });
    }
};