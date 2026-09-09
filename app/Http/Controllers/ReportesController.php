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
        
        $userId = auth()->id();
        if (auth()->user()->hasRole('Administrador') && $request->filled('user_id')) {
            $userId = (int) $request->input('user_id');
        }
        $usuarioSeleccionado = \App\Models\User::find($userId) ?? auth()->user();

        // Obtener abonos de la fecha seleccionada (por fecha de pago real, para no afectar cierres con recibos de migración histórica)
        $abonos = Abono::with(['venta.cliente', 'venta.lotes.bloque'])
            ->whereDate('fecha_pago', $fecha)
            ->where('user_id', $userId)
            ->where('es_migracion', false)
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
        $salidas = Salida::whereDate('fecha', $fecha)->where('user_id', $userId)->get();

        // Obtener rescisiones del día (informativo, no afecta sumas de caja operativa)
        $rescisiones = \App\Models\Rescision::with(['cliente', 'user'])
            ->whereDate('created_at', $fecha)
            ->where('user_id', $userId)
            ->get();
        $totalRescisiones = (float) $rescisiones->sum('monto_abonos_lote');

        $totalEgresos = $salidas->sum('monto');
        $flujoNeto = $totalGeneral - $totalEgresos;

        return view('reportes.cierre_caja', compact(
            'abonos', 
            'salidas', 
            'rescisiones',
            'totalRescisiones',
            'fecha', 
            'totales', 
            'totalGeneral', 
            'totalEgresos', 
            'flujoNeto', 
            'usuarioSeleccionado', 
            'userId'
        ));
    }

    public function imprimirCierreCajaPdf(Request $request)
    {
        $fecha = $request->input('fecha', Carbon::today()->format('Y-m-d'));
        
        $userId = auth()->id();
        if (auth()->user()->hasRole('Administrador') && $request->filled('user_id')) {
            $userId = (int) $request->input('user_id');
        }
        $usuarioObj = \App\Models\User::find($userId) ?? auth()->user();

        // 1. Obtener abonos de la fecha por fecha de pago real (evita que recibos históricos de migración afecten el cierre de hoy)
        $abonos = Abono::with(['venta.cliente', 'venta.lotes.bloque'])
            ->whereDate('fecha_pago', $fecha)
            ->where('user_id', $userId)
            ->where('es_migracion', false)
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

        $rawEfectivo = [];
        $rawTransferencias = [];
        $totalEfectivo = 0.0;
        $totalTransferencias = 0.0;

        foreach ($abonos as $abono) {
            $cliente = $abono->venta && $abono->venta->cliente ? $abono->venta->cliente->nombres_apellidos : 'Cliente Desconocido';
            $lotes = '';
            $bloques = '';
            $lotesBloquesTexto = '';
            if ($abono->venta) {
                $lotesArr = [];
                $bloquesArr = [];
                $lbArr = [];
                foreach ($abono->venta->lotes as $lote) {
                    $lotesArr[] = $lote->numero_lote;
                    if ($lote->bloque) {
                        $bloquesArr[] = $lote->bloque->nombre;
                    }
                    $lbArr[] = "Lote {$lote->numero_lote}";
                }
                $lotes = implode(', ', array_unique($lotesArr));
                $bloques = implode(', ', array_unique($bloquesArr));
                $lotesBloquesTexto = implode(', ', array_unique($lbArr));
            }

            $item = [
                'id_abono' => $abono->id_abono,
                'cliente' => $cliente,
                'lotes' => $lotes,
                'bloques' => $bloques,
                'lotes_texto' => $lotesBloquesTexto ?: "Lote {$lotes}",
                'monto' => (float) $abono->monto_abonado,
                'hora' => $abono->created_at ? $abono->created_at->format('h:i a') : '-',
                'fecha_pago' => $abono->fecha_pago ? \Carbon\Carbon::parse($abono->fecha_pago)->format('d/m/Y') : '-',
                'fecha_hora_registro' => $abono->created_at ? $abono->created_at->format('d/m/Y h:i a') : \Carbon\Carbon::parse($abono->fecha_pago)->format('d/m/Y'),
                'fecha_transferencia' => $abono->fecha_transferencia ? \Carbon\Carbon::parse($abono->fecha_transferencia)->format('d/m/Y') : \Carbon\Carbon::parse($abono->fecha_pago)->format('d/m/Y'),
                'referencia' => $abono->referencia ?? 'Pago en Efectivo',
                'numero_recibo' => $abono->numero_recibo_formateado ?? ($abono->numero_recibo ? 'REC-' . $abono->numero_recibo : 'N/A'),
                'metodo_pago' => $abono->metodo_pago,
                'cuenta_destino' => $abono->cuenta_destino ?? 'N/A',
                'grupo_recibo' => $abono->grupo_recibo ?? null,
                'created_at_ts' => $abono->created_at ? $abono->created_at->timestamp : 0,
            ];

            $metodoNormalizado = trim($abono->metodo_pago ?? '');
            $esEfectivoPuro = ($metodoNormalizado === 'Efectivo' || empty($metodoNormalizado)) 
                && empty($abono->cuenta_destino) 
                && empty($abono->fecha_transferencia);

            if ($esEfectivoPuro) {
                $rawEfectivo[] = $item;
                $totalEfectivo += $item['monto'];
            } else {
                $rawTransferencias[] = $item;
                $totalTransferencias += $item['monto'];
            }
        }

        // Agrupar abonos en efectivo que pertenezcan a la misma operación / recibo
        $abonosEfectivo = [];
        $gruposEfectivo = [];

        foreach ($rawEfectivo as $item) {
            if (!empty($item['grupo_recibo'])) {
                $key = 'GRUPO_' . $item['grupo_recibo'];
            } elseif (!empty($item['numero_recibo']) && $item['numero_recibo'] !== 'N/A') {
                $key = 'REC_' . md5(mb_strtolower($item['cliente']) . '_' . $item['numero_recibo']);
            } elseif ($item['created_at_ts'] > 0) {
                $minuteKey = floor($item['created_at_ts'] / 60);
                $key = 'TIME_' . md5(mb_strtolower($item['cliente']) . '_' . $item['fecha_pago'] . '_' . $minuteKey);
            } else {
                $key = 'SINGLE_' . $item['id_abono'];
            }

            if (!isset($gruposEfectivo[$key])) {
                $gruposEfectivo[$key] = $item;
                $gruposEfectivo[$key]['lotes_lista'] = [$item['lotes_texto']];
            } else {
                $gruposEfectivo[$key]['monto'] += $item['monto'];
                if (!in_array($item['lotes_texto'], $gruposEfectivo[$key]['lotes_lista'])) {
                    $gruposEfectivo[$key]['lotes_lista'][] = $item['lotes_texto'];
                }
            }
        }

        foreach ($gruposEfectivo as $g) {
            $g['lotes_texto'] = implode(', ', $g['lotes_lista']);
            $abonosEfectivo[] = $g;
        }

        // Agrupar transferencias de la misma transacción bancaria (mismo cliente y misma referencia o grupo_recibo)
        $abonosTransferencia = [];
        $gruposTransf = [];

        foreach ($rawTransferencias as $item) {
            $refKey = trim((string)$item['referencia']);
            $hasValidRef = !empty($refKey) && $refKey !== 'N/A' && $refKey !== 'null';

            if (!empty($item['grupo_recibo'])) {
                $key = 'GRUPO_' . $item['grupo_recibo'];
            } elseif ($hasValidRef) {
                $key = 'REF_' . md5(mb_strtolower($item['cliente']) . '_' . mb_strtolower($refKey) . '_' . mb_strtolower($item['cuenta_destino']));
            } else {
                $key = 'SINGLE_' . $item['id_abono'];
            }

            if (!isset($gruposTransf[$key])) {
                $gruposTransf[$key] = $item;
                $gruposTransf[$key]['lotes_lista'] = [$item['lotes_texto']];
            } else {
                $gruposTransf[$key]['monto'] += $item['monto'];
                if (!in_array($item['lotes_texto'], $gruposTransf[$key]['lotes_lista'])) {
                    $gruposTransf[$key]['lotes_lista'][] = $item['lotes_texto'];
                }
            }
        }

        foreach ($gruposTransf as $g) {
            $g['lotes_texto'] = implode(', ', $g['lotes_lista']);
            $abonosTransferencia[] = $g;
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

        // 4. Rescisiones del día (informativo, no altera los totales de caja)
        $rescisiones = \App\Models\Rescision::with(['cliente', 'user'])
            ->whereDate('created_at', $fecha)
            ->where('user_id', $userId)
            ->get();

        $rescisionesData = [];
        $totalRescisiones = 0.0;
        foreach ($rescisiones as $r) {
            $clienteNombre = $r->cliente ? $r->cliente->nombres_apellidos : 'Cliente Desconocido';
            $destinoTexto = match($r->destino_abonos) {
                'acreditar_otro_lote' => 'Acreditado a lote conservado',
                'devolucion_efectivo' => 'Devolución en efectivo',
                default => 'Sin devolución'
            };
            $montoInvolucrado = (float) ($r->monto_abonos_lote ?: ($r->monto_transferido + $r->monto_devuelto));
            $totalRescisiones += $montoInvolucrado;

            $rescisionesData[] = [
                'id_rescision' => $r->id_rescision,
                'cliente' => $clienteNombre,
                'lotes_afectados' => $r->lotes_afectados,
                'lotes_conservados' => $r->lotes_conservados,
                'tipo' => $r->tipo,
                'destino_abonos' => $r->destino_abonos,
                'destino_texto' => $destinoTexto,
                'monto_abonos_lote' => $montoInvolucrado,
                'monto_transferido' => (float) $r->monto_transferido,
                'monto_devuelto' => (float) $r->monto_devuelto,
                'hora' => $r->created_at ? $r->created_at->format('h:i a') : '-',
                'comentario' => $r->comentario,
            ];
        }

        $cajeroNombre = $usuarioObj ? $usuarioObj->name : (auth()->user() ? auth()->user()->name : 'Cajero');

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
            'rescisionesData' => $rescisionesData,
            'totalRescisiones' => $totalRescisiones,
        ];

        $pdf = Pdf::loadView('reportes.cierre_turno_pdf', $data)
            ->setPaper('letter', 'portrait');

        return $pdf->stream('Reporte_Cierre_Caja_' . Carbon::parse($fecha)->format('Ymd') . '.pdf');
    }
}
