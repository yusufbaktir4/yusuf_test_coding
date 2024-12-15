<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * @OA\Info(
 *     title="API Documentation",
 *     version="1.0.0",
 *     description="Documentation API",
 * )
 */

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/auth/token",
     *     summary="Get authentication token",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="email", type="string", example="user@example.com"),
     *             @OA\Property(property="password", type="string", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Get Token successful"),
     *             @OA\Property(property="token", type="string", example="eyJ0eXAiOiJK...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials"
     *     )
     * )
     */

    public function getToken(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        $credentials = $request->only(['email', 'password']);

        if ($token = JWTAuth::attempt($credentials)) {
            return response()->json([
                'message' => 'Get Token successful',
                'token' => $token
            ]);
        }

        return response()->json(['message' => 'Invalid credentials'], 401);
    }
}

