<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('activities')) {
            return;
        }

        Schema::table('activities', function (Blueprint $table) {
            if (!Schema::hasColumn('activities', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('report_id');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            }

            if (!Schema::hasColumn('activities', 'old_status')) {
                $table->string('old_status')->nullable()->after('performed_by');
            }

            if (!Schema::hasColumn('activities', 'new_status')) {
                $table->string('new_status')->nullable()->after('old_status');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('activities')) {
            return;
        }

        Schema::table('activities', function (Blueprint $table) {
            if (Schema::hasColumn('activities', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }

            if (Schema::hasColumn('activities', 'old_status')) {
                $table->dropColumn('old_status');
            }

            if (Schema::hasColumn('activities', 'new_status')) {
                $table->dropColumn('new_status');
            }
        });
    }
};