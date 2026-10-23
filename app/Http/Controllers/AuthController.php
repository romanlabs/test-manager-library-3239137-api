<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', strtolower($validated['username']))->first();

        if (!$user || !Hash::check($validated['password'], $user->password_hash)) {
            return response()->json([
                'code' => 'INVALID_CREDENTIALS',
                'message' => 'Las credenciales no son válidas.',
                'details' => []
            ], 401);
        }

        $payload = [
            'iss' => env('APP_URL'),
            'aud' => env('APP_URL'),
            'iat' => now()->timestamp,
            'exp' => now()->addHours(8)->timestamp,
            'user_id' => $user->id,
            'username' => $user->username,
            'role' => $user->role,
        ];

        $token = JWT::encode($payload, env('JWT_SECRET'), 'HS256');

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 28800,
            'role' => $user->role,
        ], 200);
    }

    public function health()
    {
        return response()->json(['status' => 'ok'], 200);
    }
}
