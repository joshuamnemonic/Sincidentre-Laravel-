<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            if (!Schema::hasColumn('reports', 'person_has_multiple')) {
                $table->boolean('person_has_multiple')->default(false)->after('person_email_address');
            }

            if (!Schema::hasColumn('reports', 'additional_persons')) {
                $table->longText('additional_persons')->nullable()->after('person_has_multiple');
            }

            if (!Schema::hasColumn('reports', 'witness_details')) {
                $table->longText('witness_details')->nullable()->after('witness_attachment');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            if (Schema::hasColumn('reports', 'witness_details')) {
                $table->dropColumn('witness_details');
            }

            if (Schema::hasColumn('reports', 'additional_persons')) {
                $table->dropColumn('additional_persons');
            }

            if (Schema::hasColumn('reports', 'person_has_multiple')) {
                $table->dropColumn('person_has_multiple');
            }
        });
    }
};
