<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('activities', function (Blueprint $table) {
            // Add user_id column if it doesn't exist
            if (!Schema::hasColumn('activities', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('report_id');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            }

            // Add old_status and new_status columns if they don't exist
            if (!Schema::hasColumn('activities', 'old_status')) {
                $table->string('old_status')->nullable()->after('performed_by');
            }

            if (!Schema::hasColumn('activities', 'new_status')) {
                $table->string('new_status')->nullable()->after('old_status');
            }
        });
    }

    public function down()
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'old_status', 'new_status']);
        });
    }
};