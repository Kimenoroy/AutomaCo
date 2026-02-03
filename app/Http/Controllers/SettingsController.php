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
        // Especificamos qué columnas SÍ queremos traer
        $user = $request->user()->load(['connectedAccounts' => function ($query) {
            $query->select(
                'id',
                'user_id',
                'email_provider_id',
                'provider_user_id',
                'email',
                'name',
                'avatar',
                'created_at',
                'updated_at'
            );
        }]);

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
            'current_password' => 'required|current_password',
            'password' => 'required|string|min:8|confirmed|different:current_password', 
        ], [
            'password.different' => 'La nueva contraseña no puede ser igual a la actual.'
        ]);

        $request->user()->update([
            'password' => Hash::make($request->password)
        ]);

        return response()->json(['message' => 'Contraseña actualizada correctamente']);
    }

    // Desvincular una cuenta (Outlook/Google)
    public function unlinkProvider(Request $request, $id)
    {
        $user = $request->user();

        // Buscamos la cuenta específica asegurándonos que pertenezca al usuario autenticado
        $account = ConnectedAccount::where('user_id', $user->id)
            ->where('id', $id)
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
