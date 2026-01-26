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
        $request->validate([
            'email_provider_id' => 'required|integer',
        ]);

        $account = ConnectedAccount::with('provider')
            ->where('user_id',Auth::id())
            ->where('email_provider_id', $request->email_provider_id)
            ->first();

        if (!$account || !$account->refresh_token) {
            return response()->json(['error' => 'Cuenta no encontrada.'], 404);
        }


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

        return response()->json(['error' => 'Error al contactar con el servicio.'], 500);
    }
}