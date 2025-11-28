<?php

namespace App\Http\Controllers;
use App\Models\User;

use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash; 
class AuthController extends Controller
{

 public function register(Request $request)
{
    try {

        // -----------------------------
        // 1) Validation
        // -----------------------------
     $validated = $request->validate([
    'name'     => 'required|string|max:255',
    'email'    => 'required|email|unique:users,email',
    'password' => 'required|confirmed',
    'role'     => 'required|in:employee,branch_manager,hr,dept_manager',

    // موظف → لازم يرسل branch_id
    'branch_id'     => 'required_if:role,employee|nullable|exists:branches,id',

    // رئيس فرع و HR → لازم يرسل branch.name
    'branch.name'   => 'required_if:role,branch_manager,hr|string|max:255',
    'branch.code'   => 'nullable|string|max:10',

    // مدير إدارة → ممنوع يرسل branch
    'branch'        => 'prohibited_if:role,dept_manager',
]);


        // -----------------------------
        // 2) Run inside a transaction
        // -----------------------------
        return \DB::transaction(function () use ($validated) {

            // 3) أنشئ المستخدم
            $user = \App\Models\User::create([
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'password' => bcrypt($validated['password']),
                'annual_balance' => 30,
            ]);

            // 4) سلوك بحسب الرول
          if ($validated['role'] === 'employee') {

    // موظف → لازم يربط بفرع
    $user->branch_id = $validated['branch_id'];
    $user->save();

} elseif (in_array($validated['role'], ['branch_manager', 'hr'])) {

    // مدير فرع أو HR → إنشاء فرع جديد
    $branch = Branch::firstOrCreate(
        ['name' => $validated['branch']['name']],
        ['code' => $validated['branch']['code'] ?? null]
    );

    // إذا كان الفرع جديد → خليه يصير المدير
    if ($branch->wasRecentlyCreated) {
        $branch->manager_id = $user->id;
        $branch->save();
    }

    // اربط اليوزر بالفرع
    $user->branch_id = $branch->id;
    $user->save();

} elseif ($validated['role'] === 'dept_manager') {

    // مدير إدارة → لا فرع جديد ولا فرع موجود
    // يبقى branch_id = null
}


            // 5) Assign role
            $user->assignRole($validated['role']);

            // 6) Final response
            return response()->json([
                'success' => true,
                'message' => 'User registered successfully.',
                'data' => $user
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
    try{
       $validateData = $request->validate([
           'email' => 'required|string|email|max:255',
           'password' => 'required|string|min:6',
       ]);

       $user = User::where('email', $validateData['email'])->first();

       if (!$user || !Hash::check($validateData['password'], $user->password)) {
           return response()->json(['error' => 'Invalid credentials'], 401);
       }


       $token = JWTAuth::fromUser($user);


       if(1==1){
 return response()->json([
    'user' => [

        'name' => $user->name,
        'email' => $user->email,
 
    ],
/*     $user->role =>[
     'gander' =>  $additionalData->gander,
     'phoneNumber' => $additionalData->phoneNumber,
     'region' => $additionalData->region->name,
     'is_banned' => $additionalData->is_banned,
     'specialization'=>$additionalData->specialization->name,
    ], */
    'token' => $token,
    'status'=>200

]);
       }
else
return $this->getStudentDataForUi($user, $token);
    }
    catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'status' => 422,
            'message' => 'Validation error',
            'errors' => $e->errors(),
        ], 422);
    } catch (\Illuminate\Database\QueryException $e) {
        return response()->json([
            'status' => 500,
            'message' => 'Database error occurred: ' . $e->getMessage(),
        ], 500);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 500,
            'message' => 'An unexpected error occurred: ' . $e->getMessage(),
        ], 500);
    }

   }

   public function me()
   {
       return response()->json(auth()->user());
   }


   public function logout()
{
    try {

        $token = JWTAuth::getToken();
        JWTAuth::invalidate($token); // تعطيل التوكن لجعله غير صالح

        return response()->json(['message' => 'Successfully logged out','status'=>200], 200);
    } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {

        return response()->json(['error' => 'Token is already invalid'], 401);
    } catch (\Exception $e) {

        return response()->json(['error' => 'Could not log out, please try again.'], 500);
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
