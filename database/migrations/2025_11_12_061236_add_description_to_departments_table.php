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
        if (!Schema::hasTable('departments') || Schema::hasColumn('departments', 'description')) {
            return;
        }

        Schema::table('departments', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('departments') || !Schema::hasColumn('departments', 'description')) {
            return;
        }

        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
