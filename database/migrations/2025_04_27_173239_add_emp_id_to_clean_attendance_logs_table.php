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
        Schema::table('clean_attendance_logs', function (Blueprint $table) {
            $table->string('emp_id')->after('employee_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clean_attendance_logs', function (Blueprint $table) {
            $table->dropColumn('emp_id');
        });
    }
};
