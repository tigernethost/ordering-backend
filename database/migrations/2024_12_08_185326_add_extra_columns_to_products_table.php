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
            $table->boolean('is_special')->after('image_thumbnail')->default(0)->nullable();
            $table->boolean('is_hot')->after('is_special')->default(0)->nullable();
            $table->boolean('is_top_selling')->after('is_hot')->default(0)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('is_special');
            $table->dropColumn('is_hot');
            $table->dropColumn('is_top_selling');
        });
    }
};
