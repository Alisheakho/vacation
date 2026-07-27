<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Branch;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash; 
use App\Models\DeviceToken;
class AuthController extends Controller
{

 public function register(Request $request)
{
    try {
        $currentUser = auth('api')->user();

        // التحقق من الصلاحيات لإنشاء الحسابات
        $requestedRole = $request->input('role');

        // === قاعدة 1: فقط الأدمن يقدر يعمل حساب مدير إدارة ===
        if ($requestedRole === 'dept_manager') {
            if (!$currentUser || !$currentUser->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'فقط الأدمن يستطيع إنشاء حساب مدير إدارة',
                ], 403);
            }
        }

        // === قاعدة 2: HR يقدر يعمل employee و branch_manager بس ===
        if ($currentUser && $currentUser->hasRole('hr')) {
            if (!in_array($requestedRole, ['employee', 'branch_manager'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'HR يمكنه فقط إنشاء حسابات موظفين ورؤساء فروع',
                ], 403);
            }
        }

        // === قاعدة 3: مدير الإدارة يقدر يعمل كل شي ما عدا admin ===
        if ($currentUser && $currentUser->hasRole('dept_manager')) {
            if ($requestedRole === 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'مدير الإدارة لا يستطيع إنشاء حساب أدمن',
                ], 403);
            }
        }

        // -----------------------------
        // 1) Validation
        // -----------------------------
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'employee_id'    => 'required|unique:users,employee_id',
            'password' => 'required|confirmed',
            'role'     => 'required|in:employee,branch_manager,hr,dept_manager',
            'section' => 'nullable|string|max:255',
            'jobe_title' => 'nullable|string|max:255',
            // أ) الموظف: ينضم لفرع موجود
            'branch_id' => 'required_if:role,employee|nullable|exists:branches,id',

            // ب) المدير ورئيس الفرع: اسم الفرع الجديد
            'branch.name' => 'required_if:role,branch_manager,hr|string|max:255',
            'branch.code' => 'nullable|string|max:10',

            // ج) رئيس الفرع: رقم الفرع الأب (عشان نربط الفرع الجديد تحته، وعشان نحط رئيس الفرع فيه)
            'parent_branch_id' => 'required_if:role,branch_manager|nullable|exists:branches,id',
        ]);

        // -----------------------------
        // 2) Transaction
        // -----------------------------
        return \DB::transaction(function () use ($validated) {

            // 3) إنشاء المستخدم
            $user = \App\Models\User::create([
                'name'           => $validated['name'],
                'password'       => bcrypt($validated['password']),
                'annual_balance' => 30,
                'employee_id'    => $validated['employee_id'],
                'section'        => $validated['section'] ?? null,
                'jobe_title'     => $validated['jobe_title']??null,
            ]);

            // 4) المعالجة حسب الرتبة
            if ($validated['role'] === 'dept_manager') {

                // --- مدير الإدارة ---
                // 1. ينشئ الفرع الرئيسي (بدون أب)
                $branch = \App\Models\Branch::create([
                    'name'       => $validated['branch']['name'],
                    'parent_id'  => null, 
                    'manager_id' => $user->id, // هو يدير هذا الفرع
                ]);

                // 2. مدير الإدارة يكون بداخل فرعه
                $user->branch_id = $branch->id;
                $user->save();

            } elseif (in_array($validated['role'], ['branch_manager', 'hr'])) {

                // --- رئيس الفرع ---
                // 1. ينشئ الفرع الفرعي (ويربطه بالفرع الأب)
                $newBranch = \App\Models\Branch::create([
                    'name'       => $validated['branch']['name'],
                    'parent_id'  => $validated['parent_branch_id'], // تابع للإدارة
                    'manager_id' => $user->id, // هو يدير الفرع الجديد
                ]);

                // 2. رئيس الفرع نضعه في الفرع الأب
                $user->branch_id = $validated['parent_branch_id']; 
                $user->save();

            } elseif ($validated['role'] === 'employee') {

                // --- الموظف ---
                // ينضم للفرع المحدد
                $user->branch_id = $validated['branch_id'];
                $user->save();
            }

            // 5) Assign Role
            $user->assignRole($validated['role']);

        return response()->json([
    'success' => true,
    'message' => 'User registered successfully.',

    'data'    => new \App\Http\Resources\UserResource($user->load('branch.manager')) 
], 201);
        });

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error occurred',
            'error'   => $e->getMessage(),
        ], 500);
    }
}
public function login(Request $request)
{
    try {
        $validateData = $request->validate([
            'employee_id'    => 'required|string|max:255',
            'password' => 'required|string|min:6',
        ]);

   $user = User::with(['branch.manager']) 
            ->where('employee_id', $validateData['employee_id'])
            ->first();
        if (!$user || !Hash::check($validateData['password'], $user->password)) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        // فحص الحظر
        if ($user->isBanned()) {
            return response()->json([
                'status'  => false,
                'message' => 'تم حظر حسابك. تواصل مع الإدارة.',
            ], 403);
        }

        // إنشاء JWT Token
        $token = JWTAuth::fromUser($user);
$userRole = $user->getRoleNames()->first();
$authData = [
            'token' => $token,
            'user'  => $user,
            'role'  => $userRole,
        ];
        // 👉 الشكل النهائي المتوافق مع Flutter
        return response()->json([
            'status'  => true,
            'message' => 'Login successful',
       'data'    => new \App\Http\Resources\AuthResource($authData),
        ], 200);

    } catch (\Illuminate\Validation\ValidationException $e) {

        return response()->json([
            'status'  => false,
            'message' => 'Validation error',
            'errors'  => $e->errors(),
        ], 422);

    } catch (\Illuminate\Database\QueryException $e) {

        return response()->json([
            'status'  => false,
            'message' => 'Database error occurred',
            'error'   => $e->getMessage(),
        ], 500);

    } catch (\Exception $e) {

        return response()->json([
            'status'  => false,
            'message' => 'Unexpected error occurred',
            'error'   => $e->getMessage(),
        ], 500);

    }
}

   public function me()
   {
       return response()->json(auth()->user());
   }


  public function logout(Request $request)
{
    try {
        // 👇👇 1. طباعة التوكن الواصل للسيرفر في ملف اللوج
        \Log::info('🚀 Logout Request Received');
        \Log::info('📥 Token from App:', ['token' => $request->fcm_token]);

        // 👇👇 2. استخدام filled بدلاً من has (أضمن)
        if ($request->filled('fcm_token')) {
            
            // محاولة الحذف ومعرفة العدد
            $deletedCount = DeviceToken::where('token', $request->fcm_token)->delete();
            
            \Log::info("🗑️ Deleted Rows: " . $deletedCount);

            if ($deletedCount == 0) {
                \Log::warning("⚠️ Token received but NOT found in Database! (Mismatch)");
            }
        } else {
            \Log::error("❌ fcm_token is NULL or Missing!");
        }

        // 3. إبطال الـ JWT
        $token = JWTAuth::getToken();
        JWTAuth::invalidate($token);

        return response()->json(['message' => 'Successfully logged out', 'status' => 200], 200);

    } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
        return response()->json(['error' => 'Token is already invalid'], 401);
    } catch (\Exception $e) {
        \Log::error("🔥 Logout Error: " . $e->getMessage()); // تسجيل الخطأ
        return response()->json(['error' => 'Could not log out'], 500);
    }
}
/*    public function refresh()
   {
       return $this->respondWithToken(auth()->refresh());
   } */
   public function refreshToken(Request $request)
   {
       try {

           $newToken = JWTAuth::refresh(JWTAuth::getToken());

           return response()->json([
               'message' => 'Token refreshed successfully',
               'token' => $newToken,
               'status'=>200
           ], 200);
       } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
           return response()->json(['error' => 'Invalid token'], 401);
       } catch (\Exception $e) {
           return response()->json(['error' => 'Could not refresh token'], 500);
       }
   }

   protected function respondWithToken($token)
   {
       return response()->json([
           'access_token' => $token,
           'token_type' => 'bearer',
           'expires_in' => auth('api')->factory()->getTTL() * 60
       ]);
   }
//functions

}
