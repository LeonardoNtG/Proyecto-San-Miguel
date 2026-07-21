<?php

namespace App\Http\Controllers;
use App\Models\Abono;
use App\Models\Salida;
use App\Models\Cliente;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;

class GraficoController extends Controller
{
    public function dashboard()
{
    // 1. Ingresos por mes (Abonos)
    $ingresosMensuales = Abono::selectRaw('MONTH(fecha_pago) as mes, SUM(monto_abonado) as total')
        ->whereYear('fecha_pago', date('Y'))
        ->groupBy('mes')
        ->orderBy('mes')
        ->pluck('total', 'mes')->all();

    // 2. Egresos por mes (Salidas)
    $egresosMensuales = Salida::selectRaw('MONTH(fecha) as mes, SUM(monto) as total')
        ->whereYear('fecha', date('Y'))
        ->groupBy('mes')
        ->orderBy('mes')
        ->pluck('total', 'mes')->all();

    // 3. Clientes nuevos por mes
    $clientesMensuales = Cliente::selectRaw('MONTH(created_at) as mes, COUNT(*) as cantidad')
        ->whereYear('created_at', date('Y'))
        ->groupBy('mes')
        ->orderBy('mes')
        ->pluck('cantidad', 'mes')->all();

    // Preparamos los nombres de los meses para JS
    $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    
    // Formateamos los datos para que siempre tengan 12 posiciones (incluso si hay meses con 0)
    $dataIngresos = [];
    $dataEgresos = [];
    $dataClientes = [];

    for ($i = 1; $i <= 12; $i++) {
        $dataIngresos[] = $ingresosMensuales[$i] ?? 0;
        $dataEgresos[] = $egresosMensuales[$i] ?? 0;
        $dataClientes[] = $clientesMensuales[$i] ?? 0;
    }
    $totalIngresosAnio = Abono::whereYear('fecha_pago', date('Y'))->sum('monto_abonado');
    $totalEgresosAnio = Salida::whereYear('fecha', date('Y'))->sum('monto');
    $totalClientesTotal = Cliente::count();
    $balanceNetoAnio = $totalIngresosAnio - $totalEgresosAnio;

    return view('reportes.dashboard', compact('meses', 'dataIngresos', 'dataEgresos', 'dataClientes','totalIngresosAnio', 'totalEgresosAnio', 'totalClientesTotal', 'balanceNetoAnio'));
}
}
