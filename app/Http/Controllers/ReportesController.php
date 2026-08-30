<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Abono;
use App\Models\Salida;
use App\Models\AperturaCaja;
use App\Models\Lotificacion;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportesController extends Controller
{
    public function cierreCaja(Request $request)
    {
        $fecha = $request->input('fecha', Carbon::today()->format('Y-m-d'));

        // Obtener abonos del día, con sus relaciones (venta, cliente, lotes, bloque)
        $abonos = Abono::with(['venta.cliente', 'venta.lotes.bloque'])
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
        $salidas = Salida::whereDate('fecha', $fecha)->where('user_id', auth()->id())->get();

        $totalEgresos = $salidas->sum('monto');
        $flujoNeto = $totalGeneral - $totalEgresos;

        return view('reportes.cierre_caja', compact('abonos', 'salidas', 'fecha', 'totales', 'totalGeneral', 'totalEgresos', 'flujoNeto'));
    }

    public function imprimirCierreCajaPdf(Request $request)
    {
        $fecha = $request->input('fecha', Carbon::today()->format('Y-m-d'));
        $userId = auth()->id();

        // 1. Obtener abonos de la fecha
        $abonos = Abono::with(['venta.cliente', 'venta.lotes.bloque'])
            ->whereDate('fecha_pago', $fecha)
            ->where('user_id', $userId)
            ->get();

        // 2. Obtener salidas de la fecha
        $salidas = Salida::whereDate('fecha', $fecha)
            ->where('user_id', $userId)
            ->get();

        // 3. Apertura de caja para saldo inicial
        $apertura = AperturaCaja::where('fecha', $fecha)
            ->where('user_id', $userId)
            ->latest()
            ->first();
        $saldoInicial = $apertura ? (float)$apertura->monto_inicial : 0.0;

        $abonosEfectivo = [];
        $abonosTransferencia = [];
        $totalEfectivo = 0.0;
        $totalTransferencias = 0.0;

        foreach ($abonos as $abono) {
            $cliente = $abono->venta && $abono->venta->cliente ? $abono->venta->cliente->nombres_apellidos : 'Cliente Desconocido';
            $lotes = '';
            $bloques = '';
            if ($abono->venta) {
                $lotesArr = [];
                $bloquesArr = [];
                foreach ($abono->venta->lotes as $lote) {
                    $lotesArr[] = $lote->numero_lote;
                    if ($lote->bloque) {
                        $bloquesArr[] = $lote->bloque->nombre;
                    }
                }
                $lotes = implode(', ', array_unique($lotesArr));
                $bloques = implode(', ', array_unique($bloquesArr));
            }

            $item = [
                'cliente' => $cliente,
                'lotes' => $lotes,
                'bloques' => $bloques,
                'monto' => $abono->monto_abonado,
                'hora' => $abono->created_at ? $abono->created_at->format('h:i a') : '-',
                'referencia' => $abono->referencia ?? 'N/A',
                'metodo_pago' => $abono->metodo_pago,
                'cuenta_destino' => $abono->cuenta_destino ?? 'N/A',
            ];

            if ($abono->metodo_pago === 'Efectivo') {
                $abonosEfectivo[] = $item;
                $totalEfectivo += $abono->monto_abonado;
            } else {
                $abonosTransferencia[] = $item;
                $totalTransferencias += $abono->monto_abonado;
            }
        }

        // Salidas
        $totalSalidasEfectivo = 0.0;
        foreach ($salidas as $salida) {
            if (empty($salida->metodo_pago) || $salida->metodo_pago === 'Efectivo') {
                $totalSalidasEfectivo += $salida->monto;
            }
        }
        $existenciaEnCaja = $saldoInicial + $totalEfectivo - $totalSalidasEfectivo;

        // Nombre y logo de la lotificación activa
        $lotificacionNombre = 'Proyecto';
        $logoBase64 = null;
        $lotificacionObj = null;

        try {
            $lotificacionObj = app(\App\Services\LotificacionService::class)->getActiveLotificacion();
        } catch (\Exception $e) {}

        if (!$lotificacionObj && session('lotificacion_id')) {
            $lotificacionObj = Lotificacion::find(session('lotificacion_id'));
        }

        if ($lotificacionObj) {
            $lotificacionNombre = $lotificacionObj->nombre;
            if (!empty($lotificacionObj->logo)) {
                $path = public_path('storage/' . $lotificacionObj->logo);
                if (!file_exists($path)) {
                    $path = storage_path('app/public/' . $lotificacionObj->logo);
                }
                if (file_exists($path)) {
                    $type = pathinfo($path, PATHINFO_EXTENSION);
                    $dataImg = file_get_contents($path);
                    $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($dataImg);
                }
            }
        }

        $cajeroNombre = auth()->user() ? auth()->user()->name : 'Cajero';

        $data = [
            'fechaFormateada' => Carbon::parse($fecha)->format('d/m/Y'),
            'horaGeneracion' => now()->format('h:i a'),
            'cajero' => $cajeroNombre,
            'lotificacionNombre' => $lotificacionNombre,
            'logoBase64' => $logoBase64,
            'saldoInicial' => $saldoInicial,
            'totalEfectivo' => $totalEfectivo,
            'totalSalidas' => $totalSalidasEfectivo,
            'saldoFinalCaja' => $existenciaEnCaja,
            'totalTransferencias' => $totalTransferencias,
            'abonosEfectivo' => $abonosEfectivo,
            'abonosTransferencia' => $abonosTransferencia,
        ];

        $pdf = Pdf::loadView('reportes.cierre_turno_pdf', $data)
            ->setPaper('letter', 'portrait');

        return $pdf->stream('Reporte_Cierre_Caja_' . Carbon::parse($fecha)->format('Ymd') . '.pdf');
    }
}
