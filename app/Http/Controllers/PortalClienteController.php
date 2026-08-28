<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PortalClienteController extends Controller
{
    public function show($token)
    {
        $cliente = \App\Models\Cliente::where('token_seguimiento', $token)->firstOrFail();
        
        $cliente->load(['ventas.lotes.bloque', 'ventas.cuotas' => function ($query) {
            $query->orderBy('numero_cuota', 'asc');
        }, 'ventas.abonos' => function ($query) {
            $query->orderBy('created_at', 'desc');
        }]);

        $venta = $cliente->ventas->first();
        if ($venta) {
            $venta->total_abonado = $venta->abonos->sum('monto_abonado');
        }

        return view('portal.estado_cuenta', compact('cliente', 'venta'));
    }
}
