<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeviceTokenController extends Controller
{
    public function store(Request $request)
    {
        try {
            // 1. التحقق من البيانات
            $request->validate([
                'token'       => 'required|string',
                'platform'    => 'nullable|string',
                'device_name' => 'nullable|string',
            ]);

            $user = $request->user();

            // 👇👇👇 التعديل الجديد هنا 👇👇👇
            // 2. تنظيف التوكن من المستخدمين الآخرين
            if ($user) {
                // نحذف أي سجل يحمل نفس التوكن ولكن يتبع لمستخدم آخر
                DeviceToken::where('token', $request->token)
                    ->where('user_id', '!=', $user->id) // شرط: لا تحذفه إذا كان لنفس المستخدم
                    ->delete();
            }
            // 👆👆👆

            // 3. الحفظ أو التحديث للمستخدم الحالي
            // بما أننا نظفنا التوكن من غيرنا، updateOrCreate رح تلاقيه فاضي وتعمل جديد، 
            // أو تلاقيه تبعنا (إذا كنا مسجلين دخول من قبل) وتحدث التاريخ.
            $deviceToken = DeviceToken::updateOrCreate(
                ['token' => $request->token], // البحث بالتوكن
                [
                    'user_id'      => $user?->id, // ربطه بالمستخدم الحالي
                    'platform'     => $request->platform,
                    'device_name'  => $request->device_name,
                    'last_used_at' => now(),
                ]
            );

            return response()->json([
                'status'          => 'ok',
                'device_token_id' => $deviceToken->id,
                'message'         => 'Token updated successfully and decoupled from old users.'
            ], 200);

        } catch (\Throwable $e) {
            
            Log::error("FCM Token Store Error: " . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Server Error: Unable to save token.',
            ], 500);
        }
    }

    // دالة destroy تبقى كما هي...
    public function destroy(Request $request)
    {
        try {
            $request->validate([
                'token' => 'required|string',
            ]);

            $user = $request->user();

            DeviceToken::where('token', $request->token)
                ->where('user_id', $user?->id)
                ->delete();

            return response()->json(['status' => 'deleted'], 200);

        } catch (\Throwable $e) {
            Log::error("FCM Token Delete Error: " . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Server Error: Unable to delete token.',
            ], 500);
        }
    }
}