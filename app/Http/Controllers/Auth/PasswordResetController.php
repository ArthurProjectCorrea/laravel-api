<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class PasswordResetController extends Controller
{
    public function sendResetLink(ForgotPasswordRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::where('email', $data['email'])->first();

        // Always return a generic response (no user enumeration)
        if (! $user) {
            return response()->json(['message' => 'Se um usuário com esse email existir, enviaremos um link de redefinição.']);
        }

        $token = Password::createToken($user);

        $user->notify(new ResetPasswordNotification($token, $data['frontend_url']));

        return response()->json(['message' => 'Se um usuário com esse email existir, enviaremos um link de redefinição.']);
    }

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $data = $request->validated();

        $status = Password::reset(
            ['email' => $data['email'], 'password' => $data['password'], 'token' => $data['token']],
            function (User $user, string $password): void {
                $user->password = Hash::make($password);
                $user->save();

                // ensure the PasswordReset event is fired for auditing
                event(new \Illuminate\Auth\Events\PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Senha redefinida com sucesso.']);
        }

        return response()->json(['message' => 'Token inválido ou expirado.'], 400);
    }
}
