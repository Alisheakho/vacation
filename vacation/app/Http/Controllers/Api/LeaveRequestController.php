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

        // 1. جلب أرقام الفروع التي يديرها هذا المستخدم (إن وجد)
        $managedBranchIds = \App\Models\Branch::where('manager_id', $user->id)->pluck('id');

        // 2. الاستعلام الذكي (للجهتين)
        $requests = \App\Models\LeaveRequest::with('user')
            ->where(function ($query) use ($user, $managedBranchIds) {
                
                // أ: جيب الطلبات اللي أنا قدمتها (كموظف)
                $query->where('user_id', $user->id);

                // ب: أو.. إذا كنت مدير، جيب الطلبات اللي جاية للفروع تبعي (ما عدا طلباتي الشخصية عشان ما وافق على نفسي، أو اتركها عادي)
                if ($managedBranchIds->isNotEmpty()) {
                    // نستخدم orWhereIn عشان ندمج الحالتين
                    $query->orWhereIn('branch_id', $managedBranchIds);
                }
            })
            // ❌ حذفنا شرط under_review عشان يظهر المقبول والمرفوض
            // ->where('status', 'under_review') 
            
            // 3. التحقق من تاريخ مسح الإشعارات
            ->where(function($query) use ($user) {
                if ($user->last_notification_clear_date) {
                    $query->where('created_at', '>', $user->last_notification_clear_date);
                }
            })
            // 4. ترتيب تنازلي (الأحدث فوق)
            ->orderBy('created_at', 'desc')
            // جلب آخر 50 إشعار
            ->get();

        return response()->json($requests);
    }

    public function clearNotifications()
    {
        $user = Auth::user();
        $user->last_notification_clear_date = now();
        $user->save();
        return response()->json(['message' => 'History cleared successfully']);
    }
    ////////////////////////////////////
    // أضف هذا الـ use في أعلى الملف
    // use App\Events\LeaveRequestStatusUpdated;

   public function updateStatus(Request $request, $id, FcmService $fcm)
    {
        try {
            // 1. التحقق من المدخلات
            $request->validate([
                'status'     => 'required|in:approved,rejected', 
                'admin_note' => 'nullable|string', 
            ]);

            $manager = Auth::user();

            // 2. جلب الطلب
            $leaveRequest = LeaveRequest::with('user')->find($id);

            if (!$leaveRequest) {
                return response()->json(['message' => 'الطلب غير موجود'], 404);
            }

            // 3. التحقق من الصلاحية
            $branch = \App\Models\Branch::find($leaveRequest->branch_id);
            
            if (!$branch || $branch->manager_id !== $manager->id) {
                return response()->json(['message' => 'عذراً، ليس لديك صلاحية للرد على هذا الطلب'], 403);
            }

            // 4. تحديث حالة الطلب
            $leaveRequest->status = $request->status;
            $leaveRequest->admin_notes = $request->admin_note;
            $leaveRequest->save();

            // 5. الإشعارات 🔔
            try {
                // ✅ التعديل هنا: استخدام نفس الايفنت مع تحديد النوع updated
                // نرسل الطلب + آيدي الموظف + نوع الحدث
                \App\Events\NewLeaveRequest::dispatch($leaveRequest, $leaveRequest->user_id, 'updated');

                // ب: FCM
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
                'data'    => $leaveRequest
            ]);

        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'حدث خطأ', 'error' => $e->getMessage()], 500);
        }
    }
}