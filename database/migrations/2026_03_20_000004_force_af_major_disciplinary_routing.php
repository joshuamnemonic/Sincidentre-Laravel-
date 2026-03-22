<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Set all A–F Major categories to disciplinary_officer except ID 39
        DB::table('categories')
            ->whereIn('main_category_code', ['A', 'B', 'C', 'D', 'E', 'F'])
            ->where('classification', 'Major')
            ->where('id', '!=', 39)
            ->update([
                'routing_group_code' => 'disciplinary',
                'updated_at' => now(),
            ]);

        // ID 39 should be networks_iot
        DB::table('categories')
            ->where('id', 39)
            ->update([
                'routing_group_code' => 'networks_iot',
                'updated_at' => now(),
            ]);

        // ID 33 should be disciplinary_officer (explicit)
        DB::table('categories')
            ->where('id', 33)
            ->update([
                'routing_group_code' => 'disciplinary',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Data correction only; no rollback.
    }
};
