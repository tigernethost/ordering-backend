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
        //
        Schema::table('employee_biometrics', function (Blueprint $table) {
            // Change the biometric_data column to LONGBLOB for binary data
            $table->binary('biometric_data')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('employee_biometrics', function (Blueprint $table) {
            // Revert biometric_data column to its previous state (e.g., TEXT)
            $table->text('biometric_data')->nullable()->change();
        });
    }
};
