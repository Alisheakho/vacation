<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
use App\Http\Controllers\LeaveRequestController;

Route::middleware(['auth', 'role:employee'])->group(function () {
    Route::get('/leaves/create', [LeaveRequestController::class, 'create'])
        ->name('leaves.create');
    Route::post('/leaves', [LeaveRequestController::class, 'store'])
        ->name('leaves.store');
});

Route::middleware(['auth', 'role:branch_manager'])->group(function () {
    Route::get('/leaves/pending/short', [LeaveRequestController::class, 'pendingShort'])
        ->name('leaves.pending.short');
});

Route::middleware(['auth', 'role:hr'])->group(function () {
    Route::get('/leaves/pending/hr', [LeaveRequestController::class, 'pendingHr'])
        ->name('leaves.pending.hr');
});
Route::middleware(['auth', 'permission:leave.approve.short'])->group(function () {
    // راوتات الموافقة على الإجازات القصيرة
});
