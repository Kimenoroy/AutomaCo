<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Models\ConnectedAccount; // Asegúrate de tener este modelo

class SettingsController extends Controller
{
    // Obtener configuración actual y cuentas vinculadas
    public function index(Request $request)
    {
        $user = $request->user()->load('connectedAccounts');

        return response()->json([
            'user' => $user,
            'connected_accounts' => $user->connectedAccounts
        ]);
    }

    // Actualizar Perfil (Nombre, Email)
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
        ]);

        $user->update($validated);

        return response()->json(['message' => 'Perfil actualizado correctamente', 'user' => $user]);
    }

    // Actualizar Contraseña
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password', // Valida que la actual sea correcta
            'password' => 'required|string|min:8|confirmed',
        ]);

        $request->user()->update([
            'password' => Hash::make($request->password)
        ]);

        return response()->json(['message' => 'Contraseña actualizada correctamente']);
    }

    // Desvincular una cuenta (Outlook/Google)
    public function unlinkProvider(Request $request, $providerId)
    {
        $user = $request->user();

        // Buscamos la cuenta vinculada de ese usuario
        $account = ConnectedAccount::where('user_id', $user->id)
            ->where('email_provider_id', $providerId)
            ->firstOrFail();

        $account->delete();

        return response()->json(['message' => 'Cuenta desvinculada exitosamente']);
    }

    // Eliminar cuenta permanentemente
    public function destroy(Request $request)
    {
        // Validamos que envíe la contraseña correcta por seguridad
        $request->validate([
            'password' => 'required|current_password',
        ]);

        $user = $request->user();

        // Revocar todos los tokens de acceso
        $user->tokens()->delete();

        // Eliminar usuario
        $user->delete();

        return response()->json(['message' => 'Cuenta eliminada exitosamente.']);
    }
}
