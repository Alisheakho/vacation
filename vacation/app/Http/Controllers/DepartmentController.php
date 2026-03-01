<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DepartmentController extends Controller
{
    // GET ALL DEPARTMENTS
    public function index()
    {
        try {
            $departments = Department::with(['branch', 'users'])->get();
            
            // Format for Flutter: id, name, branch_id
            $formatted = $departments->map(function ($dept) {
                return [
                    'id'        => $dept->id,
                    'name'      => $dept->name,
                    'branch_id' => (int) $dept->branch_id,
                    'branch_name' => $dept->branch ? $dept->branch->name : 'Unknown',
                    'employees_count' => $dept->users->count(),
                ];
            });

            return response()->json([
                'status'  => true,
                'message' => 'Success',
                'data'    => $formatted,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'An unexpected error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    // SHOW SINGLE DEPARTMENT
    public function show($id)
    {
        try {
            $dept = Department::with(['branch', 'users'])->find($id);
            if (!$dept) {
                return response()->json(['status' => false, 'message' => 'Department not found'], 404);
            }

            return response()->json([
                'status'  => true,
                'message' => 'Success',
                'data'    => [
                    'id'        => $dept->id,
                    'name'      => $dept->name,
                    'branch_id' => (int) $dept->branch_id,
                    'branch_name' => $dept->branch ? $dept->branch->name : 'Unknown',
                    'employees' => $dept->users->map(fn($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email]),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'An unexpected error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    // CREATE DEPARTMENT
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name'      => 'required|string|max:255',
                'branch_id' => 'nullable|exists:branches,id',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
            }

            $dept = Department::create([
                'name'      => $request->name,
                'branch_id' => $request->branch_id,
            ]);

            $dept->load(['branch', 'users']);

            return response()->json([
                'status'  => true,
                'message' => 'Successfully created department',
                'data'    => [
                    'id'        => $dept->id,
                    'name'      => $dept->name,
                    'branch_id' => (int) $dept->branch_id,
                    'branch_name' => $dept->branch ? $dept->branch->name : 'Unknown',
                    'employees_count' => $dept->users->count(),
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'An unexpected error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    // UPDATE DEPARTMENT
    public function update(Request $request, $id)
    {
        try {
            $dept = Department::find($id);
            if (!$dept) {
                return response()->json(['status' => false, 'message' => 'Department not found'], 404);
            }

            $validator = Validator::make($request->all(), [
                'name'      => 'string|max:255',
                'branch_id' => 'nullable|exists:branches,id',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
            }

            if ($request->has('name')) $dept->name = $request->name;
            if ($request->has('branch_id')) $dept->branch_id = $request->branch_id;
            
            $dept->save();
            $dept->load(['branch', 'users']);

            return response()->json([
                'status'  => true,
                'message' => 'Department updated successfully',
                'data'    => [
                    'id'        => $dept->id,
                    'name'      => $dept->name,
                    'branch_id' => (int) $dept->branch_id,
                    'branch_name' => $dept->branch ? $dept->branch->name : 'Unknown',
                    'employees_count' => $dept->users->count(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'An unexpected error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    // DELETE DEPARTMENT
    public function destroy($id)
    {
        try {
            $dept = Department::find($id);
            if (!$dept) {
                return response()->json(['status' => false, 'message' => 'Department not found'], 404);
            }

            $dept->delete();

            return response()->json([
                'status'  => true,
                'message' => 'Department deleted successfully',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => 'An unexpected error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }
}
