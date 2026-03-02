<?php

namespace App\Http\Controllers\Public;

use Illuminate\Http\Request;
use App\Services\WompiService;
use App\Models\ActivationCode;
use App\Models\Transaction;
use App\Mail\SendActivationCodeMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;

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
            'email' => 'required|email',
            'amount' => 'required|numeric|min:1',
            'plan_name' => 'required|string|max:255', // <-- Cambiado aquí
        ]);

        try {
            $paymentReference = 'ACT-' . Str::random(8);

            //Guardar la intencion de compra con el correo
            Transaction::create([
                'email' => $request->email,
                'reference' => $paymentReference,
                'plan_name' => $request->plan_name,
                'amount' => $request->amount,
                'status' => 'PENDING'
            ]);

            //Generar el link en wompi 
            $response = $this->wompiService->createPaymentLink(
                $request->amount,
                $request->plan_name . '(Ref:' . $paymentReference . ')'
            );

            $urlPago = $response['urlEnlace'] ?? $response['UrlEnlace'] ?? $response['urlEnlaceLargo'] ?? null;

            if ($urlPago) {
                return response()->json([
                    'success' => true,
                    'payment_url' => $urlPago,
                    'reference' => $paymentReference,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Wompi rechazó la petición',
                'detalle_wompi' => $response
            ], 500);
        } catch (\Exception $e) {
            Log::error('Error al generar enlace de pago: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error JAJAJAJA'], 500);
        }
    }

    //Aviso de wompi que el pago fue exitoso
    public function handleWebhook(Request $request)
    {
        $payload = $request->all();
        Log::info('Wompi webhook recibido:', context: $payload);

        try {
            $estadoTransaccion = $payload['ResultadoTransaccion'] ?? null;

            $nombreProducto = $payload['EnlacePago']['NombreProducto'] ?? '';

            preg_match('/(ACT-[a-zA-Z0-9]+)/', $nombreProducto, $matches);
            $referencia = $matches[1] ?? null;

            if (!$referencia) {
                return response()->json(['status' => 'Ignorado, sin referencia ACT'], 200);
            }

            $transaction = Transaction::where('reference', $referencia)->first();

            if ($transaction && $transaction->status === 'PENDING') {

                if ($estadoTransaccion === 'ExitosaAprobada') {

                    $transaction->update(['status' => 'APPROVED']);

                    $rawCode = strtoupper(Str::random(10));
                    ActivationCode::create([
                        'code_hash' => Hash::make($rawCode),
                        'is_used' => false,
                        'user_id' => null
                    ]);

                    Mail::to($transaction->email)
                        ->send(new SendActivationCodeMail($rawCode));

                    Log::info("EXITO Código generado y enviado a: " . $transaction->email);
                } else {
                    $transaction->update(['status' => 'REJECTED']);
                    Log::info("Pago rechazado para la referencia: " . $referencia);
                }
            }

            return response()->json(['status' => 'Recibido correctamente']);
        } catch (\Exception $e) {
            Log::error('Error procesando el webhook: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }
}
