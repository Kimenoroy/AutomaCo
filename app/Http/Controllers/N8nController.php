<?php

namespace App\Http\Controllers;

use App\Services\N8nService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class N8nController extends Controller
{
    protected $n8nService;

    /**
     * Inyectamos el servicio centralizado de n8n
     */
    public function __construct(N8nService $n8nService)
    {
        $this->n8nService = $n8nService;
    }

    /**
     * Método para disparar la sincronización de facturas
     */
    public function syncInvoices(Request $request)
    {
        $user = Auth::user();

        // Validamos que el usuario tenga los datos necesarios para n8n
        if (!$user->refresh_token) {
            return back()->withErrors(['error' => 'No se encontró una cuenta de correo vinculada.']);
        }

        // Ejecutamos el disparo del flujo a través del servicio
        $resultado = $this->n8nService->triggerInvoiceProcessing([
            'user_id'       => $user->id,
            'refresh_token' => $user->refresh_token,
            'client_name'   => $user->name,
        ]);

        if ($resultado) {
            return back()->with('status', 'Sincronización iniciada. Tus facturas aparecerán en breve.');
        }

        return back()->withErrors(['error' => 'Hubo un problema al conectar con el servicio de automatización.']);
    }
}