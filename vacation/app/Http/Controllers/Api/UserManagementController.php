<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Branch;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    /**
     * عرض جميع المستخدمين مع الفلترة
     */
    public function index(Request $request)
    {
        try {
            $query = User::with(['branch.manager']);

            // فلترة حسب الرول
            if ($request->has('role')) {
                $query->role($request->role);
            }

            // فلترة حسب الفرع
            if ($request->has('branch_id')) {
                $query->where('branch_id', $request->branch_id);
            }

            // فلترة حسب الحظر
            if ($request->has('is_banned')) {
                $query->where('is_banned', $request->boolean('is_banned'));
            }

            // بحث بالاسم أو رقم الموظف
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('employee_id', 'like', "%{$search}%");
                });
            }

            $users = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => UserResource::collection($users),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * عرض مستخدم واحد
     */
    public function show($id)
    {
        try {
            $user = User::with(['branch.manager'])->find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'المستخدم غير موجود',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => new UserResource($user),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * تعديل بيانات المستخدم (اسم، قسم، مسمى وظيفي، فرع، الرصيد، الباسورد)
     */
    public function update(Request $request, $id)
    {
        try {
            $currentUser = auth()->user();
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'المستخدم غير موجود',
                ], 404);
            }

            // لا يمكن تعديل الأدمن إلا من أدمن آخر
            if ($user->hasRole('admin') && !$currentUser->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكنك تعديل بيانات الأدمن',
                ], 403);
            }

            // لا يمكن لـ HR تعديل مدير الإدارة
            if ($user->hasRole('dept_manager') && $currentUser->hasRole('hr')) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن لـ HR تعديل بيانات مدير الإدارة',
                ], 403);
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'employee_id' => 'sometimes|string|unique:users,employee_id,' . $id,
                'section' => 'sometimes|nullable|string|max:255',
                'jobe_title' => 'sometimes|nullable|string|max:255',
                'branch_id' => 'sometimes|nullable|exists:branches,id',
                'annual_balance' => 'sometimes|integer|min:0',
                'password' => 'sometimes|string|min:6|confirmed',
            ]);

            // لو في باسورد جديد
            if (isset($validated['password'])) {
                $validated['password'] = bcrypt($validated['password']);
            }

            $user->update($validated);
            $user->load('branch.manager');

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث بيانات المستخدم بنجاح',
                'data' => new UserResource($user),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في البيانات',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * حظر مستخدم
     */
    public function ban($id)
    {
        try {
            $currentUser = auth()->user();
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'المستخدم غير موجود',
                ], 404);
            }

            // لا يمكن حظر نفسك
            if ($user->id === $currentUser->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكنك حظر نفسك',
                ], 403);
            }

            // لا يمكن حظر الأدمن
            if ($user->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن حظر الأدمن',
                ], 403);
            }

            // HR لا يمكنه حظر مدير الإدارة
            if ($user->hasRole('dept_manager') && $currentUser->hasRole('hr')) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن لـ HR حظر مدير الإدارة',
                ], 403);
            }

            $user->is_banned = true;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'تم حظر المستخدم بنجاح',
                'data' => new UserResource($user->load('branch.manager')),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * إلغاء حظر مستخدم
     */
    public function unban($id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'المستخدم غير موجود',
                ], 404);
            }

            $user->is_banned = false;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'تم إلغاء حظر المستخدم بنجاح',
                'data' => new UserResource($user->load('branch.manager')),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * تغيير رول المستخدم
     */
    public function changeRole(Request $request, $id)
    {
        try {
            $currentUser = auth()->user();
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'المستخدم غير موجود',
                ], 404);
            }

            $validated = $request->validate([
                'role' => 'required|in:employee,branch_manager,hr,dept_manager',
            ]);

            $newRole = $validated['role'];

            // فقط الأدمن يقدر يعمل dept_manager
            if ($newRole === 'dept_manager' && !$currentUser->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'فقط الأدمن يستطيع تعيين رول مدير الإدارة',
                ], 403);
            }

            // HR لا يمكنه تغيير رول مدير الإدارة
            if ($user->hasRole('dept_manager') && $currentUser->hasRole('hr')) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن لـ HR تغيير رول مدير الإدارة',
                ], 403);
            }

            // لا يمكن تغيير رول الأدمن
            if ($user->hasRole('admin') && !$currentUser->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن تغيير رول الأدمن',
                ], 403);
            }

            DB::transaction(function () use ($user, $newRole) {
                // لو كان مدير فرع، نشيله من إدارة الفرع
                if ($user->hasRole('branch_manager') && $newRole !== 'branch_manager') {
                    Branch::where('manager_id', $user->id)->update(['manager_id' => null]);
                }

                // شيل كل الأدوار القديمة وحط الجديد
                $user->syncRoles([$newRole]);
            });

            $user->load('branch.manager');

            return response()->json([
                'success' => true,
                'message' => 'تم تغيير الرول بنجاح',
                'data' => new UserResource($user),
                'new_role' => $newRole,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في البيانات',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * حذف مستخدم نهائياً من النظام
     */
    public function destroy($id)
    {
        try {
            $currentUser = auth()->user();
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'المستخدم غير موجود',
                ], 404);
            }

            // لا يمكن حذف نفسك
            if ($user->id === $currentUser->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكنك حذف نفسك',
                ], 403);
            }

            // لا يمكن حذف الأدمن
            if ($user->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن حذف الأدمن',
                ], 403);
            }

            // HR لا يمكنه حذف مدير الإدارة
            if ($user->hasRole('dept_manager') && $currentUser->hasRole('hr')) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن لـ HR حذف مدير الإدارة',
                ], 403);
            }

            DB::transaction(function () use ($user) {
                // شيل المستخدم من إدارة أي فرع
                Branch::where('manager_id', $user->id)->update(['manager_id' => null]);

                // احذف توكنات الأجهزة
                $user->deviceTokens()->delete();

                // احذف المستخدم
                $user->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'تم حذف المستخدم نهائياً',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
