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
    Schema::table('users', function (Blueprint $table) {
        if (!Schema::hasColumn('users', 'department_id')) {
            $table->foreignId('department_id')->nullable()->constrained('departments')->after('email');
        }
    });
}

public function down(): void
{
    if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'department_id')) {
        return;
    }

    Schema::table('users', function (Blueprint $table) {
        $table->dropForeign(['department_id']);
        $table->dropColumn('department_id');
    });
}

};
