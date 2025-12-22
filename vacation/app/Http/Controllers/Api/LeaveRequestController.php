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
    // 1. التحقق من البيانات (حذفنا days و branch_id)
    $validated = $request->validate([
        'leave_type' => 'required|in:annual,sick,emergency,unpaid,occasion,official',
        'start_date' => 'required|date',
        'end_date'   => 'required|date|after_or_equal:start_date',
        'notes'      => 'nullable|string',
    ]);

    // تجهيز الموظف الحالي
    $user = Auth::user();

    // 🛑 خطوة أمان: التأكد أن الموظف مرتبط بفرع أصلاً
    if (! $user->branch_id) {
        return response()->json(['message' => 'عذراً، أنت غير مسجل في أي فرع حالياً'], 400);
    }

    // ---------------------------------------------------------
    // 2. حساب الأيام 🧮
    // ---------------------------------------------------------
    $start = \Carbon\Carbon::parse($request->start_date);
    $end   = \Carbon\Carbon::parse($request->end_date);
    $daysCount = 0;

    while ($start->lte($end)) {
        if (! $start->isFriday() && ! $start->isSaturday()) {
            $daysCount++;
        }
        $start->addDay();
    }

    // ---------------------------------------------------------
    // 3. جلب المدير وتوكناته (من علاقة المستخدم بالفرع) 🕵️‍♂️
    // ---------------------------------------------------------
    // بما أن الموظف عنده branch_id، بنجيب مودل الفرع لنعرف مين مديره
    // يفضل تكون عامل علاقة branch() في مودل User، بس هون رح جيبها يدوي للأمان
    $branch = \App\Models\Branch::find($user->branch_id);
    
    // مدير الفرع
    $managerId = $branch->manager_id;

    // توكنات المدير
    $managerTokens = \App\Models\DeviceToken::where('user_id', $managerId)
        ->pluck('token')
        ->toArray();
     
    // ---------------------------------------------------------
    // 4. إنشاء الطلب 💾
    // ---------------------------------------------------------
    $leaveRequest = LeaveRequest::create([
        'user_id'    => $user->id,
        'branch_id'  => $user->branch_id, // 👈 جبناه من اليوزر نفسه مو من الريكويست
        'leave_type' => $request->leave_type,
        'start_date' => $request->start_date,
        'end_date'   => $request->end_date,
        'days'       => $daysCount,
        'notes'      => $request->notes,
        'status'     => 'under_review'
    ]);

    // ---------------------------------------------------------
    // 5. الإشعارات 🔔
    // ---------------------------------------------------------
    
    // Pusher
    NewLeaveRequest::dispatch($leaveRequest, $managerId);

    // FCM
    if (!empty($managerTokens)) {
        $fcm->sendToTokens(
            $managerTokens,
            'طلب إجازة جديد 📝',
            'الموظف ' . $user->name . ' طلب إجازة لمدة ' . $daysCount . ' أيام.',
            ['screen' => 'home', 'request_id' => $leaveRequest->id]
        );
    }

    return response()->json([
        'message' => 'تم تقديم الطلب بنجاح',
        'data'    => $leaveRequest
    ]);
}
}