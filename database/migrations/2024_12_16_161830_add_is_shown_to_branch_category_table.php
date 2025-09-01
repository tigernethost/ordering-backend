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
        Schema::table('branch_category', function (Blueprint $table) {
            $table->boolean('is_shown')->default(0)->nullable()->after('default_slots');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branch_category', function (Blueprint $table) {
            $table->dropColumn('is_shown');
        });
    }
};
