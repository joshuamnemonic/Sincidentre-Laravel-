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
        if (!Schema::hasTable('reports') || !Schema::hasColumn('reports', 'category')) {
            return;
        }

        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('reports') || Schema::hasColumn('reports', 'category')) {
            return;
        }

        Schema::table('reports', function (Blueprint $table) {
            $table->string('category')->nullable();
        });
    }
};
