<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Events\NewLeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Services\FcmService;
class LeaveRequestController extends Controller
{
    public function store(Request $request, FcmService $fcm)
    {
        // 1. التحقق من البيانات (Validation)
        $validated = $request->validate([
            'branch_id' => 'required|integer', // تأكد أن لديك فرع بهذا الرقم في الداتا بيز
            'leave_type' => 'required|in:annual,sick,emergency,unpaid,occasion,official',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'days' => 'required|integer',
            'notes' => 'nullable|string',
        ]);

        // 2. إنشاء الطلب في قاعدة البيانات
        $leaveRequest = LeaveRequest::create([
            'user_id' => Auth::id(), // الموظف الحالي (من التوكن)
            'branch_id' => $request->branch_id,
            'leave_type' => $request->leave_type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'days' => $request->days,
            'notes' => $request->notes,
            'status' => 'under_review'
        ]);
$token = 'eO6D5rQ3SmefJZSwneQHTb:APA91bFjQ524ZVhmjRyBSUxqvNXUnBd7c89IH3qr9G1qS-GPJupGMgFuBPTpvxuK_DmX0oAR6zObLjlxPOWVmC7LXGrfR9w32XmnfAc7NaRoMNxarSAOkzY
'; // بدون \n لو تقدر

        $managerId = 3; 

        // 4. إطلاق الحدث (Real-Time)
        NewLeaveRequest::dispatch($leaveRequest, $managerId);

        return response()->json([
            $fcm->sendToTokens(
        [$token],
        'Test from Laravel HTTP v1',
        'هالرسالة جاية من Laravel → FCM v1 😎',
        ['screen' => 'home'] // انتبه: map (فيها key => value)
        // 3. تحديد المدير الذي سيستلم الإشعار
            ),
            'message' => 'تم تق        // (للتجربة سنرسل للمستخدم رقم 1، يمكنك تغييرها لاحقاً ليكون مدير الفرع)
ديم الطلب بنجاح!',
            'data' => $leaveRequest
        ]);
    }
}