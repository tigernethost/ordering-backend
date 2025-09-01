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
            $table->string('pin')->default('UNKNOWN')->change();
            $table->string('fid')->default('UNKNOWN')->change();
            $table->string('size')->default('UNKNOWN')->change();
            $table->string('valid')->default('UNKNOWN')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('employee_biometrics', function (Blueprint $table) {
            $table->string('pin')->default(null)->change();
            $table->string('fid')->default(null)->change();
            $table->string('size')->default(null)->change();
            $table->string('valid')->default(null)->change();
        });
    }
};
