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
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('apartment');
            $table->dropColumn('postal');
            $table->string('province')->after('city');
            $table->string('zip_code')->after('region')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('apartment');
            $table->string('postal');
            $table->dropColumn('province');
            $table->dropColumn('zip_code');
        });
    }
};
