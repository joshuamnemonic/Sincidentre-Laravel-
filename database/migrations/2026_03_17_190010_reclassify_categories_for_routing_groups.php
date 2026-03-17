<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('categories')
            ->whereNull('routing_group_code')
            ->update(['routing_group_code' => 'disciplinary']);

        $upserts = [
            ['name' => 'Computer security breach', 'main_category_code' => 'I', 'main_category_name' => 'Networks and Internet / IoT', 'classification' => 'Major', 'routing_group_code' => 'networks_iot'],
            ['name' => 'Unauthorized access to school Wi-Fi', 'main_category_code' => 'I', 'main_category_name' => 'Networks and Internet / IoT', 'classification' => 'Minor', 'routing_group_code' => 'networks_iot'],
            ['name' => 'Tampering with network equipment', 'main_category_code' => 'I', 'main_category_name' => 'Networks and Internet / IoT', 'classification' => 'Major', 'routing_group_code' => 'networks_iot'],
            ['name' => 'Unauthorized access to restricted systems', 'main_category_code' => 'I', 'main_category_name' => 'Networks and Internet / IoT', 'classification' => 'Major', 'routing_group_code' => 'networks_iot'],
            ['name' => 'Network and internet service disruption', 'main_category_code' => 'I', 'main_category_name' => 'Networks and Internet / IoT', 'classification' => 'Major', 'routing_group_code' => 'networks_iot'],
            ['name' => 'Computer hacking and/or identity theft', 'main_category_code' => 'I', 'main_category_name' => 'Networks and Internet / IoT', 'classification' => 'Major', 'routing_group_code' => 'networks_iot'],
            ['name' => 'Forgery/falsification of digital academic records', 'main_category_code' => 'I', 'main_category_name' => 'Networks and Internet / IoT', 'classification' => 'Grave', 'routing_group_code' => 'networks_iot'],

            ['name' => 'Tampering with electrical fixtures', 'main_category_code' => 'H', 'main_category_name' => 'Facilities and Electricity', 'classification' => 'Major', 'routing_group_code' => 'facilities_electricity'],
            ['name' => 'Unauthorized use of power outlets', 'main_category_code' => 'H', 'main_category_name' => 'Facilities and Electricity', 'classification' => 'Minor', 'routing_group_code' => 'facilities_electricity'],
            ['name' => 'Damaging air-conditioning units or projectors', 'main_category_code' => 'H', 'main_category_name' => 'Facilities and Electricity', 'classification' => 'Major', 'routing_group_code' => 'facilities_electricity'],
            ['name' => 'Unauthorized use of classrooms and school facilities', 'main_category_code' => 'H', 'main_category_name' => 'Facilities and Electricity', 'classification' => 'Minor', 'routing_group_code' => 'facilities_electricity'],
            ['name' => 'Vandalism or malicious destruction of school property', 'main_category_code' => 'H', 'main_category_name' => 'Facilities and Electricity', 'classification' => 'Major', 'routing_group_code' => 'facilities_electricity'],
            ['name' => 'Theft or unjust enrichment involving school property/equipment', 'main_category_code' => 'H', 'main_category_name' => 'Facilities and Electricity', 'classification' => 'Grave', 'routing_group_code' => 'facilities_electricity'],

            ['name' => 'Violation of institute-imposed policies', 'main_category_code' => 'D', 'main_category_name' => 'Disciplinary', 'classification' => 'Minor', 'routing_group_code' => 'disciplinary'],
            ['name' => 'Simple misconduct', 'main_category_code' => 'D', 'main_category_name' => 'Disciplinary', 'classification' => 'Minor', 'routing_group_code' => 'disciplinary'],
            ['name' => 'Harassment or any form of bullying', 'main_category_code' => 'D', 'main_category_name' => 'Disciplinary', 'classification' => 'Major', 'routing_group_code' => 'disciplinary'],
            ['name' => 'Assault resulting in physical injury or death', 'main_category_code' => 'D', 'main_category_name' => 'Disciplinary', 'classification' => 'Grave', 'routing_group_code' => 'disciplinary'],
        ];

        foreach ($upserts as $category) {
            DB::table('categories')->updateOrInsert(
                ['name' => $category['name']],
                [
                    'main_category_code' => $category['main_category_code'],
                    'main_category_name' => $category['main_category_name'],
                    'classification' => $category['classification'],
                    'routing_group_code' => $category['routing_group_code'],
                    'updated_at' => now(),
                    'created_at' => DB::raw('COALESCE(created_at, NOW())'),
                ]
            );
        }

        DB::table('categories')
            ->where(function ($q) {
                $q->where('name', 'like', '%network%')
                  ->orWhere('name', 'like', '%internet%')
                  ->orWhere('name', 'like', '%iot%')
                  ->orWhere('name', 'like', '%computer%')
                  ->orWhere('name', 'like', '%software%')
                  ->orWhere('name', 'like', '%social media%')
                  ->orWhere('name', 'like', '%digital%')
                  ->orWhere('name', 'like', '%credit card fraud%');
            })
            ->update(['routing_group_code' => 'networks_iot']);

        DB::table('categories')
            ->where(function ($q) {
                $q->where('name', 'like', '%facility%')
                  ->orWhere('name', 'like', '%electrical%')
                  ->orWhere('name', 'like', '%power%')
                  ->orWhere('name', 'like', '%classroom%')
                  ->orWhere('name', 'like', '%infrastructure%')
                  ->orWhere('name', 'like', '%lighting%')
                  ->orWhere('name', 'like', '%plumbing%')
                  ->orWhere('name', 'like', '%restroom%')
                  ->orWhere('name', 'like', '%air-conditioning%')
                  ->orWhere('name', 'like', '%ventilation%')
                  ->orWhere('name', 'like', '%projector%');
            })
            ->update(['routing_group_code' => 'facilities_electricity']);

        $topManagers = DB::table('users')
            ->where('is_top_management', 1)
            ->whereNotNull('routing_position_code')
            ->get(['first_name', 'last_name', 'routing_position_code']);

        foreach ($topManagers as $topManager) {
            $fullName = trim((string) (($topManager->first_name ?? '') . ' ' . ($topManager->last_name ?? '')));
            if ($fullName === '') {
                continue;
            }

            DB::table('reports')
                ->whereNull('assigned_position_code')
                ->whereRaw('LOWER(assigned_to) = ?', [strtolower($fullName)])
                ->update(['assigned_position_code' => $topManager->routing_position_code]);
        }
    }

    public function down(): void
    {
        DB::table('categories')
            ->whereIn('name', [
                'Computer security breach',
                'Unauthorized access to school Wi-Fi',
                'Tampering with network equipment',
                'Unauthorized access to restricted systems',
                'Network and internet service disruption',
                'Forgery/falsification of digital academic records',
                'Tampering with electrical fixtures',
                'Unauthorized use of power outlets',
                'Damaging air-conditioning units or projectors',
                'Unauthorized use of classrooms and school facilities',
                'Vandalism or malicious destruction of school property',
                'Theft or unjust enrichment involving school property/equipment',
            ])
            ->delete();
    }
};
