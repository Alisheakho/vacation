<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BranchController extends Controller
{
    // GET ALL BRANCHES
    public function index()
    {
        try {
            $branches = Branch::with(['departments', 'users'])->get();
            
            // Format for Flutter: map database fields to what Flutter expects
            $formatted = $branches->map(function ($branch) {
                return [
                    'id'               => $branch->id,
                    'code'             => $branch->code,
                    'branchName'       => $branch->name,
                    'branchManagerID'  => (int) $branch->manager_id,
                    'departments_count'=> $branch->departments->count(),
                    'employees_count'  => $branch->users->count(),
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

    // SHOW SINGLE BRANCH
    public function show($id)
    {
        try {
            $branch = Branch::with(['departments', 'users'])->find($id);
            if (!$branch) {
                $branch = Branch::with(['departments', 'users'])->where('code', $id)->first();
            }

            if (!$branch) {
                return response()->json(['status' => false, 'message' => 'Branch not found'], 404);
            }

            return response()->json([
                'status'  => true,
                'message' => 'Success',
                'data'    => [
                    'id'               => $branch->id,
                    'code'             => $branch->code,
                    'branchName'       => $branch->name,
                    'branchManagerID'  => (int) $branch->manager_id,
                    'departments'      => $branch->departments->map(fn($d) => ['id' => $d->id, 'name' => $d->name]),
                    'employees'        => $branch->users->map(fn($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email]),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'An unexpected error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    // CREATE BRANCH
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'branchName'      => 'required|string|max:255',
                'code'            => 'nullable|string|max:50',
                'branchManagerID' => 'nullable|exists:users,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 422,
                    'message' => 'Validation error',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $branch = Branch::create([
                'name'       => $request->branchName,
                'code'       => $request->code,
                'manager_id' => $request->branchManagerID,
            ]);

            $branch->load(['departments', 'users']);

            return response()->json([
                'status'  => 201,
                'success' => true,
                'message' => 'Successfully created branch',
                'data'    => [
                    'id'               => $branch->id,
                    'code'             => $branch->code,
                    'branchName'       => $branch->name,
                    'branchManagerID'  => (int) $branch->manager_id,
                    'departments_count'=> $branch->departments->count(),
                    'employees_count'  => $branch->users->count(),
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => 'An unexpected error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    // UPDATE BRANCH
    public function update(Request $request, $id)
    {
        try {
            $branch = Branch::find($id);
            if (!$branch) {
                $branch = Branch::where('code', $id)->first();
            }

            if (!$branch) {
                return response()->json(['status' => false, 'message' => 'Branch not found'], 404);
            }

            $validator = Validator::make($request->all(), [
                'branchName'      => 'string|max:255',
                'code'            => 'string|max:50',
                'branchManagerID' => 'nullable|exists:users,id',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
            }

            if ($request->has('branchName')) $branch->name = $request->branchName;
            if ($request->has('code')) $branch->code = $request->code;
            if ($request->has('branchManagerID')) $branch->manager_id = $request->branchManagerID;
            
            $branch->save();
            $branch->load(['departments', 'users']);

            return response()->json([
                'status'  => true,
                'message' => 'Branch updated successfully',
                'data'    => [
                    'id'               => $branch->id,
                    'code'             => $branch->code,
                    'branchName'       => $branch->name,
                    'branchManagerID'  => (int) $branch->manager_id,
                    'departments_count'=> $branch->departments->count(),
                    'employees_count'  => $branch->users->count(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => 'An unexpected error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    // DELETE BRANCH
    public function destroy($id)
    {
        try {
            $branch = Branch::find($id);
            if (!$branch) {
                $branch = Branch::where('code', $id)->first();
            }

            if (!$branch) {
                return response()->json(['status' => false, 'message' => 'Branch not found'], 404);
            }

            $branch->delete();

            return response()->json([
                'status'  => true,
                'message' => 'Branch deleted successfully',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => 'An unexpected error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }
}
