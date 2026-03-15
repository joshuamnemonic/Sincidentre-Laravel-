<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Move existing technical/facility categories from F to G.
        DB::table('categories')
            ->where('main_category_code', 'F')
            ->whereIn('main_category_name', [
                'Technical and Facility Issues',
                'Technical and Facility-Related Concerns',
            ])
            ->update([
                'main_category_code' => 'G',
                'main_category_name' => 'Technical and Facility Issues',
                'updated_at' => now(),
            ]);

        $publicMorals = [
            [
                'name' => 'Possession/display/distribution of pornographic or morally offensive materials',
                'classification' => 'Major',
            ],
            [
                'name' => 'Public display of intimacy (kissing, necking, petting, etc.)',
                'classification' => 'Major',
            ],
            [
                'name' => 'Acts of lewdness, indecency, or sexual advances toward students or staff',
                'classification' => 'Major',
            ],
            [
                'name' => 'Acts of lasciviousness or sexual harassment (per R.A. 11313 Safe Spaces Act)',
                'classification' => 'Grave',
            ],
            [
                'name' => 'Conviction in court for a criminal offense involving moral turpitude',
                'classification' => 'Grave',
            ],
        ];

        foreach ($publicMorals as $item) {
            DB::table('categories')->updateOrInsert(
                ['name' => $item['name']],
                [
                    'main_category_code' => 'F',
                    'main_category_name' => 'OFFENSES AGAINST PUBLIC MORALS',
                    'classification' => $item['classification'],
                    'updated_at' => now(),
                    'created_at' => DB::raw('COALESCE(created_at, NOW())'),
                ]
            );
        }
    }

    public function down(): void
    {
        // Keep existing data in place; this migration is data-correction only.
    }
};
