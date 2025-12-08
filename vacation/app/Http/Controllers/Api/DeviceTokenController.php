<?php

// app/Http/Controllers/Api/DeviceTokenController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'token'       => 'required|string',
            'platform'    => 'nullable|string',
            'device_name' => 'nullable|string',
        ]);

        $user = $request->user(); // يجي من JWT

        $deviceToken = DeviceToken::updateOrCreate(
            ['token' => $request->token],
            [
                'user_id'     => $user?->id,
                'platform'    => $request->platform,
                'device_name' => $request->device_name,
                'last_used_at'=> now(),
            ]
        );

        return response()->json([
            'status' => 'ok',
            'device_token_id' => $deviceToken->id,
        ]);
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $user = $request->user();

        DeviceToken::where('token', $request->token)
            ->where('user_id', $user?->id)
            ->delete();

        return response()->json(['status' => 'deleted']);
    }
}
