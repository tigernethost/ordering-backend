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
        Schema::table('attendance_logs', function (Blueprint $table) {
            if (Schema::hasColumn('attendance_logs', 'time_in')) {
                $table->dropColumn('time_in');
            }
            if (Schema::hasColumn('attendance_logs', 'time_out')) {
                $table->dropColumn('time_out');
            }

            // Add a unified timestamp column and type column for flexible entry types
            $table->timestamp('timestamp')->nullable(); // Ensure this column is created in the `up()` method
            $table->unsignedTinyInteger('type')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            if (Schema::hasColumn('attendance_logs', 'time_in')) {
                $table->dropColumn('time_in');
            }
            if (Schema::hasColumn('attendance_logs', 'time_out')) {
                $table->dropColumn('time_out');
            }
        
            if (!Schema::hasColumn('attendance_logs', 'type')) {
                $table->unsignedTinyInteger('type')->default(0);
            }
        });        
    }
};
