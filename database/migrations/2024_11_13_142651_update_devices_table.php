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
        Schema::table('devices', function (Blueprint $table) {
            // Add new columns
            $table->foreignId('branch_id')->nullable()->constrained()->onDelete('cascade');
            $table->integer('port')->default(4370);
            $table->string('device_name')->nullable();

            // Drop the existing 'location' column if it is no longer needed
            $table->dropColumn('location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            // Reverse the changes
            $table->dropForeign(['branch_id']);
            $table->dropColumn(['branch_id', 'port', 'device_name']);

            // Re-add the 'location' column
            $table->string('location')->nullable();
        });
    }
};
