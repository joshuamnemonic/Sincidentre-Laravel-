<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            if (!Schema::hasColumn('reports', 'main_category_code')) {
                $table->string('main_category_code', 10)->nullable()->after('category_id');
            }

            if (!Schema::hasColumn('reports', 'person_involvement')) {
                $table->string('person_involvement', 20)->nullable()->after('person_email_address');
            }

            if (!Schema::hasColumn('reports', 'unknown_person_details')) {
                $table->text('unknown_person_details')->nullable()->after('person_involvement');
            }

            if (!Schema::hasColumn('reports', 'technical_facility_details')) {
                $table->text('technical_facility_details')->nullable()->after('unknown_person_details');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            if (Schema::hasColumn('reports', 'technical_facility_details')) {
                $table->dropColumn('technical_facility_details');
            }

            if (Schema::hasColumn('reports', 'unknown_person_details')) {
                $table->dropColumn('unknown_person_details');
            }

            if (Schema::hasColumn('reports', 'person_involvement')) {
                $table->dropColumn('person_involvement');
            }

            if (Schema::hasColumn('reports', 'main_category_code')) {
                $table->dropColumn('main_category_code');
            }
        });
    }
};
