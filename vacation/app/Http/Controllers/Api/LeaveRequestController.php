<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Events\NewLeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveRequestController extends Controller
{
    public function store(Request $request)
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

        // 3. تحديد المدير الذي سيستلم الإشعار
        // (للتجربة سنرسل للمستخدم رقم 1، يمكنك تغييرها لاحقاً ليكون مدير الفرع)
        $managerId = 3; 

        // 4. إطلاق الحدث (Real-Time)
        NewLeaveRequest::dispatch($leaveRequest, $managerId);

        return response()->json([
            'message' => 'تم تقديم الطلب بنجاح!',
            'data' => $leaveRequest
        ]);
    }
}