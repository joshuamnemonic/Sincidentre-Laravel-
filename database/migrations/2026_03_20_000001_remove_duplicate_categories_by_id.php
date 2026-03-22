<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $idsToDelete = [92, 93, 96, 97, 98, 99];

        // Explicit duplicate mappings requested by the user.
        $forcedRemap = [
            97 => 46,
            98 => 38,
            99 => 40,
        ];

        $categoriesToDelete = DB::table('categories')
            ->whereIn('id', $idsToDelete)
            ->get(['id', 'name'])
            ->keyBy('id');

        if ($categoriesToDelete->isEmpty()) {
            return;
        }

        $fallbackId = DB::table('categories')
            ->whereNotIn('id', $idsToDelete)
            ->where('name', 'Violation of institute-imposed policies')
            ->value('id');

        if (!$fallbackId) {
            $fallbackId = DB::table('categories')
                ->whereNotIn('id', $idsToDelete)
                ->orderBy('id')
                ->value('id');
        }

        foreach ($idsToDelete as $oldId) {
            if (!isset($categoriesToDelete[$oldId])) {
                continue;
            }

            $newId = $this->resolveReplacementId(
                $oldId,
                $categoriesToDelete[$oldId]->name,
                $idsToDelete,
                $forcedRemap,
                $fallbackId
            );

            if ($newId) {
                DB::table('reports')
                    ->where('category_id', $oldId)
                    ->update(['category_id' => $newId]);
            }
        }

        DB::table('categories')
            ->whereIn('id', $idsToDelete)
            ->delete();
    }

    private function resolveReplacementId(
        int $oldId,
        string $name,
        array $idsToDelete,
        array $forcedRemap,
        ?int $fallbackId
    ): ?int {
        if (isset($forcedRemap[$oldId])) {
            $forcedId = (int) $forcedRemap[$oldId];

            if (!in_array($forcedId, $idsToDelete, true) && DB::table('categories')->where('id', $forcedId)->exists()) {
                return $forcedId;
            }
        }

        $sameNameId = DB::table('categories')
            ->where('name', $name)
            ->whereNotIn('id', $idsToDelete)
            ->orderBy('id')
            ->value('id');

        if ($sameNameId) {
            return (int) $sameNameId;
        }

        return $fallbackId ? (int) $fallbackId : null;
    }

    public function down(): void
    {
        // Intentionally irreversible: only duplicate category IDs were removed.
    }
};
