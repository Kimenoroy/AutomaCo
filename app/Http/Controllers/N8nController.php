<?php

namespace App\Http\Controllers;

use App\Services\N8nService;
use App\Models\ConnectedAccount;
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
     * Sincronización de facturas validando la cuenta seleccionada en el header
     */
    public function syncInvoices(Request $request)
    {
        $user = $request->user();

        // 1. Obtener el ID de la cuenta desde el header
        $selectedAccountId = $request->header('X-Account-ID');

        // 2. Validar que se haya enviado un ID específico (no nulo y no 'all')
        if (!$selectedAccountId || $selectedAccountId === 'all') {
            return response()->json(['error' => 'Debe seleccionar una cuenta específica para sincronizar.'], 400);
        }

        // 3. Buscar la cuenta asegurando que pertenezca al usuario autenticado
        // Esto implícitamente valida la seguridad: si el ID no es del usuario, no encontrará nada.
        $account = ConnectedAccount::with('provider')
            ->where('id', $selectedAccountId)
            ->where('user_id', $user->id)
            ->first();

        // 4. Validar existencia y token
        if (!$account || !$account->refresh_token) {
            return response()->json(['error' => 'Cuenta no encontrada, no autorizada o sin permisos de sincronización.'], 404);
        }

        // 5. Enviar datos al servicio N8n
        $resultado = $this->n8nService->sendProviderIdentifier([
            'user_id'       => $account->user_id,
            'provider'      => $account->provider->identifier, 
            'email'         => $account->email,
            'access_token'  => $account->token,
            'refresh_token' => $account->refresh_token,
            'expires_in'    => $account->expires_at, 
        ]);

        if ($resultado) {
            return response()->json(['status' => 'Sincronización iniciada con éxito.']);
        }

        return response()->json(['error' => 'Error al contactar con el servicio de automatización.'], 500);
    }
}