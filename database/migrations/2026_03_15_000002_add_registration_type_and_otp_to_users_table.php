<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'registrant_type')) {
                $table->string('registrant_type', 30)->nullable()->after('department_id');
            }

            if (!Schema::hasColumn('users', 'employee_office')) {
                $table->string('employee_office')->nullable()->after('registrant_type');
            }

            if (!Schema::hasColumn('users', 'employee_id_number')) {
                $table->string('employee_id_number', 100)->nullable()->after('employee_office');
            }

            if (!Schema::hasColumn('users', 'email_verification_otp')) {
                $table->string('email_verification_otp', 6)->nullable()->after('email_verified_at');
            }

            if (!Schema::hasColumn('users', 'email_verification_otp_expires_at')) {
                $table->timestamp('email_verification_otp_expires_at')->nullable()->after('email_verification_otp');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'email_verification_otp_expires_at')) {
                $table->dropColumn('email_verification_otp_expires_at');
            }

            if (Schema::hasColumn('users', 'email_verification_otp')) {
                $table->dropColumn('email_verification_otp');
            }

            if (Schema::hasColumn('users', 'employee_id_number')) {
                $table->dropColumn('employee_id_number');
            }

            if (Schema::hasColumn('users', 'employee_office')) {
                $table->dropColumn('employee_office');
            }

            if (Schema::hasColumn('users', 'registrant_type')) {
                $table->dropColumn('registrant_type');
            }
        });
    }
};
