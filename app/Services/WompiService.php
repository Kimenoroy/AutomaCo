<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class WompiService
{
    protected $baseUrl;
    protected $idUrl;
    protected $appId;
    protected $apiSecret;

    public function __construct()
    {
        $this->baseUrl = config('services.wompi.base_url');
        $this->idUrl = config('services.wompi.id_url');
        $this->appId = config('services.wompi.app_id');
        $this->apiSecret = config('services.wompi.api_secret');
    }

    public function getAccessToken(): ?string
    {
        try {
            $response = Http::asForm()->post("{$this->idUrl}/connect/token", [
                'grant_type' => 'client_credentials',
                'client_id' => $this->appId,
                'client_secret' => $this->apiSecret,
                'audience' => 'wompi_api',
            ]);

            if ($response->successful()) {
                return $response->json('access_token');
            }

            Log::error('Wompi Auth Error: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Wompi Service Exception: ' . $e->getMessage());
            return null;
        }
    }

    public function createPaymentLink(float $amount, string $reason)
    {
        $token = $this->getAccessToken();

        if (!$token)
            return null;

        $response = Http::withToken($token)
            ->post("{$this->baseUrl}/EnlacePago", [
                'identificadorEnlacePago' => 0,
                'monto' => $amount,
                'nombreProducto' => $reason,
                'formaPago' => [
                    'permitirTarjetaCreditoDebido' => true,
                    'permitirPagoConPuntos' => false
                ],
                'configuracion' => [
                    'esMontoEditable' => false,
                    'esCantidadEditable' => false,
                    'cantidadMaximaVisualizar' => 1,
                    'urlRetorno' => env('FRONTEND_URL', 'http://localhost:5173/') . 'pago/completado',
                ]
            ]);

        return $response->json();
    }
}