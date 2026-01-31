<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Only add the columns that don't exist
            if (!Schema::hasColumn('users', 'suspension_reason')) {
                $table->text('suspension_reason')->nullable()->after('status');
            }
            
            if (!Schema::hasColumn('users', 'suspended_at')) {
                $table->timestamp('suspended_at')->nullable()->after('suspension_reason');
            }
            
            if (!Schema::hasColumn('users', 'suspended_by')) {
                $table->unsignedBigInteger('suspended_by')->nullable()->after('suspended_at');
                
                // Add foreign key
                $table->foreign('suspended_by')
                      ->references('id')
                      ->on('users')
                      ->onDelete('set null');
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop in reverse order
            if (Schema::hasColumn('users', 'suspended_by')) {
                $table->dropForeign(['suspended_by']);
                $table->dropColumn('suspended_by');
            }
            
            if (Schema::hasColumn('users', 'suspended_at')) {
                $table->dropColumn('suspended_at');
            }
            
            if (Schema::hasColumn('users', 'suspension_reason')) {
                $table->dropColumn('suspension_reason');
            }
        });
    }
};