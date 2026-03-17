<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\RoutingPosition;
use App\Models\ReportRoutingRule;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AdminSeeder extends Seeder
{
    public function run()
    {
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

        $admins = [
            [
                'first_name' => 'Admin',
                'last_name' => 'Johnny',
                'email' => 'admin@llcc.edu.ph',
                'password' => Hash::make('admin123'),
                'department_id' => 1,
            ],
            [
                'first_name' => 'Admin',
                'last_name' => 'Anna',
                'email' => 'admin.anna@llcc.edu.ph',
                'password' => Hash::make('admin123'),
                'department_id' => 2,
            ],
            [
                'first_name' => 'Admin',
                'last_name' => 'Jane',
                'email' => 'admin.jane@llcc.edu.ph',
                'password' => Hash::make('admin123'),
                'department_id' => 3,
            ],
        ];

        foreach ($admins as $adminData) {
            $admin = User::where('email', $adminData['email'])->first();
            
            if ($admin) {
                // ✅ Update existing admin
                $admin->update([
                    'first_name' => $adminData['first_name'],
                    'last_name' => $adminData['last_name'],
                    'password' => $adminData['password'],
                    'department_id' => $adminData['department_id'],
                    'is_department_student_discipline_officer' => 1,
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
                    'department_id' => $adminData['department_id'],
                    'is_department_student_discipline_officer' => 1,
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


