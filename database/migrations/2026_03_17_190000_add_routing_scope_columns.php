<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'routing_group_code')) {
                $table->string('routing_group_code', 50)->nullable()->after('classification');
                $table->index('routing_group_code');
            }
        });

        Schema::table('report_routing_rules', function (Blueprint $table) {
            if (!Schema::hasColumn('report_routing_rules', 'routing_group_code')) {
                $table->string('routing_group_code', 50)->nullable()->after('target_position_code');
                $table->index('routing_group_code');
            }
        });

        Schema::table('reports', function (Blueprint $table) {
            if (!Schema::hasColumn('reports', 'assigned_position_code')) {
                $table->string('assigned_position_code', 100)->nullable()->after('assigned_to');
                $table->index('assigned_position_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            if (Schema::hasColumn('reports', 'assigned_position_code')) {
                $table->dropIndex(['assigned_position_code']);
                $table->dropColumn('assigned_position_code');
            }
        });

        Schema::table('report_routing_rules', function (Blueprint $table) {
            if (Schema::hasColumn('report_routing_rules', 'routing_group_code')) {
                $table->dropIndex(['routing_group_code']);
                $table->dropColumn('routing_group_code');
            }
        });

        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'routing_group_code')) {
                $table->dropIndex(['routing_group_code']);
                $table->dropColumn('routing_group_code');
            }
        });
    }
};
