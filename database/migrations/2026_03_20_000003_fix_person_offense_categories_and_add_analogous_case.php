<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Move these two offenses back to Category B (Offenses Against Persons).
        DB::table('categories')
            ->whereIn('id', [32, 36])
            ->update([
                'main_category_code' => 'B',
                'main_category_name' => 'Offenses Against Persons',
                'routing_group_code' => 'disciplinary',
                'updated_at' => now(),
            ]);

        // Fallback by name for environments where IDs differ.
        DB::table('categories')
            ->whereIn('name', [
                'Harassment or any form of bullying',
                'Assault resulting in physical injury or death',
            ])
            ->update([
                'main_category_code' => 'B',
                'main_category_name' => 'Offenses Against Persons',
                'routing_group_code' => 'disciplinary',
                'updated_at' => now(),
            ]);

        // Keep existing reports aligned if they store main_category_code directly.
        DB::table('reports')
            ->whereIn('category_id', [32, 36])
            ->update(['main_category_code' => 'B']);

        // Add missing Category D major fallback item.
        DB::table('categories')->updateOrInsert(
            ['name' => 'Other analogous cases'],
            [
                'main_category_code' => 'D',
                'main_category_name' => 'Disciplinary',
                'classification' => 'Major',
                'routing_group_code' => 'disciplinary',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        // Data correction migration; no destructive rollback.
    }
};
