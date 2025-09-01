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
        Schema::create('special_menu_product', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('special_menu_id');
            $table->unsignedBigInteger('product_id');

            $table->foreign('special_menu_id')->references('id')->on('special_menus')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('special_menu_product');
    }
};
