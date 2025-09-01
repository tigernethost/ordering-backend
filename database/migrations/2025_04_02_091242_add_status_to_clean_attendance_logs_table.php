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
            $table->boolean('is_night_shift')->default(0)->after('check_out');
            $table->enum('status', ['complete', 'incomplete', 'error'])->default('incomplete')->after('is_night_shift');
            $table->json('missing_parts')->after('status')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clean_attendance_logs', function (Blueprint $table) {
            $table->dropColumn('is_night_shift');
            $table->dropColumn('status');
            $table->dropColumn('missing_parts');
        });
    }
};
