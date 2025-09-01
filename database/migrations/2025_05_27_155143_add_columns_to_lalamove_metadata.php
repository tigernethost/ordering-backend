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
        Schema::table('lalamove_metadata', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->dropColumn('data');

            $table->string('location_name')->after('id');
            $table->string('locode')->after('location_name');
            $table->string('icon')->nullable()->after('locode');
            $table->string('vehicle_key')->after('icon');
            $table->string('description')->nullable()->after('vehicle_key');
            $table->json('load')->nullable()->after('description');
            $table->json('dimensions')->nullable()->after('load');
            $table->json('special_requests')->nullable()->after('dimensions');
            $table->json('delivery_item_specification')->nullable()->after('special_requests');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lalamove_metadata', function (Blueprint $table) {
            $table->string('type')->after('id');
            $table->json('data')->after('type');
            
            $table->dropColumn('location_name');
            $table->dropColumn('locode');
            $table->dropColumn('vehicle_key');
            $table->dropColumn('description');
            $table->dropColumn('load');
            $table->dropColumn('dimensions');
            $table->dropColumn('special_requests');
            $table->dropColumn('delivery_item_specification');
        });
    }
};
