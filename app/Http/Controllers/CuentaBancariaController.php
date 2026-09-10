<?php

namespace App\Http\Controllers;

use App\Models\CuentaBancaria;
use Illuminate\Http\Request;

class CuentaBancariaController extends Controller
{
    /**
     * Retorna la lista de cuentas bancarias activas (JSON).
     */
    public function index()
    {
        $cuentas = CuentaBancaria::where('estado', 'Activa')
            ->orderBy('banco')
            ->orderBy('moneda')
            ->get();

        return response()->json([
            'success' => true,
            'cuentas' => $cuentas->map(function ($c) {
                return [
                    'id' => $c->id,
                    'banco' => $c->banco,
                    'moneda' => $c->moneda,
                    'numero_cuenta' => $c->numero_cuenta,
                    'titular' => $c->titular,
                    'texto_completo' => $c->texto_completo,
                ];
            })
        ]);
    }

    /**
     * Almacena una nueva cuenta bancaria vía AJAX desde el modal.
     */
    public function store(Request $request)
    {
        $request->validate([
            'banco' => 'required|string|max:100',
            'moneda' => 'required|string|in:$,C$',
            'numero_cuenta' => 'required|string|max:100',
            'titular' => 'required|string|max:255',
        ]);

        $cuenta = CuentaBancaria::create([
            'banco' => trim($request->banco),
            'moneda' => trim($request->moneda),
            'numero_cuenta' => trim($request->numero_cuenta),
            'titular' => trim($request->titular),
            'estado' => 'Activa',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cuenta bancaria registrada con éxito.',
            'cuenta' => [
                'id' => $cuenta->id,
                'banco' => $cuenta->banco,
                'moneda' => $cuenta->moneda,
                'numero_cuenta' => $cuenta->numero_cuenta,
                'titular' => $cuenta->titular,
                'texto_completo' => $cuenta->texto_completo,
            ]
        ]);
    }
}
