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
        if (!Schema::hasTable('reports') || Schema::hasColumn('reports', 'rejection_reason')) {
            return;
        }

        Schema::table('reports', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('reports') || !Schema::hasColumn('reports', 'rejection_reason')) {
            return;
        }

        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn('rejection_reason');
        });
    }
};
