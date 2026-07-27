<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use App\Http\Resources\BranchResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BranchController extends Controller
{
    /**
     * عرض جميع الفروع
     */
    public function index()
    {
        try {
            $branches = Branch::with(['manager', 'parent', 'users'])->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $branches->map(function ($branch) {
                    return [
                        'id' => $branch->id,
                        'name' => $branch->name,
                        'parent_id' => $branch->parent_id,
                        'parent_name' => $branch->parent ? $branch->parent->name : null,
                        'manager' => $branch->manager ? new UserResource($branch->manager) : null,
                        'is_vacant' => $branch->isVacant(),
                        'employees_count' => $branch->users->count(),
                    ];
                }),
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
     * عرض فرع واحد مع تفاصيلة
     */
    public function show($id)
    {
        try {
            $branch = Branch::with(['manager', 'parent', 'users', 'children'])->find($id);

            if (!$branch) {
                return response()->json([
                    'success' => false,
                    'message' => 'الفرع غير موجود',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'parent_id' => $branch->parent_id,
                    'parent_name' => $branch->parent ? $branch->parent->name : null,
                    'manager' => $branch->manager ? new UserResource($branch->manager) : null,
                    'is_vacant' => $branch->isVacant(),
                    'employees_count' => $branch->users->count(),
                    'employees' => UserResource::collection($branch->users),
                    'sub_branches' => $branch->children->map(function ($child) {
                        return [
                            'id' => $child->id,
                            'name' => $child->name,
                            'manager_id' => $child->manager_id,
                            'is_vacant' => $child->isVacant(),
                        ];
                    }),
                ],
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
     * إنشاء فرع جديد
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'parent_id' => 'nullable|exists:branches,id',
                'manager_id' => 'nullable|exists:users,id',
            ]);

            // لو حدد مدير، نتأكد إنو مش مدير فرع ثاني
            if (!empty($validated['manager_id'])) {
                $existingBranch = Branch::where('manager_id', $validated['manager_id'])->first();
                if ($existingBranch) {
                    return response()->json([
                        'success' => false,
                        'message' => 'هذا المستخدم يدير فرع آخر بالفعل: ' . $existingBranch->name,
                    ], 422);
                }
            }

            $branch = Branch::create([
                'name' => $validated['name'],
                'parent_id' => $validated['parent_id'] ?? null,
                'manager_id' => $validated['manager_id'] ?? null,
            ]);

            $branch->load(['manager', 'parent']);

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء الفرع بنجاح',
                'data' => [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'parent_id' => $branch->parent_id,
                    'parent_name' => $branch->parent ? $branch->parent->name : null,
                    'manager' => $branch->manager ? new UserResource($branch->manager) : null,
                    'is_vacant' => $branch->isVacant(),
                ],
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في البيانات',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * تعديل فرع
     */
    public function update(Request $request, $id)
    {
        try {
            $branch = Branch::find($id);

            if (!$branch) {
                return response()->json([
                    'success' => false,
                    'message' => 'الفرع غير موجود',
                ], 404);
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'parent_id' => 'sometimes|nullable|exists:branches,id',
            ]);

            // لا يمكن أن يكون الفرع أب لنفسه
            if (isset($validated['parent_id']) && $validated['parent_id'] == $id) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن أن يكون الفرع تابعاً لنفسه',
                ], 422);
            }

            $branch->update($validated);
            $branch->load(['manager', 'parent']);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الفرع بنجاح',
                'data' => [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'parent_id' => $branch->parent_id,
                    'parent_name' => $branch->parent ? $branch->parent->name : null,
                    'manager' => $branch->manager ? new UserResource($branch->manager) : null,
                    'is_vacant' => $branch->isVacant(),
                ],
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
     * تعيين مدير لفرع (الفرع لازم يكون شاغر)
     */
    public function assignManager(Request $request, $id)
    {
        try {
            $branch = Branch::find($id);

            if (!$branch) {
                return response()->json([
                    'success' => false,
                    'message' => 'الفرع غير موجود',
                ], 404);
            }

            // التأكد أن الفرع شاغر
            if (!$branch->isVacant()) {
                return response()->json([
                    'success' => false,
                    'message' => 'هذا الفرع ليس شاغراً. يجب إزالة المدير الحالي أولاً.',
                    'current_manager_id' => $branch->manager_id,
                ], 422);
            }

            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
            ]);

            $user = User::find($validated['user_id']);

            // تأكد إنو مش مدير فرع ثاني
            $existingBranch = Branch::where('manager_id', $user->id)->first();
            if ($existingBranch) {
                return response()->json([
                    'success' => false,
                    'message' => 'هذا المستخدم يدير فرعاً آخر بالفعل: ' . $existingBranch->name,
                ], 422);
            }

            DB::transaction(function () use ($branch, $user) {
                // عيّن المدير
                $branch->manager_id = $user->id;
                $branch->save();

                // أعطه رول branch_manager لو ما عنده
                if (!$user->hasRole('branch_manager')) {
                    $user->syncRoles(['branch_manager']);
                }
            });

            $branch->load(['manager', 'parent']);

            return response()->json([
                'success' => true,
                'message' => 'تم تعيين المدير بنجاح',
                'data' => [
                    'branch_id' => $branch->id,
                    'branch_name' => $branch->name,
                    'manager' => new UserResource($branch->manager),
                ],
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
     * نقل مدير فرع إلى فرع آخر
     * (شيله من الفرع القديم وحطه بالفرع الجديد)
     */
    public function transferManager(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'to_branch_id' => 'required|exists:branches,id',
            ]);

            $user = User::find($validated['user_id']);
            $toBranch = Branch::find($validated['to_branch_id']);

            // تأكد أن الفرع الجديد شاغر
            if (!$toBranch->isVacant()) {
                return response()->json([
                    'success' => false,
                    'message' => 'الفرع الجديد ليس شاغراً. يجب إزالة المدير الحالي أولاً.',
                ], 422);
            }

            // جد الفرع القديم يلي كان يديره
            $fromBranch = Branch::where('manager_id', $user->id)->first();

            DB::transaction(function () use ($user, $fromBranch, $toBranch) {
                // شيله من الفرع القديم
                if ($fromBranch) {
                    $fromBranch->manager_id = null;
                    $fromBranch->save();
                }

                // حطه بالفرع الجديد
                $toBranch->manager_id = $user->id;
                $toBranch->save();

                // تأكد إنو عنده رول branch_manager
                if (!$user->hasRole('branch_manager')) {
                    $user->syncRoles(['branch_manager']);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'تم نقل المدير بنجاح',
                'data' => [
                    'user' => new UserResource($user->load('branch.manager')),
                    'from_branch' => $fromBranch ? $fromBranch->name : null,
                    'to_branch' => $toBranch->name,
                ],
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
     * إزالة مدير من فرع (وتغيير رولو)
     */
    public function removeManager(Request $request, $id)
    {
        try {
            $branch = Branch::with('manager')->find($id);

            if (!$branch) {
                return response()->json([
                    'success' => false,
                    'message' => 'الفرع غير موجود',
                ], 404);
            }

            if ($branch->isVacant()) {
                return response()->json([
                    'success' => false,
                    'message' => 'هذا الفرع ليس لديه مدير بالفعل',
                ], 422);
            }

            $validated = $request->validate([
                'new_role' => 'required|in:employee,hr',
            ]);

            $manager = $branch->manager;

            DB::transaction(function () use ($branch, $manager, $validated) {
                // شيل المدير من الفرع
                $branch->manager_id = null;
                $branch->save();

                // غيّر رولو
                $manager->syncRoles([$validated['new_role']]);
            });

            return response()->json([
                'success' => true,
                'message' => 'تم إزالة المدير من الفرع وتغيير رولو بنجاح',
                'data' => [
                    'branch_id' => $branch->id,
                    'branch_name' => $branch->name,
                    'removed_manager' => new UserResource($manager->load('branch.manager')),
                    'new_role' => $validated['new_role'],
                ],
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
     * حذف فرع
     */
    public function destroy($id)
    {
        try {
            $branch = Branch::withCount('users')->find($id);

            if (!$branch) {
                return response()->json([
                    'success' => false,
                    'message' => 'الفرع غير موجود',
                ], 404);
            }

            // لا نحذف فرع فيه موظفين
            if ($branch->users_count > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن حذف فرع فيه موظفين. يجب نقل الموظفين أولاً.',
                    'employees_count' => $branch->users_count,
                ], 422);
            }

            // لا نحذف فرع له فروع فرعية
            $childrenCount = Branch::where('parent_id', $id)->count();
            if ($childrenCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن حذف فرع له فروع فرعية. يجب حذف الفروع الفرعية أولاً.',
                    'sub_branches_count' => $childrenCount,
                ], 422);
            }

            $branch->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الفرع بنجاح',
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
