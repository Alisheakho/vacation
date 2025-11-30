<?php

use Illuminate\Support\Facades\Route;
use App\Models\User;
use App\Events\SendMessage; // تأكد ان اسم ملف الايفنت صح SendMessage

// صفحة التجربة
Route::get('/test-jwt', function () {
    // 1. نجيب يوزر للتجربة
    $user = User::firstOrCreate(
        ['email' => 'jwt@test.com'],
        ['name' => 'JWT User', 'password' => bcrypt('123456')]
    );

    // 2. نطلع توكن حقيقي (حسب مكتبتك)
    $token = auth('api')->login($user);

    // 3. نرجع صفحة فيها التوكن جاهز
    return view('jwt_test_view', ['token' => $token, 'id' => $user->id]);
});

// رابط إرسال الرسالة
Route::get('/send-jwt-msg/{id}', function ($id) {
    // إرسال مباشر للآيدي المكتوب في الرابط
    \App\Events\SendMessage::dispatch("تجربة رسالة للآيدي " . $id, $id);
    return "تم الإرسال للمستخدم رقم: " . $id;
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