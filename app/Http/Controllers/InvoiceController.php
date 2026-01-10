<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    /**
     * Listar facturas del usuario autenticado
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        return Invoice::where('user_id', $user->id)
            ->select('id', 'generation_code', 'created_at')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    /**
     * Recibir factura desde n8n (POST)
     * n8n enviará: user_id, pdf_file, json_file
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'pdf_file' => 'required|file|mimes:pdf|max:10240', // Max 10MB
            'json_file' => 'required|file|mimes:json,text|max:5120', // Max 5MB
        ]);

        // Verificar que el usuario existe
        $user = User::findOrFail($request->user_id);

        $pdf = $request->file('pdf_file');
        $json = $request->file('json_file');

        // Obtener un nombre único (usando json o generar uno)
        $content = file_get_contents($json->getRealPath());
        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'message' => 'El archivo JSON no es válido',
                'error' => json_last_error_msg()
            ], 400);
        }

        $code = $data['generationCode'] ?? $data['codigoGeneracion'] ?? Str::uuid()->toString();

        // Verificar si ya existe
        if (Invoice::where('generation_code', $code)->exists()) {
            return response()->json([
                'message' => 'La factura con este código ya existe',
                'generation_code' => $code
            ], 409);
        }

        // Guardar facturas en una carpeta organizada por fecha
        $folder = 'invoices/' . date('Y/m');

        $pdfPath = $pdf->storeAs($folder, "{$code}.pdf", 'public');
        $jsonPath = $json->storeAs($folder, "{$code}.json", 'public');

        // Registrar en Base de Datos
        $invoice = Invoice::create([
            'user_id' => $user->id,
            'generation_code' => $code,
            'pdf_path' => $pdfPath,
            'json_path' => $jsonPath,
        ]);

        return response()->json([
            'message' => 'Factura guardada exitosamente',
            'invoice' => [
                'id' => $invoice->id,
                'generation_code' => $invoice->generation_code,
                'created_at' => $invoice->created_at,
            ]
        ], 201);
    }

    /**
     * Descargar PDF de la factura
     */
    public function downloadPdf(Request $request, $id)
    {
        $user = $request->user();
        
        $invoice = Invoice::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if (!Storage::disk('public')->exists($invoice->pdf_path)) {
            return response()->json([
                'message' => 'El archivo PDF no existe'
            ], 404);
        }

        $filename = str_replace(['/', '\\'], '-', $invoice->generation_code) . '.pdf';
        $path = Storage::disk('public')->path($invoice->pdf_path);
        return response()->download($path, $filename);
    }

    /**
     * Descargar JSON de la factura
     */
    public function downloadJson(Request $request, $id)
    {
        $user = $request->user();
        
        $invoice = Invoice::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if (!Storage::disk('public')->exists($invoice->json_path)) {
            return response()->json([
                'message' => 'El archivo JSON no existe'
            ], 404);
        }

        $filename = str_replace(['/', '\\'], '-', $invoice->generation_code) . '.json';
        $path = Storage::disk('public')->path($invoice->json_path);
        return response()->download($path, $filename);
    }

    /**
     * Ver detalles de la factura
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        
        $invoice = Invoice::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        return response()->json([
            'invoice' => [
                'id' => $invoice->id,
                'generation_code' => $invoice->generation_code,
                'created_at' => $invoice->created_at,
                'pdf_url' => asset('storage/' . $invoice->pdf_path),
                'json_url' => asset('storage/' . $invoice->json_path),
            ]
        ], 200);
    }
}
