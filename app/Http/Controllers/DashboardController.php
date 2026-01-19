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
        // Obtener el año del request, o usar el actual por defecto
        $year = $request->input('year', date('Y'));

        // DATOS PARA LA GRÁFICA (Agrupado por mes) ---
        // Inicializar array con los 12 meses en 0 para asegurar estructura
        $months = [
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun',
            7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'
        ];
        
        $chartData = [];
        foreach ($months as $num => $name) {
            $chartData[$num] = [
                'month' => $name,
                'emails' => 0
            ];
        }

        // Consulta agrupada por mes
        $stats = Invoice::where('user_id', $user->id)
            ->whereYear('created_at', $year)
            ->select(DB::raw('MONTH(created_at) as month_num'), DB::raw('count(*) as total'))
            ->groupBy('month_num')
            ->get();

        // Rellenar los datos reales
        foreach ($stats as $stat) {
            if (isset($chartData[$stat->month_num])) {
                $chartData[$stat->month_num]['emails'] = $stat->total;
            }
        }

        // Reindexar array para enviar JSON limpio (0..11)
        $finalChartData = array_values($chartData);


        // --- DATOS PARA CORREOS RECIENTES (Últimos 5) ---
        $recentEmails = Invoice::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    // Usamos el nombre del cliente o un fallback
                    'sender' => $invoice->client_name ?? 'Desconocido',
                    // Usamos el código de generación o un texto genérico
                    'subject' => 'DTE Recibido: ' . substr($invoice->generation_code, 0, 15) . '...',
                    // Formato de hora AM/PM
                    'time' => Carbon::parse($invoice->created_at)->format('h:i A'),
                    // Un preview simulado o datos reales si tienes campo de descripción
                    'preview' => 'Factura procesada correctamente. Haz clic para ver detalles.'
                ];
            });

        return response()->json([
            'chartData' => $finalChartData,
            'recentEmails' => $recentEmails
        ]);
    }
}