<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $employeId = env('ADMIN_Number');

        // تأكد وجود رول admin
        if (!Role::where('name', 'admin')->exists()) {
            Role::create(['name' => 'admin', 'guard_name' => 'api']);
        }

        // أنشئ الأدمن لو غير موجود
        if (!User::where('employee_id', $employeId)->exists()) {
            $user = User::create([
                'name' => 'Super Admin',
                'employee_id' =>$employeId,
                'password' => bcrypt('admin123'),
                'section' => 'Administration',
                'jobe_title' => 'System Administrator',
                
            ]);

            $user->assignRole('admin');
        }
    }
}
