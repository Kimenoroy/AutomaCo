<?php

namespace App\Http\Controllers;

use App\Services\N8nService;
use App\Models\ConnectedAccount; // Importación necesaria para las consultas
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class N8nController extends Controller
{
    protected $n8nService;

    public function __construct(N8nService $n8nService)
    {
        $this->n8nService = $n8nService;
    }

    /**
     * Sincronización de facturas obteniendo datos de ConnectedAccount
     */
    public function syncInvoices(Request $request)
    {
        // 1. Validamos la entrada del frontend
        $request->validate([
            'user_id' => 'required|integer',
            'email_provider_id' => 'required|integer',
        ]);

        // 2. Buscamos la cuenta conectada específica
        // Esto permite que el flujo funcione aunque el usuario tenga varios proveedores
        $account = ConnectedAccount::with('provider')
            ->where('user_id', $request->user_id)
            ->where('email_provider_id', $request->email_provider_id)
            ->first();

        if (!$account || !$account->refresh_token) {
            return response()->json(['error' => 'Cuenta no encontrada o falta el refresh_token.'], 404);
        }


        $resultado = $this->n8nService->sendProviderIdentifier([
            'user_id'       => $account->user_id,
            'provider'      => $account->provider->identifier, // Requiere relación con EmailProvider
            'email'         => $account->email,
            'access_token'  => $account->token,
            'refresh_token' => $account->refresh_token,
            'expires_in'    => $account->expires_at, // O el cálculo de segundos restantes
        ]);

        if ($resultado) {
            return response()->json(['status' => 'Sincronización iniciada con éxito.']);
        }

        return response()->json(['error' => 'Error al contactar con n8n.'], 500);
    }
}