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
        Schema::create('paynamics_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('payment_category_id');
            $table->foreign('payment_category_id')->references('id')->on('paynamics_payment_categories')->onDelete('cascade');
            $table->string('code')->nullable();
            $table->string('icon')->nullable();
            $table->longText('description')->nullable();
            $table->string('fee')->nullable();
            $table->string('additional_fee')->nullable();
            $table->boolean('active')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paynamics_payment_methods');
    }
};
