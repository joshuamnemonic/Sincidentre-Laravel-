<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            if (Schema::hasColumn('reports', 'person_has_multiple')) {
                $table->string('person_has_multiple', 16)->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            if (Schema::hasColumn('reports', 'person_has_multiple')) {
                $table->boolean('person_has_multiple')->default(false)->change();
            }
        });
    }
};
