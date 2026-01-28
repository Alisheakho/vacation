<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Events\NewLeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\FcmService;
use Illuminate\Validation\ValidationException;
// 👇 استيراد الريسورس
use App\Http\Resources\LeaveRequestResource;

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

            // 3. جلب المدير (لأغراض التنبيهات)
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

            // 5. الإشعارات وتجهيز الرد
            try {
                // 👇👇 هام جداً: تحميل العلاقات المتداخلة (User + Branch + Manager) 👇👇
                // عشان الريسورس يشتغل صح ويرجع الفرع وجواته المدير
                $leaveRequest->load(['user', 'branch.manager']); 

                // Pusher
                if ($managerId) {
                    // نرسل الريسورس نفسه بالبوشر لتوحيد الداتا
                    NewLeaveRequest::dispatch($leaveRequest, $managerId);
                }

                // FCM
                if (!empty($managerTokens)) {
                    $fcm->sendToTokens(
                        $managerTokens,
                        'طلب إجازة جديد 📝',
                        "الموظف {$user->name} طلب إجازة {$daysCount} أيام.",
                        [
                            'screen' => 'home',
                            'request_id' => $leaveRequest->id,
                        ]
                    );
                }
            } catch (\Exception $e) {
                // Ignored
            }

            return response()->json([
                'message' => 'تم تقديم الطلب بنجاح',
                // 👇 استخدام الريسورس الجديد
                'data'    => new LeaveRequestResource($leaveRequest)
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

        // 1. جلب أرقام الفروع التي يديرها هذا المستخدم
        $managedBranchIds = \App\Models\Branch::where('manager_id', $user->id)->pluck('id');

        // 2. الاستعلام
        // 👇👇 هام جداً: تحميل العلاقات (User + Branch + Manager) مع القائمة 👇👇
        $requests = \App\Models\LeaveRequest::with(['user', 'branch.manager'])
            ->where(function ($query) use ($user, $managedBranchIds) {
                
                // أ: طلباتي
                $query->where('user_id', $user->id);

                // ب: طلبات فروعي (للمدير)
                if ($managedBranchIds->isNotEmpty()) {
                    $query->orWhereIn('branch_id', $managedBranchIds);
                }
            })
            ->where(function($query) use ($user) {
                if ($user->last_notification_clear_date) {
                    $query->where('created_at', '>', $user->last_notification_clear_date);
                }
            })
            ->orderBy('created_at', 'desc')
            ->get();

        // 👇 استخدام الكولكشن (للقوائم)
        return response()->json(LeaveRequestResource::collection($requests));
    }

    public function clearNotifications()
    {
        $user = Auth::user();
        $user->last_notification_clear_date = now();
        $user->save();
        return response()->json(['message' => 'History cleared successfully']);
    }

    public function updateStatus(Request $request, $id, FcmService $fcm)
    {
        try {
            $request->validate([
                'status'     => 'required|in:approved,rejected', 
                'admin_note' => 'nullable|string', 
            ]);

            $manager = Auth::user();

            // 👇 هام جداً: جلب الطلب مع العلاقات (User + Branch + Manager)
            $leaveRequest = LeaveRequest::with(['user', 'branch.manager'])->find($id);

            if (!$leaveRequest) {
                return response()->json(['message' => 'الطلب غير موجود'], 404);
            }

            // التحقق من الصلاحية (نتأكد من أن المدير هو مدير الفرع فعلاً)
            // بما أننا جبنا العلاقة، ممكن نستخدم $leaveRequest->branch مباشرة
            if (!$leaveRequest->branch || $leaveRequest->branch->manager_id !== $manager->id) {
                return response()->json(['message' => 'عذراً، ليس لديك صلاحية للرد على هذا الطلب'], 403);
            }

            // التحديث
            $leaveRequest->status = $request->status;
            $leaveRequest->admin_notes = $request->admin_note;
            $leaveRequest->save();

            // الإشعارات
            try {
                \App\Events\NewLeaveRequest::dispatch($leaveRequest, $leaveRequest->user_id, 'updated');

                $employeeTokens = \App\Models\DeviceToken::where('user_id', $leaveRequest->user_id)
                    ->pluck('token')
                    ->toArray();

                if (!empty($employeeTokens)) {
                    $statusText = $request->status == 'approved' ? 'الموافقة على' : 'رفض';
                    $emoji = $request->status == 'approved' ? '✅' : '❌';
                    
                    $fcm->sendToTokens(
                        $employeeTokens,
                        "تم تحديث حالة طلبك $emoji",
                        "قام المدير بـ $statusText طلب الإجازة الخاص بك.",
                        [
                            'screen' => 'request_details',
                            'request_id' => $leaveRequest->id
                        ]
                    );
                }
            } catch (\Exception $e) {
                // Ignored
            }

            return response()->json([
                'message' => 'تم تحديث حالة الطلب بنجاح',
                // 👇 استخدام الريسورس
                'data'    => new LeaveRequestResource($leaveRequest)
            ]);

        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'حدث خطأ', 'error' => $e->getMessage()], 500);
        }
    }
}