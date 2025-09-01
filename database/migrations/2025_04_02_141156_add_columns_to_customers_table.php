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
        Schema::table('customers', function (Blueprint $table) {
            $table->string('first_name')->nullable()->change();
            $table->string('last_name')->nullable()->change();
            $table->string('province')->nullable()->change();
            $table->string('firebase_uid')->after('id')->nullable()->unique();
            $table->string('name')->after('firebase_uid')->nullable();
            $table->string('photo_url')->after('name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('firebase_uid');
            $table->dropColumn('name');
            $table->dropColumn('photo_url');
        });
    }
};
