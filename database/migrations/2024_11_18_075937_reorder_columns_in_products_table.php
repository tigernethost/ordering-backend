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
        Schema::table('products', function (Blueprint $table) {
            // Temporarily drop the timestamps
            $table->dropColumn(['created_at', 'updated_at']);
        });

        Schema::table('products', function (Blueprint $table) {
            // Re-add the timestamps at the end of the table
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Reverse: Temporarily drop timestamps
            $table->dropColumn(['created_at', 'updated_at']);
        });

        Schema::table('products', function (Blueprint $table) {
            // Re-add the timestamps in their original order
            $table->timestamp('created_at')->nullable()->after('specific_column');
            $table->timestamp('updated_at')->nullable()->after('created_at');
        });
    }
};
