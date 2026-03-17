<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_responses', function (Blueprint $table) {
            if (!Schema::hasColumn('report_responses', 'response_type')) {
                $table->string('response_type')->nullable()->after('status');
            }

            if (!Schema::hasColumn('report_responses', 'attachment_path')) {
                $table->string('attachment_path')->nullable()->after('remarks');
            }
        });
    }

    public function down(): void
    {
        Schema::table('report_responses', function (Blueprint $table) {
            if (Schema::hasColumn('report_responses', 'attachment_path')) {
                $table->dropColumn('attachment_path');
            }

            if (Schema::hasColumn('report_responses', 'response_type')) {
                $table->dropColumn('response_type');
            }
        });
    }
};
