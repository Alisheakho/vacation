<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Events\NewLeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Services\FcmService;
use Illuminate\Validation\ValidationException;

class LeaveRequestController extends Controller
{
    public function store(Request $request, FcmService $fcm)
    {
        try {
            // 1. التحقق من البيانات
            $request->validate([
                'leave_type' => 'required|in:annual,sick,emergency,unpaid,occasion,official',
                'start_date' => 'required|date',
                'end_date'   => 'required|date|after_or_equal:start_date',
                'notes'      => 'nullable|string',
            ]);

            $user = Auth::user();

            if (!$user->branch_id) {
                return response()->json(['message' => 'عذراً، أنت غير مسجل في أي فرع حالياً'], 400);
            }

            // 2. حساب الأيام
            $start = \Carbon\Carbon::parse($request->start_date);
            $end   = \Carbon\Carbon::parse($request->end_date);
            $daysCount = 0;

            while ($start->lte($end)) {
                if (!$start->isFriday() && !$start->isSaturday()) {
                    $daysCount++;
                }
                $start->addDay();
            }

            // 3. جلب المدير
            $branch = \App\Models\Branch::find($user->branch_id);
            if (!$branch) {
                throw new \Exception("الفرع غير موجود");
            }

            $managerId = $branch->manager_id;
            $managerTokens = [];
            if ($managerId) {
                $managerTokens = \App\Models\DeviceToken::where('user_id', $managerId)
                    ->pluck('token')->toArray();
            }

            // 4. إنشاء الطلب
            $leaveRequest = LeaveRequest::create([
                'user_id'    => $user->id,
                'branch_id'  => $user->branch_id,
                'leave_type' => $request->leave_type,
                'start_date' => $request->start_date,
                'end_date'   => $request->end_date,
                'days'       => $daysCount,
                'notes'      => $request->notes,
                'status'     => 'under_review'
            ]);

            // 5. الإشعارات
            try {
                // 👇👇 تعديل هام: تحميل بيانات اليوزر عشان الاسم يوصل بالبوشر 👇👇
                $leaveRequest->load('user'); 

                // Pusher (نبعث المودل كامل)
                if ($managerId) {
                    NewLeaveRequest::dispatch($leaveRequest, $managerId);
                }

                // FCM (نبعث بيانات للعرض + المودل مخفي بالداتا)
                if (!empty($managerTokens)) {
                    $fcm->sendToTokens(
                        $managerTokens,
                        'طلب إجازة جديد 📝',
                        "الموظف {$user->name} طلب إجازة {$daysCount} أيام.",
                        [
                            'screen' => 'home',
                            'request_id' => $leaveRequest->id,
                            // ممكن تبعت المودل هنا كـ سترينج لو حبيت
                            // 'leave_request' => json_encode($leaveRequest) 
                        ]
                    );
                }
            } catch (\Exception $e) {
                // Ignored
            }

            return response()->json([
                'message' => 'تم تقديم الطلب بنجاح',
                'data'    => $leaveRequest
            ]);

        } catch (ValidationException $e) {
            return response()->json(['message' => 'بيانات المدخلات غير صحيحة', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'حدث خطأ', 'error' => $e->getMessage()], 500);
        }
    }

    public function getNotifications()
    {
        $user = Auth::user();
        
        $managedBranchIds = \App\Models\Branch::where('manager_id', $user->id)->pluck('id');
        
        if ($managedBranchIds->isEmpty()) {
            return response()->json([]);
        }

        // جلب الطلبات (الداتا الكاملة)
        $requests = \App\Models\LeaveRequest::whereIn('branch_id', $managedBranchIds)
            ->with('user') // 👈 ضروري عشان اسم الموظف يوصل لفلاتر
            ->where('status', 'under_review')
            ->where(function($query) use ($user) {
                if ($user->last_notification_clear_date) {
                    $query->where('created_at', '>', $user->last_notification_clear_date);
                }
            })
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();
            
        // 👇👇👇 التغيير هنا: رجعنا الداتا كاملة بدون map 👇👇👇
        // هيك فلاتر بيقدر يحولها لـ LeaveRequest ويطلع كل التفاصيل
        return response()->json($requests);
    }

    public function clearNotifications()
    {
        $user = Auth::user();
        $user->last_notification_clear_date = now();
        $user->save();
        return response()->json(['message' => 'History cleared successfully']);
    }
}