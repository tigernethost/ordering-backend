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
        Schema::create('multisys_payments', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('mobile');
            $table->longText('description')->nullable();

            $table->string('billing_address_line1')->nullable();
            $table->string('billing_address_city')->nullable();
            $table->string('billing_address_state')->nullable();
            $table->string('billing_address_zip_code')->nullable();
            $table->string('billing_address_country_code')->nullable();

            $table->string('txnid')->nullable();
            $table->string('amount')->nullable();
            $table->longText('digest')->nullable();
            $table->string('callback_url')->nullable();
            $table->string('initial_amount')->nullable();
            $table->longText('url')->nullable();
            $table->string('refno')->nullable();
            $table->string('mpay_refno')->nullable();
            
            $table->string('transaction_date')->nullable();
            $table->string('payment_channel')->nullable();
            $table->string('payment_channel_branch')->nullable();

            $table->longText('raw_data')->nullable();
            $table->longText('initial_response')->nullable();
            
            $table->longText('response')->nullable();
            $table->string('response_code')->nullable();
            $table->longText('response_message')->nullable();

            $table->boolean('mail_sent')->default(0);
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('multisys_payments');
    }
};
