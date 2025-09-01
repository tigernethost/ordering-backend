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
        Schema::create('lalamove_place_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('lalamove_order_id');
            $table->string('lalamove_quotation_id');
            $table->string('lalamove_driver_id')->nullable();
            $table->string('share_link')->nullable();
            $table->string('status')->default('ASSIGNING_DRIVER');

            // Distance
            $table->decimal('distance_value', 10, 2)->nullable();
            $table->string('distance_unit')->nullable();

            // Price Breakdown
            $table->decimal('price_base', 10, 2)->nullable();
            $table->decimal('price_total_exclude_priority_fee', 10, 2)->nullable();
            $table->decimal('price_total', 10, 2)->nullable();
            $table->string('currency', 10)->nullable();

            // Partner
            $table->string('partner')->nullable();

            // Stops (full JSON array)
            $table->json('stops')->nullable();

            // Full response from Lalamove (optional for debugging/logging)
            $table->json('response_payload')->nullable();

            // Foreign key to your order
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lalamove_place_orders');
    }
};
