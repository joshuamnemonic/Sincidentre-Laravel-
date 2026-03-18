<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('activities') || !Schema::hasColumn('activities', 'report_id')) {
            return;
        }

        Schema::table('activities', function (Blueprint $table) {
            $table->unsignedBigInteger('report_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('activities') || !Schema::hasColumn('activities', 'report_id')) {
            return;
        }

        Schema::table('activities', function (Blueprint $table) {
            $table->unsignedBigInteger('report_id')->nullable(false)->change();
        });
    }
};