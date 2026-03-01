<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Branch;
use App\Models\Department;
use Illuminate\Support\Facades\DB;

class BranchAndDepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear old data to prevent duplicates and remove old test branches
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Department::truncate();
        Branch::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $branches = [
            ['name' => 'فرع دمشق', 'code' => 'DAM-001'],
            ['name' => 'فرع حمص', 'code' => 'HOM-002'],
            ['name' => 'فرع حلب', 'code' => 'ALE-003'],
            ['name' => 'فرع حماة', 'code' => 'HAM-004'],
            ['name' => 'فرع اللاذقية', 'code' => 'LAT-005'],
            ['name' => 'فرع طرطوس', 'code' => 'TAR-006'],
        ];

        $standardDepartments = [
            'فرع المعلوماتية',
            'القسم التنفيذي',
            'الموارد البشرية',
            'القسم المالي',
            'قسم التسويق',
            'قسم المبيعات',
            'قسم العمليات',
            'خدمة العملاء'
        ];

        foreach ($branches as $branchData) {
            $branch = Branch::create([
                'code' => $branchData['code'],
                'name' => $branchData['name'],
            ]);

            foreach ($standardDepartments as $deptName) {
                Department::create([
                    'name' => $deptName,
                    'branch_id' => $branch->id,
                ]);
            }
        }
    }
}
