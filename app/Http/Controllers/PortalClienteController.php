<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PortalClienteController extends Controller
{
    public function show(Request $request, $token)
    {
        $cliente = \App\Models\Cliente::withoutGlobalScope('lotificacion')
            ->where('token_seguimiento', $token)
            ->firstOrFail();
        
        $cliente->load([
            'ventas' => function ($vq) {
                $vq->withoutGlobalScope('lotificacion')->with([
                    'lotificacion',
                    'lotes' => function($lq) {
                        $lq->withoutGlobalScope('lotificacion')->with([
                            'bloque' => fn($bq) => $bq->withoutGlobalScope('lotificacion')
                        ]);
                    },
                    'cuotas' => function ($query) {
                        $query->orderBy('numero_cuota', 'asc');
                    },
                    'abonos' => function ($query) {
                        $query->orderBy('created_at', 'desc');
                    }
                ]);
            }
        ]);

        $ventas = $cliente->ventas;
        $ventas->each(function ($v) {
            $v->total_abonado = $v->abonos->sum('monto_abonado');
            $primeraCuota = $v->cuotas->first();
            $fechaContrato = $v->fecha_venta ? \Carbon\Carbon::parse($v->fecha_venta)->format('Y-m-d') : null;
            if ($fechaContrato && (!$primeraCuota || $primeraCuota->fecha_vencimiento !== $fechaContrato)) {
                \App\Http\Controllers\AbonoController::recalcularCuotas($v->id_venta);
                $v->load(['cuotas' => fn($q) => $q->orderBy('numero_cuota', 'asc')]);
            }
        });

        $ventaId = $request->get('venta_id');
        $venta = $ventaId ? $ventas->firstWhere('id_venta', $ventaId) : ($ventas->firstWhere('estado_contrato', 'Vigente') ?? $ventas->first());

        return view('portal.estado_cuenta', compact('cliente', 'venta', 'ventas'));
    }

    public function imprimirRecibo(Request $request, $token, $abonoId)
    {
        $cliente = \App\Models\Cliente::withoutGlobalScope('lotificacion')
            ->where('token_seguimiento', $token)
            ->firstOrFail();

        $abono = \App\Models\Abono::withoutGlobalScope('lotificacion')
            ->whereHas('venta', function($q) use ($cliente) {
                $q->withoutGlobalScope('lotificacion')->where('id_cliente', $cliente->id_cliente);
            })
            ->where('id_abono', $abonoId)
            ->firstOrFail();

        return app(\App\Http\Controllers\AbonoController::class)->imprimirRecibo($abono->id_abono);
    }
}
