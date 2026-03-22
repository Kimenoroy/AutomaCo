<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        //Se opttiene el usuario autenticado
        $user = $request->user();

        //Se busca las transacciones que tengan con su correo 
        $transactions = Transaction::where("email", $user->email)
            //Se buscan las transacciones ordenadas por fecha de creación de forma descendente
            ->orderBy("created_at", 'desc')
            ->get([
                'id',
                'reference',
                'plan_name',
                'amount',
                'status',
                'created_at'
            ]);

            if ($transactions->isEmpty()) {
            return response()->json([
                'message' => 'Aún no tienes historial de compras. Cuando adquieras un plan, tus transacciones aparecerán aquí.',
                'transactions' => []
            ], 200);
        }

        return response()->json([
            'message' => 'Historial de compras obtenido exitosamente.',
            'transactions' => $transactions
        ], 200);
    }
}
