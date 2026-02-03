<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $year = $request->input('year', date('Y'));
        $accountId = $request->input('account_id'); 

        // --- LÓGICA DE FILTRADO DE CUENTAS ---
        
        // Iniciamos la consulta base de Invoices
        $query = Invoice::query();

        if ($accountId) {

            // Verificar que esa cuenta pertenezca al usuario logueado.
            $ownsAccount = $user->connectedAccounts()->where('id', $accountId)->exists();

            if (!$ownsAccount) {
                // Si intenta ver una cuenta que no es suya, devolvemos vacío o error
                return response()->json(['chartData' => [], 'recentEmails' => []]);
            }

            $query->where('connected_account_id', $accountId);

        } else {
            // Buscamos los IDs de todas las cuentas conectadas de este usuario
            $userAccountIds = $user->connectedAccounts()->pluck('id');
            
            // Filtramos las facturas que pertenezcan a CUALQUIERA de sus cuentas
            $query->whereIn('connected_account_id', $userAccountIds);
        }

        // Filtramos por año
        $query->whereYear('created_at', $year);

        // Clona la query para no interferir entre la gráfica y la lista
        $chartQuery = clone $query;
        $listQuery = clone $query;

        // --- DATOS PARA LA GRÁFICA ---
        
        $months = [
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun',
            7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'
        ];
        
        $chartData = [];
        foreach ($months as $num => $name) {
            $chartData[$num] = ['month' => $name, 'emails' => 0];
        }

        $stats = $chartQuery
            ->select(DB::raw('MONTH(created_at) as month_num'), DB::raw('count(*) as total'))
            ->groupBy('month_num')
            ->get();

        foreach ($stats as $stat) {
            if (isset($chartData[$stat->month_num])) {
                $chartData[$stat->month_num]['emails'] = $stat->total;
            }
        }

        $finalChartData = array_values($chartData);

        // --- DATOS PARA CORREOS RECIENTES ---
        
        $recentEmails = $listQuery
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'sender' => $invoice->client_name ?? 'Desconocido',
                    'subject' => 'DTE Recibido: ' . substr($invoice->generation_code, 0, 15) . '...',
                    'time' => Carbon::parse($invoice->created_at)->format('h:i A'),
                    'preview' => 'Factura procesada correctamente.'
                ];
            });

        return response()->json([
            'chartData' => $finalChartData,
            'recentEmails' => $recentEmails
        ]);
    }
}