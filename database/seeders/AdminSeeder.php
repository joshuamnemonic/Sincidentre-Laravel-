<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AdminSeeder extends Seeder
{
    public function run()
    {
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
                    'is_admin' => 1,
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
                    'is_admin' => 1,
                    'email_verified_at' => Carbon::now(),
                    'status' => 'active',
                ]);
                
                $this->command->info("Admin {$adminData['email']} created successfully!");
            }
        }
        
        $this->command->info('All admin users processed successfully!');
    }
}