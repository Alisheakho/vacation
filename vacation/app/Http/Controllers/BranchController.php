<?php

namespace App\Http\Controllers;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
class BranchController extends Controller
{
public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name'       => 'required|string|max:255',
                'manager_id' => 'nullable|exists:users,id',
            ]);

            $branch = Branch::create([
                'name'       => $request->name,
                'manager_id' => $request->manager_id,
            ]);
            return response()->json([
                'status'  => 201,
                'success' => true,
                'message' => 'sucsfull crated branch',
                'data'    => $branch,
            ], 201);

        }  catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'status' => 422,
            'message' => 'Validation error',
            'errors' => $e->errors(),
        ], 422);
    } catch (\Illuminate\Database\QueryException $e) {
        return response()->json([
            'status' => 500,
            'message' => 'Database error occurred: ' . $e->getMessage(),
        ], 500);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 500,
            'message' => 'An unexpected error occurred: ' . $e->getMessage(),
        ], 500);
    }
    }
}
