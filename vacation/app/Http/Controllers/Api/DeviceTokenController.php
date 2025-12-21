<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log; // 👈 ضروري عشان نسجل الأخطاء

class DeviceTokenController extends Controller
{
    public function store(Request $request)
    {
        try {
            // التحقق من البيانات
            $request->validate([
                'token'       => 'required|string',
                'platform'    => 'nullable|string',
                'device_name' => 'nullable|string',
            ]);

            $user = $request->user();

            // محاولة الحفظ أو التحديث
            $deviceToken = DeviceToken::updateOrCreate(
                ['token' => $request->token],
                [
                    'user_id'      => $user?->id,
                    'platform'     => $request->platform,
                    'device_name'  => $request->device_name,
                    'last_used_at' => now(),
                ]
            );

            return response()->json([
                'status'          => 'ok',
                'device_token_id' => $deviceToken->id,
            ], 200);

        } catch (\Throwable $e) { // \Throwable بيمسك كل أنواع الأخطاء بـ PHP 7+
            
            // 1. سجل الخطأ عندك بالسيرفر (storage/logs/laravel.log)
            Log::error("FCM Token Store Error: " . $e->getMessage());

            // 2. رجع رد للموبايل إنو في مشكلة بس بدون تفاصيل تقنية
            return response()->json([
                'status'  => 'error',
                'message' => 'Server Error: Unable to save token.',
            ], 500);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $request->validate([
                'token' => 'required|string',
            ]);

            $user = $request->user();

            // الحذف
            DeviceToken::where('token', $request->token)
                ->where('user_id', $user?->id)
                ->delete();

            return response()->json(['status' => 'deleted'], 200);

        } catch (\Throwable $e) {
            
            // تسجيل الخطأ
            Log::error("FCM Token Delete Error: " . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Server Error: Unable to delete token.',
            ], 500);
        }
    }
}