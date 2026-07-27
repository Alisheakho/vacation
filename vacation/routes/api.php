<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\Api\UserManagementController;
use App\Http\Controllers\Api\LeaveRequestController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Services\FcmService;

// ================================
// 🔓 Public Routes (بدون توكن)
// ================================
Route::post('login', [AuthController::class, 'login']);

// ================================
// 🔐 Authenticated Routes (بتوكن)
// ================================
Route::middleware(['check.token'])->group(function () {

    // ---- عام ----
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refreshToken', [AuthController::class, 'refreshToken']);

    // ================================
    // 👤 إدارة المستخدمين
    // ================================

    // Admin: يقدر ينشئ أي حساب (بما فيه dept_manager)
    Route::middleware(['permission:leave.manage'])->group(function () {
        Route::post('admin/Auth/register', [AuthController::class, 'register']);
    });

    // HR: ينشئ حسابات (employee, branch_manager) بس
    Route::middleware(['role:hr'])->group(function () {
        Route::post('hr/register', [AuthController::class, 'register']);
    });

    // dept_manager: ينشئ كل شي ماعدا admin
    Route::middleware(['role:dept_manager'])->group(function () {
        Route::post('dept-manager/register', [AuthController::class, 'register']);
    });

    // ---- إدارة المستخدمين (عرض/تعديل/حظر/حذف) ----
    // متاح لـ admin + dept_manager + hr
    Route::middleware(['role:admin|dept_manager|hr'])->prefix('users')->group(function () {
        Route::get('/', [UserManagementController::class, 'index']);          // عرض الكل
        Route::get('/{id}', [UserManagementController::class, 'show']);       // عرض واحد
        Route::put('/{id}', [UserManagementController::class, 'update']);     // تعديل
        Route::delete('/{id}', [UserManagementController::class, 'destroy']); // حذف نهائي
        Route::post('/{id}/ban', [UserManagementController::class, 'ban']);    // حظر
        Route::post('/{id}/unban', [UserManagementController::class, 'unban']); // إلغاء حظر
        Route::post('/{id}/change-role', [UserManagementController::class, 'changeRole']); // تغيير رول
    });

    // ================================
    // 🏢 إدارة الفروع
    // ================================
    Route::middleware(['role:admin|dept_manager|hr'])->prefix('branches')->group(function () {
        Route::get('/', [BranchController::class, 'index']);                   // عرض الكل
        Route::get('/{id}', [BranchController::class, 'show']);                // عرض واحد
        Route::post('/', [BranchController::class, 'store']);                   // إنشاء فرع
        Route::put('/{id}', [BranchController::class, 'update']);              // تعديل فرع
        Route::delete('/{id}', [BranchController::class, 'destroy']);          // حذف فرع
        Route::post('/{id}/assign-manager', [BranchController::class, 'assignManager']);     // تعيين مدير
        Route::post('/transfer-manager', [BranchController::class, 'transferManager']);       // نقل مدير
        Route::post('/{id}/remove-manager', [BranchController::class, 'removeManager']);     // إزالة مدير
    });

    // ================================
    // 📝 الإجازات
    // ================================
    Route::post('/leaves', [LeaveRequestController::class, 'store']);
    Route::post('/leaves/{id}/status', [LeaveRequestController::class, 'updateStatus']);

    // ================================
    // 📱 Device Tokens
    // ================================
    Route::post('/device-token', [DeviceTokenController::class, 'store']);
    Route::delete('/device-token', [DeviceTokenController::class, 'destroy']);

    // ================================
    // 🔔 الإشعارات
    // ================================
    Route::get('/notifications', [LeaveRequestController::class, 'getNotifications']);
    Route::post('/notifications/clear', [LeaveRequestController::class, 'clearNotifications']);

    // ================================
    // 📲 FCM Test
    // ================================
    Route::post('/notify-me', function (Request $request, FcmService $fcm) {
        $user = $request->user();

        $res = $fcm->sendToUser(
            $user,
            $request->input('title', 'تنبيه'),
            $request->input('body', 'وصلتك رسالة جديدة'),
            $request->input('data', [])
        );

        return response()->json($res);
    });
});

// ================================
// 🧪 Test Routes
// ================================
Route::get('/test-fcm', function (FcmService $fcm) {
    $token = 'eJbLewJ0RLq38Za9aNIkiu:APA91bETPWXDltGV0kSamoeIFBkv5-bfjwgZEEQbXV_952cWYkxJaI36nTUdyZDAoohG7d7AQy4zsLL-XYjwK-lq3_k1XSJNA8Eg321eWgoexeY6oytPn90';

    return $fcm->sendToTokens(
        [$token],
        'Test from Laravel HTTP v1',
        'هالرسالة جاية من Laravel → FCM v1 😎',
        ['screen' => 'home']
    );
});

Route::get('/debug-path', function () {
    return base_path(config('services.firebase.credentials'));
});