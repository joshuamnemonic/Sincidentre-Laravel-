<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_top_management')) {
                $table->boolean('is_top_management')->default(0)->after('is_department_student_discipline_officer');
            }
        });

        Schema::table('reports', function (Blueprint $table) {
            if (!Schema::hasColumn('reports', 'escalated_to_top_management')) {
                $table->boolean('escalated_to_top_management')->default(false)->after('handled_by');
            }

            if (!Schema::hasColumn('reports', 'escalated_at')) {
                $table->timestamp('escalated_at')->nullable()->after('escalated_to_top_management');
            }

            if (!Schema::hasColumn('reports', 'escalated_by')) {
                $table->unsignedBigInteger('escalated_by')->nullable()->after('escalated_at');
                $table->foreign('escalated_by')->references('id')->on('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            if (Schema::hasColumn('reports', 'escalated_by')) {
                $table->dropForeign(['escalated_by']);
                $table->dropColumn('escalated_by');
            }

            if (Schema::hasColumn('reports', 'escalated_at')) {
                $table->dropColumn('escalated_at');
            }

            if (Schema::hasColumn('reports', 'escalated_to_top_management')) {
                $table->dropColumn('escalated_to_top_management');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_top_management')) {
                $table->dropColumn('is_top_management');
            }
        });
    }
};
