<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Login 
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau Password salah.',
            ], 401);
        }

        // Generate Token Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        // Response ke Flutter
        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'data' => $user
        ], 200);
    }

    /**
     * Logout 
     */
    public function logout(Request $request)
{
    /** @var \App\Models\User $user */
    $user = $request->user();

    if ($user && $user->currentAccessToken()) {
        // Menghapus token yang sedang digunakan
        $user->currentAccessToken()->delete();
    }

    return response()->json([
        'success' => true,
        'message' => 'Berhasil logout'
    ], 200);
}
}
