<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $legacyCategoryIds = DB::table('categories')
            ->whereNull('main_category_code')
            ->pluck('id');

        if ($legacyCategoryIds->isEmpty()) {
            return;
        }

        $fallbackId = DB::table('categories')
            ->where('name', 'Violation of institute-imposed policies')
            ->value('id');

        if (!$fallbackId) {
            $fallbackId = DB::table('categories')
                ->whereNotNull('main_category_code')
                ->orderBy('id')
                ->value('id');
        }

        $nameMap = [
            'Bullying' => 'Harassment or any form of bullying',
            'Harassment' => 'Harassment or any form of bullying',
            'Cyberbullying' => 'Harassment or any form of bullying',
            'Theft' => 'Theft / robbery / pilferage / extortion or any attempt thereof / unjust enrichment',
            'Vandalism' => 'Vandalism or malicious destruction of college/community property',
            'Substance Abuse' => 'Being under the influence of prohibited drugs',
            'Cheating/Plagiarism' => 'Plagiarism',
            'Cleanliness/Sanitation' => 'Littering / unsanitary acts (including spitting)',
            'Security Concerns' => 'Deliberate illegal entry into the school premises',
            'Dress Code Violation' => 'Not wearing proper school attire and/or LLCC ID inside campus',
            'Teacher Misconduct' => 'Disrespect (egregious conduct, demeaning, intimidating, passive-aggressive behavior)',
            'Privacy Violation' => 'Computer hacking and/or identity theft',
            'Facility Issues' => 'Infrastructure damage (ceiling, walls, floors, doors, windows)',
            'Grade Disputes' => 'Violation of institute-imposed policies',
            'Lost & Found' => 'Violation of institute-imposed policies',
            'Other' => 'Violation of institute-imposed policies',
        ];

        foreach ($nameMap as $legacyName => $targetName) {
            $oldIds = DB::table('categories')
                ->whereNull('main_category_code')
                ->where('name', $legacyName)
                ->pluck('id');

            if ($oldIds->isEmpty()) {
                continue;
            }

            $targetId = DB::table('categories')
                ->whereNotNull('main_category_code')
                ->where('name', $targetName)
                ->value('id');

            $newCategoryId = $targetId ?: $fallbackId;

            if ($newCategoryId) {
                DB::table('reports')
                    ->whereIn('category_id', $oldIds)
                    ->update(['category_id' => $newCategoryId]);
            }
        }

        if ($fallbackId) {
            DB::table('reports')
                ->whereIn('category_id', $legacyCategoryIds)
                ->update(['category_id' => $fallbackId]);
        }

        DB::table('categories')
            ->whereNull('main_category_code')
            ->delete();
    }

    public function down(): void
    {
        // Legacy categories are intentionally removed and not restored.
    }
};
