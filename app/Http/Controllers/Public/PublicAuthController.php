<?php

namespace App\Http\Controllers\Public;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use App\Notifications\PublicResetPassword;

class PublicAuthController extends Controller
{
    public function forgotPasswordPublic(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.exists' => 'No encontramos ninguna cuenta registrada con este correo electrónico.' 
        ]);

        $user = User::where('email', $request->email)->first();

        $token = Password::broker()->createToken($user);

        $user->notify(new PublicResetPassword($token));

        return response()->json([
            'message' => 'Te hemos enviado un enlace para restablecer tu contraseña.'
        ]);
    }

    public function resetPasswordPublic(Request $request)
    {
        // Validamos los datos que envía React
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        // 2. Usamos el broker de Laravel para verificar el token y actualizar
        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();
            }
        );

        // Respondemos según el resultado
        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Contraseña restablecida correctamente.'
            ], 200);
        }

        // Si el token expiró o es inválido
        return response()->json([
            'message' => 'El enlace de recuperación es inválido o ha expirado.'
        ], 400);
    }
}
