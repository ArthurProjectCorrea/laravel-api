<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            event(new Failed(null, null));

            return response()->json([
                'message' => 'Credenciais inválidas.',
            ], 401);
        }

        // token name as app identifier
        $token = $user->createToken($data['app_name'])->plainTextToken;

        event(new Login('sanctum', $user, false));

        return response()->json([
            'message' => 'Autenticado com sucesso.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'token' => $token,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();

        if ($token) {
            // mark token as revoked (immediate effect)
            $token->revoked = true;
            $token->save();

            event(new Logout('sanctum', $request->user()));

            return response()->json(['message' => 'Logout realizado.']);
        }

        // Fallback: revoke all tokens for the current user (safe default)
        $request->user()?->tokens()->delete();

        return response()->json(['message' => 'Logout realizado.']);
    }

    public function me(Request $request): JsonResponse
    {
        $current = $request->user()?->currentAccessToken();

        if ($current && isset($current->revoked) && $current->revoked) {
            return response()->json(null, 401);
        }

        return response()->json(['data' => $request->user()]);
    }
}
