<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!DB::getSchemaBuilder()->hasColumn('reports', 'location_details')) {
            return;
        }

        DB::table('reports')
            ->whereNotNull('location')
            ->where('location', 'like', '% - %')
            ->orderBy('id')
            ->chunkById(200, function ($reports) {
                foreach ($reports as $report) {
                    $locationText = trim((string) $report->location);

                    if ($locationText === '') {
                        continue;
                    }

                    $parts = explode(' - ', $locationText, 2);
                    if (count($parts) !== 2) {
                        continue;
                    }

                    $baseLocation = trim($parts[0]);
                    $locationDetails = trim($parts[1]);

                    if ($baseLocation === '' || $locationDetails === '') {
                        continue;
                    }

                    DB::table('reports')
                        ->where('id', $report->id)
                        ->update([
                            'location' => $baseLocation,
                            'location_details' => $locationDetails,
                        ]);
                }
            });
    }

    public function down(): void
    {
        if (!DB::getSchemaBuilder()->hasColumn('reports', 'location_details')) {
            return;
        }

        DB::table('reports')
            ->whereNotNull('location_details')
            ->where('location_details', '!=', '')
            ->orderBy('id')
            ->chunkById(200, function ($reports) {
                foreach ($reports as $report) {
                    $location = trim((string) $report->location);
                    $details = trim((string) $report->location_details);

                    if ($location === '' || $details === '') {
                        continue;
                    }

                    DB::table('reports')
                        ->where('id', $report->id)
                        ->update([
                            'location' => $location . ' - ' . $details,
                            'location_details' => null,
                        ]);
                }
            });
    }
};