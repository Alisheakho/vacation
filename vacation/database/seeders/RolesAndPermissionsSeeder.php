<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // مهم جداً حتى ما يضل كاش قديم
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ============================
        // ⚡ الصلاحيات مع guard api
        // ============================
        $permissions = [
            'leave.create',
            'leave.view.own',
            'leave.view.all',
            'leave.approve.short', // مسؤول فرع
            'leave.review.hr',     // HR
            'leave.approve.long',  // مدير إدارة
            'leave.manage',        // أدمن
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(
                ['name' => $perm, 'guard_name' => 'api']
            );
        }

        // ============================
        // ⚡ الأدوار مع guard api
        // ============================
        $employee      = Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'api']);
        $hr            = Role::firstOrCreate(['name' => 'hr', 'guard_name' => 'api']);
        $branchManager = Role::firstOrCreate(['name' => 'branch_manager', 'guard_name' => 'api']);
        $deptManager   = Role::firstOrCreate(['name' => 'dept_manager', 'guard_name' => 'api']);
        $admin         = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);

        // ============================
        // ⚡ ربط الصلاحيات مع الأدوار
        // ============================

        $employee->syncPermissions([
            'leave.create',
            'leave.view.own',
        ]);

        $hr->syncPermissions([
            'leave.view.all',
            'leave.review.hr',
        ]);

        $branchManager->syncPermissions([
            'leave.view.all',
            'leave.approve.short',
        ]);

        $deptManager->syncPermissions([
            'leave.view.all',
            'leave.approve.long',
        ]);

        // أدمن ياخد كل شي
        $admin->syncPermissions(Permission::where('guard_name', 'api')->get());
    }
}
