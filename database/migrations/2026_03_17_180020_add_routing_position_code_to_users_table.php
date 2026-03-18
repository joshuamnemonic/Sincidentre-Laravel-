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
        if (!Schema::hasTable('users') || Schema::hasColumn('users', 'routing_position_code')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('routing_position_code')->nullable()->after('employee_office');
            $table->index('routing_position_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'routing_position_code')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['routing_position_code']);
            $table->dropColumn('routing_position_code');
        });
    }
};
