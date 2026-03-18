<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Department;
use App\Models\RoutingPosition;
use App\Models\ReportRoutingRule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminSeeder extends Seeder
{
    private function normalizeDepartmentAliases(): void
    {
        $canonical = Department::query()
            ->whereRaw('LOWER(name) = ?', ['college of technology'])
            ->first();

        if (!$canonical) {
            return;
        }

        $duplicates = Department::query()
            ->whereRaw('LOWER(name) IN (?, ?)', ['cot', 'co t'])
            ->where('id', '!=', $canonical->id)
            ->get(['id']);

        foreach ($duplicates as $duplicate) {
            DB::table('users')
                ->where('department_id', $duplicate->id)
                ->update(['department_id' => $canonical->id]);

            $stillReferenced = DB::table('users')
                ->where('department_id', $duplicate->id)
                ->exists();

            if (!$stillReferenced) {
                DB::table('departments')->where('id', $duplicate->id)->delete();
            }
        }
    }

    private function resolveDepartmentId(array $aliases): int
    {
        $aliases = array_map(fn ($name) => strtolower(trim((string) $name)), $aliases);

        // Normalize common short names to their canonical department names.
        if (in_array('cot', $aliases, true)) {
            $aliases[] = 'college of technology';
        }
        if (in_array('coed', $aliases, true)) {
            $aliases[] = 'college of education';
        }
        if (in_array('cohtm', $aliases, true)) {
            $aliases[] = 'college of hospitality and tourism management';
        }

        $aliases = array_values(array_unique(array_filter($aliases)));

        $department = Department::query()
            ->whereIn(DB::raw('LOWER(name)'), $aliases)
            ->first();

        if ($department) {
            return (int) $department->id;
        }

        $fallbackName = trim((string) ($aliases[0] ?? 'Department'));
        $created = Department::create(['name' => $fallbackName !== '' ? $fallbackName : 'Department']);

        return (int) $created->id;
    }

    public function run()
    {
        $this->normalizeDepartmentAliases();

        $routingPositions = [
            ['code' => 'disciplinary_officer', 'name' => 'Disciplinary Officer'],
            ['code' => 'facilities_officer', 'name' => 'Facilities Officer'],
            ['code' => 'iot_network_officer', 'name' => 'IoT / Network Officer'],
        ];

        foreach ($routingPositions as $position) {
            RoutingPosition::updateOrCreate(
                ['code' => $position['code']],
                ['name' => $position['name'], 'is_active' => true]
            );
        }

        $routingRules = [
            [
                'name' => 'Disciplinary major/grave routing',
                'routing_group_code' => 'disciplinary',
                'main_category_keywords' => ['disciplinary'],
                'category_keywords' => [],
                'classifications' => ['Major', 'Grave'],
                'target_position_code' => 'disciplinary_officer',
                'priority' => 10,
            ],
            [
                'name' => 'Facilities and electricity routing',
                'routing_group_code' => 'facilities_electricity',
                'main_category_keywords' => ['facilities'],
                'category_keywords' => ['electricity'],
                'classifications' => ['Major', 'Grave'],
                'target_position_code' => 'facilities_officer',
                'priority' => 20,
            ],
            [
                'name' => 'IoT and network routing',
                'routing_group_code' => 'networks_iot',
                'main_category_keywords' => ['iot', 'network'],
                'category_keywords' => ['iot', 'network'],
                'classifications' => ['Major', 'Grave'],
                'target_position_code' => 'iot_network_officer',
                'priority' => 30,
            ],
        ];

        foreach ($routingRules as $rule) {
            ReportRoutingRule::updateOrCreate(
                ['name' => $rule['name']],
                [
                    'main_category_keywords' => $rule['main_category_keywords'],
                    'category_keywords' => $rule['category_keywords'],
                    'routing_group_code' => $rule['routing_group_code'] ?? null,
                    'classifications' => $rule['classifications'],
                    'target_position_code' => $rule['target_position_code'],
                    'priority' => $rule['priority'],
                    'route_on_submission' => true,
                    'route_on_approval' => false,
                    'is_active' => true,
                ]
            );
        }

        $oldAdminEmails = [
            'admin@llcc.edu.ph',
            'admin.anna@llcc.edu.ph',
            'admin.jane@llcc.edu.ph',
        ];

        User::whereIn('email', $oldAdminEmails)->delete();

        $this->command->info('Legacy admin accounts (Johnny, Anna, Jane) removed.');

        $dsdoAccounts = [
            [
                'first_name' => 'Gerald',
                'last_name' => 'Alquizalas',
                'email' => 'alquizalas.gerald@llcc.edu.ph',
                'password' => Hash::make('123Gerald'),
                'department_aliases' => ['CoHTM', 'College of Hospitality and Tourism Management'],
            ],
            [
                'first_name' => 'Charlyn',
                'last_name' => 'Ompad',
                'email' => 'ompad.charlyn@llcc.edu.ph',
                'password' => Hash::make('123Charlyn'),
                'department_aliases' => ['CoED', 'College of Education'],
            ],
            [
                'first_name' => 'Van Dexter',
                'last_name' => 'Lachica',
                'email' => 'lachica.vandexter@llcc.edu.ph',
                'password' => Hash::make('123VanDexter'),
                'department_aliases' => ['CoT', 'College of Technology'],
            ],
        ];

        foreach ($dsdoAccounts as $adminData) {
            $admin = User::where('email', $adminData['email'])->first();
            $departmentId = $this->resolveDepartmentId((array) ($adminData['department_aliases'] ?? []));
            
            if ($admin) {
                // ✅ Update existing admin
                $admin->update([
                    'first_name' => $adminData['first_name'],
                    'last_name' => $adminData['last_name'],
                    'password' => $adminData['password'],
                    'department_id' => $departmentId,
                    'is_department_student_discipline_officer' => 1,
                    'is_top_management' => 0,
                    'routing_position_code' => null,
                    'email_verified_at' => Carbon::now(),
                    'status' => 'active',
                ]);
                
                $this->command->info("Admin {$adminData['email']} updated successfully!");
            } else {
                // ✅ Create new admin if doesn't exist
                User::create([
                    'first_name' => $adminData['first_name'],
                    'last_name' => $adminData['last_name'],
                    'email' => $adminData['email'],
                    'password' => $adminData['password'],
                    'department_id' => $departmentId,
                    'is_department_student_discipline_officer' => 1,
                    'is_top_management' => 0,
                    'routing_position_code' => null,
                    'email_verified_at' => Carbon::now(),
                    'status' => 'active',
                ]);
                
                $this->command->info("Admin {$adminData['email']} created successfully!");
            }
        }

        $topManagementAccounts = [
            [
                'first_name' => 'Fidelia',
                'last_name' => 'Comar',
                'email' => 'comar.fidelia@llcc.edu.ph',
                'password' => Hash::make('123FideliaComar'),
                'department_id' => 1,
                'employee_office' => 'Facilities',
                'routing_position_code' => 'facilities_officer',
            ],
            [
                'first_name' => 'Nonito',
                'last_name' => 'Odjinar',
                'email' => 'odjinar.nonito@llcc.edu.ph',
                'password' => Hash::make('123NonitoOdjinar'),
                'department_id' => 1,
                'employee_office' => 'IoT / Network',
                'routing_position_code' => 'iot_network_officer',
            ],
            [
                'first_name' => 'Eduardson',
                'last_name' => 'Projemo',
                'email' => 'projemo.eduardson@llcc.edu.ph',
                'password' => Hash::make('123EduardsonProjemo'),
                'department_id' => 1,
                'employee_office' => 'Disciplinary',
                'routing_position_code' => 'disciplinary_officer',
            ],
        ];

        foreach ($topManagementAccounts as $topManagementData) {
            $topManagement = User::where('email', $topManagementData['email'])->first();

            $payload = [
                'first_name' => $topManagementData['first_name'],
                'last_name' => $topManagementData['last_name'],
                'password' => $topManagementData['password'],
                'department_id' => $topManagementData['department_id'],
                'employee_office' => $topManagementData['employee_office'],
                'routing_position_code' => $topManagementData['routing_position_code'] ?? null,
                'is_department_student_discipline_officer' => 0,
                'is_top_management' => 1,
                'email_verified_at' => Carbon::now(),
                'email_verification_otp' => null,
                'email_verification_otp_expires_at' => null,
                'otp_attempts' => 0,
                'otp_locked_until' => null,
                'status' => 'active',
            ];

            if ($topManagement) {
                $topManagement->update($payload);
                $this->command->info('Top Management account ' . $topManagementData['email'] . ' updated successfully!');
            } else {
                User::create(array_merge(['email' => $topManagementData['email']], $payload));
                $this->command->info('Top Management account ' . $topManagementData['email'] . ' created successfully!');
            }
        }
        
        $this->command->info('All Department Student Discipline Officer and Top Management users processed successfully!');
    }
}


