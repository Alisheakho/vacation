<?php

namespace App\Http\Controllers;
use App\Models\User;

use Illuminate\Http\Request;

class AuthController extends Controller
{

   public function r(Request $request)
    {
   
     try {
            // Validation
            $validateData = $request->validate([
                "email" => "required|string|max:255|email|unique:users,email",
                "password" => "required|string|min:6|confirmed",
                "name" => "required|string|max:255",
               
            ]);

     

         
            $user = User::create([
                "email" => $validateData["email"],
                "password" => bcrypt($validateData['password']),
                "name" => $validateData['name'],
            ]);

           
            // Generate Token
            $token = auth('api')->login($user);


return response()->json([
    'access_token' => $token,
    'token_type' => 'bearer'
]);

     
        } catch (\Illuminate\Validation\ValidationException $e) {
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


       $additionalData = null;
       if ($user->role === 'teacher') {
           $additionalData = Teacher::where('user_id', $user->id)->first();

       } elseif ($user->role === 'student') {
           $additionalData = Student::where('user_id', $user->id)->first();
       }

       if($user->role === 'teacher'){
 return response()->json([
    'user' => [

        'name' => $user->name,
        'email' => $user->email,
        'role' => $user->role,
    ],
    $user->role =>[
     'gander' =>  $additionalData->gander,
     'phoneNumber' => $additionalData->phoneNumber,
     'region' => $additionalData->region->name,
     'is_banned' => $additionalData->is_banned,
     'specialization'=>$additionalData->specialization->name,
    ],
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
