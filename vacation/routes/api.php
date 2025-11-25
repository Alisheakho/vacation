<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;

/* Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum'); */

  Route::post('login', [AuthController::class,'login']); 
 Route::middleware(['check.token', 'role:admin'])->group(function () {
   Route::post('register', [AuthController::class,'register']);
   
    Route::post('/branches', [BranchController::class, 'store']);
}); 
Route::middleware(['auth:api', 'permission:leave.manage'])->get('/test', function () {
    return auth()->user()->getRoleNames();
});
   Route::post('logout', [AuthController:: class,'logout']);
