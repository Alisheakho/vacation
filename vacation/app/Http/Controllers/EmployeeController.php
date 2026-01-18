<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = User::all();

        $formatted = $employees->map(fn($user) => $this->formatUserForFlutter($user));

        return response()->json([
            'status' => true,
            'message' => 'Success',
            'data' => $formatted
        ], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'firstName' => 'required',
            'lastName' => 'required',
        ]);

        $user = User::create([
            'name' => $request->firstName . ' ' . $request->lastName, // Combine for DB
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Employee Created',
            'data' => $this->formatUserForFlutter($user)
        ], 201);
    }

    private function formatUserForFlutter($user)
    {
        $nameParts = explode(' ', $user->name, 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? '';

        return [
            'id' => $user->id,
            'userID' => $user->email, // Using email as ID for display
            'email' => $user->email,
            'role' => 'employee',

            'firstName'   => $firstName,
            'lastName'    => $lastName,
            'phoneNumber' => '0000000000', // Default if DB doesn't have it
            'title'       => 'Employee',
            'birthdate'   => '2000-01-01',
            'gender'      => 0, // 0 = Male, 1 = Female
            'avatar'      => '',
            
            'createdAt'     => $user->created_at ? $user->created_at->toDateTimeString() : now()->toDateTimeString(),
            'lastUpdatedAt' => $user->updated_at ? $user->updated_at->toDateTimeString() : now()->toDateTimeString(),
        ];
    }
}