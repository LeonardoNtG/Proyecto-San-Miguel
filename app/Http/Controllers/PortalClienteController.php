<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PortalClienteController extends Controller
{
    public function show(Request $request, $token)
    {
        $cliente = \App\Models\Cliente::where('token_seguimiento', $token)->firstOrFail();
        
        $cliente->load(['ventas.lotes.bloque', 'ventas.cuotas' => function ($query) {
            $query->orderBy('numero_cuota', 'asc');
        }, 'ventas.abonos' => function ($query) {
            $query->orderBy('created_at', 'desc');
        }]);

        $ventas = $cliente->ventas;
        $ventas->each(function ($v) {
            $v->total_abonado = $v->abonos->sum('monto_abonado');
        });

        $ventaId = $request->get('venta_id');
        $venta = $ventaId ? $ventas->firstWhere('id_venta', $ventaId) : ($ventas->firstWhere('estado_contrato', 'Vigente') ?? $ventas->first());

        return view('portal.estado_cuenta', compact('cliente', 'venta', 'ventas'));
    }
}
