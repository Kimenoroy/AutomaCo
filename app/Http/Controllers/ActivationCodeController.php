<?php

namespace App\Http\Controllers;

use App\Models\ActivationCode;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ActivationCodeController extends Controller
{
    // READ: Listar códigos
    public function index()
    {
        // Traemos todos los códigos con la información del usuario vinculado
        $codes = ActivationCode::with('user')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($codes);
    }

    // CREATE: Generar un nuevo código
    public function store(Request $request)
    {
        // 1. Generamos un código aleatorio legible (ej: 6 caracteres mayúsculas)
        $rawCode = strtoupper(Str::random(6));

        // 2. Lo hasheamos para la base de datos
        $codeHash = hash('sha256', $rawCode);

        // 3. Guardamos
        $activationCode = ActivationCode::create([
            'code_hash' => $codeHash,
            'is_used' => false,
            'user_id' => null, 
        ]);

        // 4. RETORNAMOS EL CÓDIGO CRUDO 
        return response()->json([
            'message' => 'Código generado correctamente',
            'code' => $rawCode, 
            'data' => $activationCode
        ], 201);
    }

    // DELETE: Eliminar código
    public function destroy($id)
    {
        $code = ActivationCode::findOrFail($id);
        $code->delete();

        return response()->json(['message' => 'Código eliminado correctamente']);
    }
}