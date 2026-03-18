<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('reports', 'location_details')) {
            Schema::table('reports', function (Blueprint $table) {
                $table->string('location_details')->nullable()->after('location');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('reports', 'location_details')) {
            Schema::table('reports', function (Blueprint $table) {
                $table->dropColumn('location_details');
            });
        }
    }
};