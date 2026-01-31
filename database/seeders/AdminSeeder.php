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
        $adminEmail = 'admin@llcc.edu.ph';
        
        // Find or create admin
        $admin = User::where('email', $adminEmail)->first();
        
        if ($admin) {
            // ✅ Update existing admin
            $admin->update([
                'first_name' => 'Admin',
                'last_name' => 'Johnny',
                'password' => Hash::make('admin123'),
                'department_id' => 1,
                'is_admin' => 1,
                'email_verified_at' => Carbon::now(),
            ]);
            
            $this->command->info('Admin user updated successfully!');
        } else {
            // ✅ Create new admin if doesn't exist
            User::create([
                'first_name' => 'Admin',
                'last_name' => 'Johnny',
                'email' => $adminEmail,
                'password' => Hash::make('admin123'),
                'department_id' => 1,
                'is_admin' => 1,
                'email_verified_at' => Carbon::now(),
            ]);
            
            $this->command->info('Admin user created successfully!');
        }
    }
}