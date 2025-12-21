<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\ActivationCode;
use App\Models\EmailProvider;
use App\Services\N8nService;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{

    public function register(Request $request)
    {
        /*Validar los datos de entrada*/

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        /*Crear el usuario dentro de la base*/
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        /*Crear un token para que el usuario se pueda loguear automaticamente*/
        $token = $user->createToken('auth_token')->plainTextToken;

        /*Retornar la respuesta con el token y la información del usuario*/
        return response()->json([
            'mensage' => 'Usuario registrado exitosamente',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ], 201);
    }

    public function login(Request $request)
    {
        // Validar los datos de entrada
        $credential = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // Intentar autenticar al usuario
        if (Auth::attempt($credential)) {
            $user = User::where('email', $request->email)->first();

            // 1. Eliminamos tokens anteriores y creamos uno nuevo SIEMPRE
            $user->tokens()->delete();
            $token = $user->createToken('auth_token')->plainTextToken;

            // Preparamos los datos del usuario
            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role, 
                'is_active' => (bool) $user->is_active
            ];

            // Verificamos si la cuenta NO está activa
            if (!$user->is_active) {
                return response()->json([
                    'message' => 'Credenciales correctas, pero requiere activación.',
                    'require_activation' => true, 
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'user' => $userData
                ], 200); // Retornamos 200 OK para que no caiga en el catch del frontend
            }

            // Si la cuenta ESTÁ activa, flujo normal
            return response()->json([
                'message' => 'Inicio de sesión exitoso',
                'require_activation' => false,
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $userData
            ], 200);
        }

        return response()->json([
            'message' => 'Credenciales inválidas'
        ], 401);
    }

    public function logout(Request $request)
    {
        //Eliminar el token de autenticación del usuario
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Cierre de sesión exitoso'
        ], 200);
    }

    public function activate(Request $request)
    {
        //Validar el código de activación
        $request->validate([
            'code' => 'required|string',
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'No autenticado'
            ], 401);
        }

        //Si ya está activado, no hacer nada
        if ($user->is_active) {
            return response()->json([
                'message' => 'Su cuenta ya está activada'
            ], 200);
        }

        //Buscar el código de activación
        $codeHash = hash('sha256', $request->code);
        $activationCode = ActivationCode::where('code_hash', $codeHash)
            ->where('user_id', $user->id)
            ->where('is_used', false)
            ->first();

        //Si el código de activación no existe o ya fue utilizado, retornar un error
        if (!$activationCode) {
            return response()->json([
                'message' => 'Código de activación inválido o ya utilizado'
            ], 400);
        }

        //Activar la cuenta y marcar el código como usado
        DB::transaction(function () use ($user, $activationCode) {
            $user->is_active = true;
            $user->save();

            $activationCode->is_used = true;
            $activationCode->used_at = now();
            $activationCode->save();
        });

        return response()->json([
            'message' => 'Cuenta activada exitosamente',
            'user' => $user->fresh()
        ], 200);
    }

    /**
     * Obtener lista de proveedores de email disponibles
     */
    public function getProviders()
    {
        $providers = EmailProvider::select('id', 'name', 'display_name', 'identifier')->get();

        return response()->json([
            'providers' => $providers
        ], 200);
    }

    // ELIMINADO: public function selectProvider(...) 
    // Motivo: La vinculación ahora se maneja vía OAuth en SocialAuthController
}
