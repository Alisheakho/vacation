<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;

use App\Http\Controllers\BranchController;



  Route::post('login', [AuthController::class,'login']); 

 Route::middleware(['check.token',/*  'role:admin' */])->group(function () {
    /////////////////////////General///////////////////////////////////
       Route::post('logout', [AuthController:: class,'logout']);
       /////////////////////////////////////////////////////////////////
//////////////////////////admin Route ////////////////////////////////////////
Route::group(['middleware'=>['permission:leave.manage'],'prefix' => 'admin'],function(){
    Route::prefix('Auth')->group(function () {
        Route::post('register', [AuthController::class,'register']);
    });}   );
    Route::post('/branches', [BranchController::class, 'store']);
 
}); 
/////////////////////////////////////////////////////////////////////////////////

    Route::post('register', [AuthController::class,'register']);








use App\Http\Controllers\Api\LeaveRequestController;

Route::middleware('auth:api')->group(function () {
    Route::post('/leaves', [LeaveRequestController::class, 'store']);
});





/* Route::middleware(['auth:api', 'permission:leave.manage'])->get('/test', function () {
    return auth()->user()->getRoleNames();
}); */

