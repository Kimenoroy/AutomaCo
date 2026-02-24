<?php

namespace App\Http\Controllers\Public;

use Illuminate\Http\Request;
use App\Services\WompiService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class PaymentController extends Controller
{
    protected $wompiService;

    public function __construct(WompiService $wompiService)
    {
        $this->wompiService = $wompiService;
    }

    /**
     * Crear un enlace de pago en Wompi y devolver la URL al frontend
     */
    public function createPaymentLink(Request $request)
    {
        // Validar los datos recibidos desde el frontend
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'reason' => 'required|string|max:255',
        ]);

        try {
            // Llamar al servicio para generar el link
            $response = $this->wompiService->createPaymentLink(
                $request->amount,
                $request->reason
            );

            // Verificar si la respuesta fue exitosa y contiene la URL
            if ($response && isset($response['urlEnlace'])) {
                return response()->json([
                    'success' => true,
                    'payment_url' => $response['urlEnlace'],
                    'wompi_response' => $response,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No se pudo generar el enlace de pago con Wompi.',
                'error_details' => $response
            ], 500);

        } catch (\Exception $e) {
            Log::error('Error al generar enlace de pago: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno al intentar generar el pago.'
            ], 500);
        }
    }

    /**
     * Recibir notificaciones (Webhooks) de Wompi cuando el estado del pago cambie
     */
    public function handleWebhook(Request $request)
    {
        // 1. Recibir los datos del webhook de Wompi
        $payload = $request->all();

        // 2. Registrar el payload en los logs para poder depurar/ver qué datos envía Wompi
        Log::info('Wompi Webhook Recibido:', $payload);

        // TODO: Extraer el estado de la transacción (Ej: APROBADA, RECHAZADA)
        // TODO: Buscar en la base de datos la factura o registro correspondiente 
        // TODO: Actualizar el estado del pago en la base de datos

        return response()->json(['status' => 'Webhook procesado correctamente']);
    }
}
