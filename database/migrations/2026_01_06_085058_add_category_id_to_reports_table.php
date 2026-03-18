<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('reports', 'category_id')) {
            return;
        }

        Schema::table('reports', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable()->after('title');

            $table->foreign('category_id')
                ->references('id')
                ->on('categories')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('reports', 'category_id')) {
            return;
        }

        Schema::table('reports', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
    }
};