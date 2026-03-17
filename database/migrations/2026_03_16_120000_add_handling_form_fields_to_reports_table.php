<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            if (!Schema::hasColumn('reports', 'hearing_date')) {
                $table->date('hearing_date')->nullable()->after('target_date');
            }
            if (!Schema::hasColumn('reports', 'hearing_time')) {
                $table->time('hearing_time')->nullable()->after('hearing_date');
            }
            if (!Schema::hasColumn('reports', 'hearing_venue')) {
                $table->string('hearing_venue')->nullable()->after('hearing_time');
            }
            if (!Schema::hasColumn('reports', 'respondent_notified_at')) {
                $table->timestamp('respondent_notified_at')->nullable()->after('hearing_venue');
            }
            if (!Schema::hasColumn('reports', 'respondent_notified_by')) {
                $table->unsignedBigInteger('respondent_notified_by')->nullable()->after('respondent_notified_at');
                $table->foreign('respondent_notified_by')->references('id')->on('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('reports', 'reprimand_document_path')) {
                $table->string('reprimand_document_path')->nullable()->after('respondent_notified_by');
            }
            if (!Schema::hasColumn('reports', 'reprimand_issued_at')) {
                $table->timestamp('reprimand_issued_at')->nullable()->after('reprimand_document_path');
            }
            if (!Schema::hasColumn('reports', 'reprimand_issued_by')) {
                $table->unsignedBigInteger('reprimand_issued_by')->nullable()->after('reprimand_issued_at');
                $table->foreign('reprimand_issued_by')->references('id')->on('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('reports', 'student_acknowledged_reprimand_at')) {
                $table->timestamp('student_acknowledged_reprimand_at')->nullable()->after('reprimand_issued_by');
            }

            if (!Schema::hasColumn('reports', 'suspension_document_path')) {
                $table->string('suspension_document_path')->nullable()->after('student_acknowledged_reprimand_at');
            }
            if (!Schema::hasColumn('reports', 'suspension_days')) {
                $table->unsignedInteger('suspension_days')->nullable()->after('suspension_document_path');
            }
            if (!Schema::hasColumn('reports', 'suspension_effective_date')) {
                $table->date('suspension_effective_date')->nullable()->after('suspension_days');
            }
            if (!Schema::hasColumn('reports', 'offense_count')) {
                $table->unsignedTinyInteger('offense_count')->nullable()->after('suspension_effective_date');
            }
            if (!Schema::hasColumn('reports', 'appeal_deadline_at')) {
                $table->timestamp('appeal_deadline_at')->nullable()->after('offense_count');
            }
            if (!Schema::hasColumn('reports', 'disciplinary_action')) {
                $table->string('disciplinary_action')->nullable()->after('appeal_deadline_at');
            }
            if (!Schema::hasColumn('reports', 'suspension_issued_by')) {
                $table->unsignedBigInteger('suspension_issued_by')->nullable()->after('disciplinary_action');
                $table->foreign('suspension_issued_by')->references('id')->on('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('reports', 'suspension_issued_at')) {
                $table->timestamp('suspension_issued_at')->nullable()->after('suspension_issued_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            if (Schema::hasColumn('reports', 'respondent_notified_by')) {
                $table->dropForeign(['respondent_notified_by']);
            }
            if (Schema::hasColumn('reports', 'reprimand_issued_by')) {
                $table->dropForeign(['reprimand_issued_by']);
            }
            if (Schema::hasColumn('reports', 'suspension_issued_by')) {
                $table->dropForeign(['suspension_issued_by']);
            }

            $table->dropColumn([
                'hearing_date',
                'hearing_time',
                'hearing_venue',
                'respondent_notified_at',
                'respondent_notified_by',
                'reprimand_document_path',
                'reprimand_issued_at',
                'reprimand_issued_by',
                'student_acknowledged_reprimand_at',
                'suspension_document_path',
                'suspension_days',
                'suspension_effective_date',
                'offense_count',
                'appeal_deadline_at',
                'disciplinary_action',
                'suspension_issued_by',
                'suspension_issued_at',
            ]);
        });
    }
};
