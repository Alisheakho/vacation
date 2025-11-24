<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // الصلاحيات (تقدّر تزيد عليها)
        Permission::firstOrCreate(['name' => 'leave.create']);
        Permission::firstOrCreate(['name' => 'leave.view.own']);
        Permission::firstOrCreate(['name' => 'leave.view.all']);
        Permission::firstOrCreate(['name' => 'leave.approve.short']); // مسؤول فرع
        Permission::firstOrCreate(['name' => 'leave.review.hr']);     // HR
        Permission::firstOrCreate(['name' => 'leave.approve.long']);  // مدير إدارة
        Permission::firstOrCreate(['name' => 'leave.manage']);        // أدمن

        // الأدوار
        $employee      = Role::firstOrCreate(['name' => 'employee']);
        $hr            = Role::firstOrCreate(['name' => 'hr']);
        $branchManager = Role::firstOrCreate(['name' => 'branch_manager']);
        $deptManager   = Role::firstOrCreate(['name' => 'dept_manager']);
        $admin         = Role::firstOrCreate(['name' => 'admin']);

        // ربط الصلاحيات بالأدوار
        $employee->givePermissionTo(['leave.create', 'leave.view.own']);

        $hr->givePermissionTo([
            'leave.view.all',
            'leave.review.hr',
        ]);

        $branchManager->givePermissionTo([
            'leave.view.all',
            'leave.approve.short',
        ]);

        $deptManager->givePermissionTo([
            'leave.view.all',
            'leave.approve.long',
        ]);

        $admin->givePermissionTo(Permission::all());
    }
}
