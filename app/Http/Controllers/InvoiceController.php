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
     * Helper para encontrar factura por cuenta vinculada
     */
    private function findInvoiceForUser($invoiceId, $user)
    {
        $myAccountIds = $user->connectedAccounts()->pluck('id');

        return Invoice::where('id', $invoiceId)
            ->whereIn('connected_account_id', $myAccountIds)
            ->firstOrFail();
    }

    /**
     * LISTAR FACTURAS 
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $myAccountIds = $user->connectedAccounts()->pluck('id');

        $query = Invoice::whereIn('connected_account_id', $myAccountIds);

        $selectedFilter = $request->header('X-Account-ID');

        if ($selectedFilter && $selectedFilter != 'all') {
            if ($myAccountIds->contains($selectedFilter)) {
                $query->where('connected_account_id', $selectedFilter);
            }
        }

        return $query->select(
            'id',
            'connected_account_id',
            'generation_code',
            'created_at',
            'client_name',
            'pdf_created_at',
            'json_created_at',
            'pdf_original_name',
            'json_original_name'
        )
            ->with('connectedAccount:id,email,email_provider_id')
            ->orderBy('client_name', 'asc')
            ->orderBy('created_at', 'desc')
            ->paginate(50);
    }

    /**
     * GUARDAR FACTURA 
     */
    public function store(Request $request)
    {

        /*
        return response()->json([
            'mensaje' => 'debug',
            'todos_los_datos_texto' => $request->all(),
            'tiene_archivo_pdf' => $request->hasFile('pdf_file'),
            'tiene_archivo_json' => $request->hasFile('json_file'),
            'nombres_de_los_archivos_que_llegaron' => array_keys($request->allFiles())
        ]);
    */

        $secret = env('N8N_WEBHOOK_SECRET');

        if (!$secret || $request->header('X-Webhook-Secret') !== $secret) {
            return response()->json(['message' => 'Acceso denegado: Secreto inválido.'], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'source_email' => 'required|email',
            'client_name' => 'required|string',
            'pdf_file' => 'nullable|file|mimes:pdf|max:10240',
            'json_file' => 'nullable|file|mimes:json,text|max:5120',
        ]);

        $user = User::findOrFail($request->user_id);

        // Buscar cuenta por email
        $connectedAccount = $user->connectedAccounts()
            ->where('email', $request->source_email)
            ->first();

        if (!$connectedAccount) {
            return response()->json(['message' => 'No existe cuenta vinculada para: ' . $request->source_email], 404);
        }

        $clientName = $request->client_name;
        $pdf = $request->file('pdf_file');
        $json = $request->file('json_file');
        $pdfOriginalName = $pdf ? $pdf->getClientOriginalName() : null;
        $jsonOriginalName = $json ? $json->getClientOriginalName() : null;

        $code = null;
        $content = '';
        $data = [];

        if ($json) {
            $content = file_get_contents($json->getRealPath());
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json(['message' => 'JSON inválido'], 400);
            }

            $code = $data['generationCode']
                ?? $data['codigoGeneracion']
                ?? $data['identificacion']['codigoGeneracion']
                ?? null;
        }

        if (!$code) {
            $code = hash('sha256', $content);
        }

        $existingInvoice = Invoice::where('generation_code', $code)->first();

        if ($existingInvoice) {
            return response()->json([
                'message' => 'Factura ya procesada anteriormente (Omitida)',
                'status' => 'skipped',
                'generation_code' => $code,
                'invoice_id' => $existingInvoice->id
            ], 200);
        }

        if (Invoice::where('generation_code', $code)->exists()) {
            return response()->json(['message' => 'Factura ya existe', 'generation_code' => $code], 409);
        }

        // Guardar archivos
        $folderName = Str::slug($clientName);
        $folder = 'invoices/' . $folderName . '/' . date('Y/m');
        $pdfPath = $pdf->storeAs($folder, "{$code}.pdf", 'public');
        $jsonPath = $json->storeAs($folder, "{$code}.json", 'public');

        // CREAR FACTURA (Usando connected_account_id)
        $invoice = Invoice::create([
            'connected_account_id' => $connectedAccount->id,
            'client_name' => $clientName,
            'generation_code' => $code,
            'pdf_path' => $pdfPath,
            'json_path' => $jsonPath,
            'pdf_original_name' => $pdfOriginalName,
            'json_original_name' => $jsonOriginalName,

            'pdf_created_at' => $pdf ? $request->input('pdf_date', now()) : null,

            'json_created_at' => $json ? $request->input('json_date', now()) : null,
        ]);

        return response()->json([
            'message' => 'Factura guardada exitosamente',
            'invoice' => ['id' => $invoice->id]
        ], 201);
    }

    public function downloadPdf(Request $request, $id)
    {
        $user = $request->user();
        $invoice = $this->findInvoiceForUser($id, $user);

        if (!Storage::disk('public')->exists($invoice->pdf_path)) {
            return response()->json(['message' => 'El archivo PDF no existe'], 404);
        }

        $filename = str_replace(['/', '\\'], '-', $invoice->generation_code) . '.pdf';
        $path = Storage::disk('public')->path($invoice->pdf_path);
        return response()->download($path, $filename);
    }

    public function downloadJson(Request $request, $id)
    {
        $user = $request->user();
        $invoice = $this->findInvoiceForUser($id, $user);

        if (!Storage::disk('public')->exists($invoice->json_path)) {
            return response()->json(['message' => 'El archivo JSON no existe'], 404);
        }

        $filename = str_replace(['/', '\\'], '-', $invoice->generation_code) . '.json';
        $path = Storage::disk('public')->path($invoice->json_path);
        return response()->download($path, $filename);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $invoice = $this->findInvoiceForUser($id, $user);

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
