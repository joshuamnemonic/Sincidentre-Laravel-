<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reports') && Schema::hasColumn('reports', 'title')) {
            Schema::table('reports', function (Blueprint $table) {
                $table->dropColumn('title');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('reports') && !Schema::hasColumn('reports', 'title')) {
            Schema::table('reports', function (Blueprint $table) {
                $table->string('title')->nullable()->after('id');
            });
        }
    }
};
