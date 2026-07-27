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
            // صلاحيات الإجازات
            'leave.create',
            'leave.view.own',
            'leave.view.all',
            'leave.approve.short', // مسؤول فرع
            'leave.review.hr',     // HR
            'leave.approve.long',  // مدير إدارة
            'leave.manage',        // أدمن

            // صلاحيات إدارة المستخدمين
            'user.create',          // إنشاء مستخدم
            'user.update',          // تعديل مستخدم
            'user.delete',          // حذف مستخدم
            'user.view',            // عرض المستخدمين
            'user.ban',             // حظر/إلغاء حظر
            'user.change_role',     // تغيير الرول

            // صلاحيات إدارة الفروع
            'branch.create',        // إنشاء فرع
            'branch.update',        // تعديل فرع
            'branch.delete',        // حذف فرع
            'branch.view',          // عرض الفروع
            'branch.assign_manager',    // تعيين مدير فرع
            'branch.transfer_manager',  // نقل مدير فرع
            'branch.remove_manager',    // إزالة مدير فرع

            // صلاحية إنشاء مدير إدارة (أدمن فقط)
            'user.create_dept_manager',
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

        // الموظف: فقط إنشاء إجازة ورؤية إجازاته
        $employee->syncPermissions([
            'leave.create',
            'leave.view.own',
        ]);

        // HR: إدارة المستخدمين والفروع (ماعدا مدير الإدارة)
        $hr->syncPermissions([
            'leave.view.all',
            'leave.review.hr',
            'user.create',
            'user.update',
            'user.delete',
            'user.view',
            'user.ban',
            'user.change_role',
            'branch.create',
            'branch.update',
            'branch.delete',
            'branch.view',
            'branch.assign_manager',
            'branch.transfer_manager',
            'branch.remove_manager',
        ]);

        // رئيس الفرع
        $branchManager->syncPermissions([
            'leave.view.all',
            'leave.approve.short',
        ]);

        // مدير الإدارة: نفس HR + إدارة كاملة
        $deptManager->syncPermissions([
            'leave.view.all',
            'leave.approve.long',
            'user.create',
            'user.update',
            'user.delete',
            'user.view',
            'user.ban',
            'user.change_role',
            'branch.create',
            'branch.update',
            'branch.delete',
            'branch.view',
            'branch.assign_manager',
            'branch.transfer_manager',
            'branch.remove_manager',
        ]);

        // أدمن ياخد كل شي
        $admin->syncPermissions(Permission::where('guard_name', 'api')->get());
    }
}
