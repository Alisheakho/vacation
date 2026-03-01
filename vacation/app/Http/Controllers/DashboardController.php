<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Branch;
use App\Models\Department;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function getStats()
    {
        try {
            $totalEmployees = User::count();
            $totalBranches = Branch::count();
            $totalDepartments = Department::count();

            return response()->json([
                'status' => true,
                'message' => 'Success',
                'data' => [
                    'totalEmployees' => $totalEmployees,
                    'totalBranches' => $totalBranches,
                    'totalDepartments' => $totalDepartments,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
}
