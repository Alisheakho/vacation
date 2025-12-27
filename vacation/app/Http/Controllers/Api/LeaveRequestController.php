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
 // تأكد من وجود هذا الاستيراد فوق الكلاس

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

        // تجهيز الموظف الحالي
        $user = Auth::user();

        // 🛑 خطوة أمان
        if (!$user->branch_id) {
            return response()->json(['message' => 'عذراً، أنت غير مسجل في أي فرع حالياً'], 400);
        }

        // ---------------------------------------------------------
        // 2. حساب الأيام 🧮
        // ---------------------------------------------------------
        $start = \Carbon\Carbon::parse($request->start_date);
        $end   = \Carbon\Carbon::parse($request->end_date);
        $daysCount = 0;

        while ($start->lte($end)) {
            if (!$start->isFriday() && !$start->isSaturday()) {
                $daysCount++;
            }
            $start->addDay();
        }

        // ---------------------------------------------------------
        // 3. جلب المدير وتوكناته
        // ---------------------------------------------------------
        $branch = \App\Models\Branch::find($user->branch_id);
        
        if (!$branch) {
            throw new \Exception("الفرع غير موجود");
        }

        $managerId = $branch->manager_id;
        
        $managerTokens = [];
        if ($managerId) {
            $managerTokens = \App\Models\DeviceToken::where('user_id', $managerId)
                ->pluck('token')
                ->toArray();
        }

        // ---------------------------------------------------------
        // 4. إنشاء الطلب 💾
        // ---------------------------------------------------------
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

        // ---------------------------------------------------------
        // 5. الإشعارات 🔔 (داخل try فرعي عشان ما توقف الطلب لو فشلت)
        // ---------------------------------------------------------
        try {
            // Pusher
            if ($managerId) {
                NewLeaveRequest::dispatch($leaveRequest, $managerId);
            }

            // FCM
            if (!empty($managerTokens)) {
                $fcm->sendToTokens(
                    $managerTokens,
                    'طلب إجازة جديد 📝',
                    'الموظف ' . $user->name . ' طلب إجازة لمدة ' . $daysCount . ' أيام.',
                    ['screen' => 'home', 'request_id' => $leaveRequest->id]
                );
            }
        } catch (\Exception $e) {
            // تم تجاهل خطأ الإشعارات كما طلبت (بدون لوج)
        }

        return response()->json([
            'message' => 'تم تقديم الطلب بنجاح',
            'data'    => $leaveRequest
        ]);

    } catch (ValidationException $e) {
        // خطأ في المدخلات (422)
        return response()->json([
            'message' => 'بيانات المدخلات غير صحيحة',
            'errors'  => $e->errors()
        ], 422);

    } catch (\Exception $e) {
        // خطأ عام في السيرفر (500)
        return response()->json([
            'message' => 'حدث خطأ أثناء معالجة الطلب',
            'error'   => $e->getMessage(),
            'line'    => $e->getLine()
        ], 500);
    }
}
 public function getNotifications()
    {
        $user = Auth::user();
        
        // 1. نعرف هل هذا المستخدم هو "مدير" لفرع معين؟
        // بنجيب أرقام الفروع التي يديرها هذا المستخدم
        $managedBranchIds = \App\Models\Branch::where('manager_id', $user->id)->pluck('id');
        
        $notifications = [];
        
        if ($managedBranchIds->isNotEmpty()) {
            // 2. إذا كان مدير، نجيب كل الطلبات اللي جاية على فروعه
          $requests = LeaveRequest::whereIn('branch_id', $managedBranchIds)
            ->with('user')
            ->where('status', 'under_review')
            
            // 👇👇 الإضافة السحرية هنا 👇👇
            // جيب الطلبات اللي تاريخ إنشائها بعد تاريخ آخر مسح (أو جيب الكل لو ما مسح من قبل)
            ->where(function($query) use ($user) {
                if ($user->last_notification_clear_date) {
                    $query->where('created_at', '>', $user->last_notification_clear_date);
                }
            })
            // 👆👆 ------------------ 👆👆
            
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();
                                    
            // 3. تحويل شكل البيانات لتناسب المودل بـ Flutter
            // NotificationModel({required String message, required String date})
            $notifications = $requests->map(function($request) {
                return [
                    'message' => "الموظف {$request->user->name} طلب إجازة لمدة {$request->days} أيام.",
                    'date'    => $request->created_at->toDateTimeString(), // عشان البارسينج بـ فلاتر
                    'id'      => $request->id, // مفيد لو حبيت تفتح التفاصيل
                ];
            });
        }
        
        return response()->json($notifications);
    }
    public function clearNotifications()
{
    $user = Auth::user();
    // تحديث تاريخ آخر مسح بالوقت الحالي
    $user->last_notification_clear_date = now();
    $user->save();
    
    return response()->json(['message' => 'History cleared successfully']);
}
}
