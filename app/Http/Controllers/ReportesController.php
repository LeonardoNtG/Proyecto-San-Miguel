<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Abono;
use Carbon\Carbon;

class ReportesController extends Controller
{
    public function cierreCaja(Request $request)
    {
        $fecha = $request->input('fecha', Carbon::today()->format('Y-m-d'));

        // Obtener abonos del día, con sus relaciones (venta, cliente, lotes)
        $abonos = Abono::with(['venta.cliente', 'venta.lotes'])
            ->whereDate('fecha_pago', $fecha)
            ->where('user_id', auth()->id())
            ->get();

        // Calcular totales por método de pago
        $totales = [
            'Efectivo' => $abonos->where('metodo_pago', 'Efectivo')->sum('monto_abonado'),
            'Transferencia Bancaria' => $abonos->where('metodo_pago', 'Transferencia Bancaria')->sum('monto_abonado'),
            'Depósito Bancario' => $abonos->where('metodo_pago', 'Depósito Bancario')->sum('monto_abonado'),
            'Cheque' => $abonos->where('metodo_pago', 'Cheque')->sum('monto_abonado'),
        ];
        
        // Sumar todos los abonos que no sean Efectivo/Transferencia/Depósito/Cheque por si acaso hay viejos
        $otros = $abonos->whereNotIn('metodo_pago', ['Efectivo', 'Transferencia Bancaria', 'Depósito Bancario', 'Cheque'])->sum('monto_abonado');
        if ($otros > 0) {
            $totales['Otros/Antiguos'] = $otros;
        }

        $totalGeneral = $abonos->sum('monto_abonado');

        // Obtener salidas (egresos) del día
        $salidas = \App\Models\Salida::whereDate('fecha', $fecha)->where('user_id', auth()->id())->get();

        $totalEgresos = $salidas->sum('monto');
        $flujoNeto = $totalGeneral - $totalEgresos;

        return view('reportes.cierre_caja', compact('abonos', 'salidas', 'fecha', 'totales', 'totalGeneral', 'totalEgresos', 'flujoNeto'));
    }
}
