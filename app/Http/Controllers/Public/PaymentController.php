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
                'plan_name' => $request->plan_name, // <-- OJO AQUÍ, debe decir $request->plan_name
                'amount' => $request->amount,
                'status' => 'PENDING'
            ]);

            //Generar el link en wompi 
            $response = $this->wompiService->createPaymentLink(
                $request->amount,
                $request->plan_name . '(Ref:' . $paymentReference . ')'
            );

            if ($response && isset($response['urlEnlance'])) {
                return response()->json([
                    'success' => true,
                    'payment_url' => $response['urlEnlance'],
                    'reference' => $paymentReference,
                ]);
            }

            return response()->json(['success' => false, 'message' => 'Error al generar el enlace de pago'], 500);


        } catch (\Exception $e) {
            Log::error('Error al generar enlace de pago: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error JAJAJAJA'], 500);
        }

    }

    //Aviso de wompi que el pago fue exitoso
    public function handleWebhook(Request $request)
    {
        $payload = $request->all();
        Log::info('Wompi webhook recibido:', $payload);

        try {
            $estadoTransaccion = $payload['estado'] ?? null;
            $referencia = $payload['refenrencia'] ?? null;

            //Se verifica que sea una compra de codigo de activacion que empiece con "ACT-"
            if (!$referencia || !str_starts_with($referencia, 'ACT-')) {
                return response()->json(['status' => 'Ignorado'], 200);
            }

            //Se busca el correo guardado temporalmente en la tabla de "transactions"
            $transaction = Transaction::where('reference', $referencia)->first();

            //Se sigue con el flujo solo si el pago estaba pendiente
            if ($transaction && $transaction->status === 'PENDING') {

                if ($estadoTransaccion === 'APROBADO') {
                    //marcamos la transaccion como pagada
                    $transaction->update(['status' => 'APPROVED']);

                    //Se genera el codigo y se guarda en la tabla "activacion_codes"
                    $rawCode = strtoupper(Str::random(10));
                    ActivationCode::create([
                        'code_hash' => Hash::make($rawCode),
                        'is_used' => false,
                        'user_id' => null // Esperando a que un usuario lo canjee
                    ]);

                    Mail::to($transaction->email)
                        ->send(new SendActivationCodeMail($rawCode));
                } elseif ($estadoTransaccion === 'RECHAZADA' || $estadoTransaccion === 'FALLIDA') {
                    $transaction->update(['status' => 'REJECTED']);
                }
            }

            return response()->json(['status' => 'Recibido correctamente']);

        } catch (\Exception $e) {
            Log::error('Error al generar enlace de pago: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error JAJAJAJA'], 500);
        }


    }
}
