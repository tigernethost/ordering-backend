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
        Schema::table('order_qoutations', function (Blueprint $table) {
            $table->renameColumn('qoutation_id', 'quotation');
            $table->string('sender_stop_id')->after('quotation');
            $table->string('recipient_stop_id')->after('sender_stop_id');
            $table->string('service_type')->nullable()->after('recipient_stop_id');
            $table->decimal('distance_value', 10, 2)->nullable()->after('service_type');
            $table->string('distance_unit')->nullable()->after('distance_value');
            $table->decimal('price_total', 10, 2)->nullable()->after('distance_unit');
            $table->string('currency', 10)->nullable()->after('price_total');
            $table->timestamp('schedule_at')->nullable()->after('currency');
            $table->timestamp('expires_at')->nullable()->after('schedule_at');
            $table->string('status')->default('pending')->after('expires_at');
            $table->json('response_payload')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_qoutations', function (Blueprint $table) {
            $table->renameColumn('qoutation', 'qoutation_id');
            $table->dropColumn('sender_stop_id');
            $table->dropColumn('recipient_stop_id');
            $table->dropColumn('service_type');
            $table->dropColumn('distance_value');
            $table->dropColumn('distance_unit');
            $table->dropColumn('price_total');
            $table->dropColumn('currency');
            $table->dropColumn('schedule_at');
            $table->dropColumn('expires_at');
            $table->dropColumn('status');
            $table->dropColumn('response_payload');
        });
    }
};
