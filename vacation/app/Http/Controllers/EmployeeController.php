<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller
{
    // GET ALL
    public function index()
    {
        $employees = User::with(['branch', 'department'])->get();
        // add "0" for missing fields
        $formatted = $employees->map(fn($u) => $this->formatUserForFlutter($u));

        return response()->json([
            'status' => true, 
            'message' => 'Success', 
            'data' => $formatted
        ], 200);
    }

    // CREATE
    public function store(Request $request)
    {
        $request->validate([
            'firstName'      => 'required',
            'lastName'       => 'required',
            'email'          => 'required|email|unique:users',
            'password'       => 'required|min:6',
            'branch_id'      => 'required|numeric',
            'department_id'  => 'nullable|numeric',
            'annual_balance' => 'numeric',
            'role'           => 'string',
        ]);

        $user = User::create([
            'name'           => $request->firstName . ' ' . $request->lastName,
            'email'          => $request->email,
            'password'       => Hash::make($request->password),
            'branch_id'      => $request->branch_id,
            'department_id'  => $request->department_id,
            'annual_balance' => $request->annual_balance ?? 0,
            'role'           => $request->role ?? 'عنصر',
        ]);

        $user->load(['branch', 'department']);

        return response()->json([
            'status' => true, 
            'message' => 'Employee Created', 
            'data' => $this->formatUserForFlutter($user)
        ], 201);
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        try {
            $user = User::find($id);
            
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Employee not found'], 404);
            }

            $validator = Validator::make($request->all(), [
                'firstName'      => 'string',
                'lastName'       => 'string',
                'email'          => 'email|unique:users,email,' . $id,
                'password'       => 'nullable|min:6',
                'branch_id'      => 'numeric',
                'department_id'  => 'nullable|numeric',
                'annual_balance' => 'numeric',
                'role'           => 'string',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
            }

            if ($request->has('firstName') || $request->has('lastName')) {
                $parts = explode(' ', $user->name, 2);
                $first = $request->has('firstName') ? $request->firstName : $parts[0];
                $last = $request->has('lastName') ? $request->lastName : ($parts[1] ?? '');
                $user->name = trim("$first $last");
            }

            if ($request->has('email')) $user->email = $request->email;
            if ($request->filled('password')) $user->password = Hash::make($request->password);
            if ($request->has('branch_id')) $user->branch_id = $request->branch_id;
            if ($request->has('department_id')) $user->department_id = $request->department_id;
            if ($request->has('annual_balance')) $user->annual_balance = $request->annual_balance;
            if ($request->has('role')) $user->role = $request->role;

            $user->save();
            $user->load(['branch', 'department']);

            return response()->json([
                'status' => true,
                'message' => 'Employee updated successfully',
                'data' => $this->formatUserForFlutter($user)
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    // DELETE
    public function destroy($id)
    {
        try {
            $user = User::find($id);
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Employee not found'], 404);
            }

            $user->delete();

            return response()->json([
                'status' => true,
                'message' => 'Employee deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    // SHOW SINGLE
    public function show($id)
    {
        $user = User::with(['branch', 'department'])->find($id);
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Employee not found'], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Success',
            'data' => $this->formatUserForFlutter($user)
        ], 200);
    }

    private function formatUserForFlutter($user)
    {
        // Split name for UI
        $nameParts = explode(' ', $user->name, 2);

        return [
            'id'            => $user->id,
            'userID'        => $user->email,
            'email'         => $user->email,
            
            'annualBalance' => (double) $user->annual_balance,
            'branchID'      => (int) $user->branch_id,
            'branchName'    => $user->branch ? $user->branch->name : "Unknown",
            'departmentID'  => (int) $user->department_id,
            'department'    => $user->department ? $user->department->name : "0", 
            'role'          => $user->role ?? "Employee", 

            // UI Helpers
            'firstName'     => $nameParts[0],
            'lastName'      => $nameParts[1] ?? '',
            'title'         => 'Employee', 
            'phoneNumber'   => '0000000000',
            'birthdate'     => '2000-01-01',
            'gender'        => 0,
            'avatar'        => '',
            'createdAt'     => $user->created_at ? $user->created_at->toDateTimeString() : now()->toDateTimeString(),
            'lastUpdatedAt' => $user->updated_at ? $user->updated_at->toDateTimeString() : now()->toDateTimeString(),
        ];
    }
}
