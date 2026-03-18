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
        if (!Schema::hasTable('reports')) {
            return;
        }

        Schema::table('reports', function (Blueprint $table) {
            if (!Schema::hasColumn('reports', 'person_full_name')) {
                $table->string('person_full_name')->nullable()->after('location');
            }
            if (!Schema::hasColumn('reports', 'person_college_department')) {
                $table->string('person_college_department')->nullable()->after('person_full_name');
            }
            if (!Schema::hasColumn('reports', 'person_role')) {
                $table->string('person_role', 50)->nullable()->after('person_college_department');
            }
            if (!Schema::hasColumn('reports', 'person_contact_number')) {
                $table->string('person_contact_number', 50)->nullable()->after('person_role');
            }
            if (!Schema::hasColumn('reports', 'person_email_address')) {
                $table->string('person_email_address')->nullable()->after('person_contact_number');
            }

            if (!Schema::hasColumn('reports', 'has_witnesses')) {
                $table->boolean('has_witnesses')->default(false)->after('person_email_address');
            }
            if (!Schema::hasColumn('reports', 'witness_attachment')) {
                $table->string('witness_attachment')->nullable()->after('has_witnesses');
            }
            if (!Schema::hasColumn('reports', 'incident_additional_sheets')) {
                $table->text('incident_additional_sheets')->nullable()->after('witness_attachment');
            }

            if (!Schema::hasColumn('reports', 'informant_full_name')) {
                $table->string('informant_full_name')->nullable()->after('incident_additional_sheets');
            }
            if (!Schema::hasColumn('reports', 'informant_college_department')) {
                $table->string('informant_college_department')->nullable()->after('informant_full_name');
            }
            if (!Schema::hasColumn('reports', 'informant_role')) {
                $table->string('informant_role', 50)->nullable()->after('informant_college_department');
            }
            if (!Schema::hasColumn('reports', 'informant_contact_number')) {
                $table->string('informant_contact_number', 50)->nullable()->after('informant_role');
            }
            if (!Schema::hasColumn('reports', 'informant_email_address')) {
                $table->string('informant_email_address')->nullable()->after('informant_contact_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('reports')) {
            return;
        }

        Schema::table('reports', function (Blueprint $table) {
            foreach ([
                'person_full_name',
                'person_college_department',
                'person_role',
                'person_contact_number',
                'person_email_address',
                'has_witnesses',
                'witness_attachment',
                'incident_additional_sheets',
                'informant_full_name',
                'informant_college_department',
                'informant_role',
                'informant_contact_number',
                'informant_email_address',
            ] as $column) {
                if (Schema::hasColumn('reports', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
