<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');

        // تأكد وجود رول admin
        if (!Role::where('name', 'admin')->exists()) {
            Role::create(['name' => 'admin', 'guard_name' => 'api']);
        }

        // أنشئ الأدمن لو غير موجود
        if (!User::where('email', $email)->exists()) {
            $user = User::create([
                'name' => 'Super Admin',
                'email' =>'ali@gmail.com',
                'password' => bcrypt('admin123'),
            ]);

            $user->assignRole('admin');
        }
    }
}
