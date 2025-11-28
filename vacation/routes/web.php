<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Events\SendMessage;
use Illuminate\Http\Request;

// هذا الراوت لإرسال رسالة تجريبية
Route::get('/send-msg', function (Request $request) {
    
    // مثلاً نرسل للمستخدم رقم 1 (تأكد أنه موجود في الداتا بيز)
    $receiverId = 1; 
    $message = "مرحبا! هذه رسالة تجريبية من بوشر.";

    // إطلاق الحدث
    SendMessage::dispatch($message, $receiverId);

    return "تم إرسال الرسالة للمستخدم رقم " . $receiverId;
});
Route::get('/', function () {
    return view('welcome');
});

/* use App\Http\Controllers\LeaveRequestController;

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
 */