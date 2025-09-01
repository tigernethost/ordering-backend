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
        //
        Schema::table('employee_biometrics', function (Blueprint $table)
         {
            Schema::table('employee_biometrics', function (Blueprint $table) {
                if (!Schema::hasColumn('employee_biometrics', 'pin')) {
                    $table->string('pin', 50)->after('employee_id')->index();
                }
    
                if (!Schema::hasColumn('employee_biometrics', 'biometric_type')) {
                    $table->enum('biometric_type', ['fingerprint', 'face', 'iris'])->after('pin');
                }
    
                if (!Schema::hasColumn('employee_biometrics', 'fid')) {
                    $table->integer('fid')->after('biometric_type');
                }
    
                if (!Schema::hasColumn('employee_biometrics', 'size')) {
                    $table->integer('size')->after('fid');
                }
    
                if (!Schema::hasColumn('employee_biometrics', 'valid')) {
                    $table->boolean('valid')->default(0)->after('size');
                }
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('employee_biometrics', function (Blueprint $table) {
            if (Schema::hasColumn('employee_biometrics', 'pin')) {
                $table->dropColumn('pin');
            }

            if (Schema::hasColumn('employee_biometrics', 'biometric_type')) {
                $table->dropColumn('biometric_type');
            }

            if (Schema::hasColumn('employee_biometrics', 'fid')) {
                $table->dropColumn('fid');
            }

            if (Schema::hasColumn('employee_biometrics', 'size')) {
                $table->dropColumn('size');
            }

            if (Schema::hasColumn('employee_biometrics', 'valid')) {
                $table->dropColumn('valid');
            }
        });
    }
};
