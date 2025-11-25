<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;

/* Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum'); */

   Route::post('login', [AuthController::class,'login']);
 Route::middleware(['auth:api', 'role:admin'])->group(function () {
   Route::post('r', [AuthController::class,'r']);
   
    Route::post('/branches', [BranchController::class, 'store']);
});
