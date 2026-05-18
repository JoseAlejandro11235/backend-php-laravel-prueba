<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // Legacy: validation inside controller and inconsistent response format.
        if (!$request->email || !$request->password) {
            return response()->json(['error' => 'Email and password are required'], 400);
        }

        $user = DB::table('users')->where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Invalid credentials'], 401);
        }

        $token = Str::random(60);
        DB::table('users')->where('id', $user->id)->update(['api_token' => $token]);

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function me(Request $request)
    {
        $user = DB::table('users')->where('id', $request->auth_user_id)->first();
        return response()->json($user);
    }

    public function logout(Request $request)
    {
        DB::table('users')->where('id', $request->auth_user_id)->update(['api_token' => null]);
        return response()->json(['ok' => true]);
    }
}
