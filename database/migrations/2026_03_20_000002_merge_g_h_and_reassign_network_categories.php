<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $facilityMainName = 'Facility Issues';
        $networkMainName = 'Networks and IoT';

        $moveToINames = [
            'Network or internet connectivity issue',
            'Computer, projector, or laboratory equipment malfunction',
        ];

        // Merge H into G and standardize G naming/routing.
        DB::table('categories')
            ->whereIn('main_category_code', ['G', 'H'])
            ->update([
                'main_category_code' => 'G',
                'main_category_name' => $facilityMainName,
                'routing_group_code' => 'facilities_electricity',
                'updated_at' => now(),
            ]);

        // Move specific network/computer issues to Category I.
        DB::table('categories')
            ->whereIn('name', $moveToINames)
            ->update([
                'main_category_code' => 'I',
                'main_category_name' => $networkMainName,
                'routing_group_code' => 'networks_iot',
                'updated_at' => now(),
            ]);

        // Standardize existing Category I display name.
        DB::table('categories')
            ->where('main_category_code', 'I')
            ->update([
                'main_category_name' => $networkMainName,
                'routing_group_code' => 'networks_iot',
                'updated_at' => now(),
            ]);

        // Keep stored report main category codes aligned with the merged taxonomy.
        DB::table('reports')
            ->where('main_category_code', 'H')
            ->update(['main_category_code' => 'G']);

        DB::table('reports')
            ->whereIn('category_id', function ($query) {
                $query->select('id')
                    ->from('categories')
                    ->whereIn('name', [
                        'Network or internet connectivity issue',
                        'Computer, projector, or laboratory equipment malfunction',
                    ]);
            })
            ->update(['main_category_code' => 'I']);

        // Ensure direct routing to top-management officers for Minor/Major/Grave.
        DB::table('report_routing_rules')
            ->where('routing_group_code', 'facilities_electricity')
            ->update([
                'classifications' => json_encode(['Minor', 'Major', 'Grave']),
                'target_position_code' => 'facilities_officer',
                'updated_at' => now(),
            ]);

        DB::table('report_routing_rules')
            ->where('routing_group_code', 'networks_iot')
            ->update([
                'classifications' => json_encode(['Minor', 'Major', 'Grave']),
                'target_position_code' => 'iot_network_officer',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Intentionally not reverting taxonomy/routing data reshaping.
    }
};
