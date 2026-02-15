<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
    // GET ALL
    public function index()
    {
        $employees = User::all();
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
            'annual_balance' => 'numeric',
        ]);

        $user = User::create([
            'name'           => $request->firstName . ' ' . $request->lastName,
            'email'          => $request->email,
            'password'       => Hash::make($request->password),
            'branch_id'      => $request->branch_id,
            'annual_balance' => $request->annual_balance ?? 0,
        ]);

        return response()->json([
            'status' => true, 
            'message' => 'Employee Created', 
            'data' => $this->formatUserForFlutter($user)
        ], 201);
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
            
            'department'    => "0", 
            'role'          => "0", 

            // UI Helpers
            'firstName'     => $nameParts[0],
            'lastName'      => $nameParts[1] ?? '',
            'title'         => 'Employee', 
            'phoneNumber'   => '0000000000',
            'birthdate'     => '2000-01-01',
            'gender'        => 0,
            'avatar'        => '',
            'createdAt'     => $user->created_at ? $user->created_at->toDateTimeString() : now()->toDateTimeString(),
        ];
    }
}