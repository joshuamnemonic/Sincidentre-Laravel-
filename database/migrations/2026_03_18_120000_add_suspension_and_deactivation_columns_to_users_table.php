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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'suspended_until')) {
                $table->timestamp('suspended_until')->nullable()->after('suspended_at');
            }

            if (!Schema::hasColumn('users', 'deactivation_category')) {
                $table->string('deactivation_category', 60)->nullable()->after('suspended_by');
            }

            if (!Schema::hasColumn('users', 'deactivated_at')) {
                $table->timestamp('deactivated_at')->nullable()->after('deactivation_category');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'deactivated_at')) {
                $table->dropColumn('deactivated_at');
            }

            if (Schema::hasColumn('users', 'deactivation_category')) {
                $table->dropColumn('deactivation_category');
            }

            if (Schema::hasColumn('users', 'suspended_until')) {
                $table->dropColumn('suspended_until');
            }
        });
    }
};
