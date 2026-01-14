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
    Route::post('/leaves/{id}/status', [LeaveRequestController::class, 'updateStatus']);

});





/* Route::middleware(['auth:api', 'permission:leave.manage'])->get('/test', function () {
    return auth()->user()->getRoleNames();
}); */

// routes/api.php

use App\Http\Controllers\Api\DeviceTokenController;
use App\Services\FcmService;


// غيّر 'jwt.auth' للي عندك لو مختلف (مثلاً auth:api)
Route::middleware(['auth:api'])->group(function () {
    Route::post('/device-token', [DeviceTokenController::class, 'store']);
    Route::delete('/device-token', [DeviceTokenController::class, 'destroy']);

    // مثال: الإشعار يروح للمستخدم الحالي
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

Route::get('/test-fcm', function (FcmService $fcm) {
    $token = 'eJbLewJ0RLq38Za9aNIkiu:APA91bETPWXDltGV0kSamoeIFBkv5-bfjwgZEEQbXV_952cWYkxJaI36nTUdyZDAoohG7d7AQy4zsLL-XYjwK-lq3_k1XSJNA8Eg321eWgoexeY6oytPn90
'; // بدون \n لو تقدر

    return $fcm->sendToTokens(
        [$token],
        'Test from Laravel HTTP v1',
        'هالرسالة جاية من Laravel → FCM v1 😎',
        ['screen' => 'home'] // انتبه: map (فيها key => value)
    );
});


Route::get('/debug-path', function () {
    return base_path(config('services.firebase.credentials'));
});
Route::get('/notifications', [LeaveRequestController::class, 'getNotifications']);
Route::post('/notifications/clear', [LeaveRequestController::class,'clearNotifications']);