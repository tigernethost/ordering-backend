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
            //
            $table->string('image_large')->nullable();
            $table->string('image_medium')->nullable();
            $table->string('image_small')->nullable();
            $table->string('image_thumbnail')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            //
            $table->dropColumn(['image_large', 'image_medium', 'image_small', 'image_thumbnail']);
        });
    }
};
