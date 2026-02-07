<?php

namespace App\Http\Controllers;

use App\Models\ActivationCode;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Exception; // Importamos la clase Exception
use Illuminate\Support\Facades\Log; // Para registrar el error real

class ActivationCodeController extends Controller
{
    // READ: Listar códigos
    public function index()
    {
        try {
            // Traemos todos los códigos con la información del usuario vinculado
            $codes = ActivationCode::with('user')
                ->orderBy('id', 'desc')
                ->get();

            return response()->json($codes);
        } catch (Exception $e) {
            Log::error("Fallo al listar códigos: " . $e->getMessage());
            return response()->json(['message' => 'Fallo al obtener la lista de códigos.'], 500);
        }
    }

    // CREATE: Generar un nuevo código
    public function store(Request $request)
    {
        try {
            // 1. Generamos un código aleatorio legible
            $rawCode = strtoupper(Str::random(6));

            // 2. Lo hasheamos para la base de datos
            $codeHash = hash('sha256', $rawCode);

            // 3. Guardamos
            $activationCode = ActivationCode::create([
                'code_hash' => $codeHash,
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
            return response()->json(['message' => 'Fallo al generar el código de activación.'], 500);
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
}