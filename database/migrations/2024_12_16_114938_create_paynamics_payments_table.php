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
        Schema::create('paynamics_payments', function (Blueprint $table) {
            $table->id();
            $table->string('firstname');
            $table->string('lastname');
            $table->string('email');
            $table->string('mobile');
            $table->string('address')->nullable();
            $table->longText('description')->nullable();

            $table->string('amount')->nullable();
            $table->string('fee')->nullable();
            $table->string('tnh_fee')->nullable();
            $table->boolean('is_inclusive')->nullable()->default(0);
            $table->longText('pay_reference')->nullable();

            $table->longText('raw_data')->nullable();
            $table->longText('initial_response')->nullable();

            $table->string('request_id');
            $table->string('response_id')->nullable();
            $table->string('merchant_id')->nullable();
            $table->string('expiry_limit')->nullable();
            $table->longText('direct_otc_info')->nullable();
            $table->longText('payment_action_info')->nullable();
            $table->longText('response')->nullable();

            $table->datetime('timestamp')->nullable();
            $table->string('rebill_id')->nullable();
            $table->longText('signature')->nullable();
            $table->string('response_code')->nullable();
            $table->longText('response_message')->nullable();
            $table->longText('response_advise')->nullable();
            $table->longText('settlement_info_details')->nullable();

            $table->boolean('mail_sent')->default(0);
            $table->string('status');
            $table->integer('type')->unsigned()->default(0)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paynamics_payments');
    }
};
