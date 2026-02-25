<?php

namespace App\Http\Controllers\Private;

use App\Http\Controllers\Controller;
use App\Models\ActivationCode;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Exception; // Importamos la clase Exception
use Illuminate\Support\Facades\Log; // Para registrar el error real
use Illuminate\Support\Facades\Crypt;

class ActivationCodeController extends Controller
{
    // READ: Listar códigos
    public function index()
    {
        try {
            $codes = ActivationCode::with('user')
                ->orderBy('id', 'desc')
                ->get();
           
            $codes->transform(function ($code) {
                try {
                    // Creamos una nueva propiedad temporal llamada 'raw_code'
                    $code->raw_code = Crypt::decryptString($code->code_hash);
                } catch (Exception $e) {
                    // Si hay un error (ej. era un hash antiguo), devolvemos null
                    $code->raw_code = null;
                }
                return $code;
            });

            return response()->json($codes);
        } catch (Exception $e) {
            Log::error("Fallo al listar códigos: " . $e->getMessage());
            return response()->json(['message' => 'Fallo al obtener la lista.'], 500);
        }
    }

    // CREATE: Generar un nuevo código
    public function store(Request $request)
    {
        try {
            $rawCode = strtoupper(Str::random(6));

            // CAMBIO AQUÍ: Usamos encriptación reversible en lugar de hash
            $encryptedCode = Crypt::encryptString($rawCode);

            $activationCode = ActivationCode::create([
                'code_hash' => $encryptedCode, // Guardamos el string encriptado
                'is_used' => false,
                'user_id' => null,
            ]);

            return response()->json([
                'message' => 'Código generado correctamente',
                'code' => $rawCode,
                'data' => $activationCode
            ], 201);
        } catch (Exception $e) {
            Log::error("Fallo al crear código: " . $e->getMessage());
            return response()->json(['message' => 'Fallo al generar el código.'], 500);
        }
    }

    // DELETE: Eliminar código
    public function destroy($id)
    {
        try {
            $code = ActivationCode::findOrFail($id);
            $code->delete();

            return response()->json(['message' => 'Código eliminado correctamente']);
        } catch (Exception $e) {
            Log::error("Fallo al eliminar código: " . $e->getMessage());
            return response()->json(['message' => 'Fallo al intentar eliminar el código.'], 500);
        }
    }

    public function confirmPassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        if (\Hash::check($request->password, $request->user()->password)) {
            return response()->json([
                'success' => true,
                'message' => 'Contraseña confirmada correctamente.'
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'La contraseña es incorrecta.'
        ], 403);
    }
}
