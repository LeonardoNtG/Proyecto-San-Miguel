<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cuota;
use Illuminate\Support\Facades\DB;

class CuotaController extends Controller
{
    /**
     * Exonera o negocia la mora de una cuota específica.
     */
    public function exonerarMora(Request $request, Cuota $cuota)
    {
        $request->validate([
            'monto_exonerar' => 'required|numeric|min:0',
        ]);

        $montoExonerar = $request->monto_exonerar;
        $moraPendienteActual = $cuota->mora_calculada - $cuota->mora_pagada - $cuota->mora_exonerada;

        if ($montoExonerar > $moraPendienteActual) {
            return redirect()->back()->with('error', 'El monto a exonerar no puede ser mayor a la mora pendiente actual ($' . number_format($moraPendienteActual, 2) . ').');
        }

        DB::beginTransaction();
        try {
            $cuota->mora_exonerada += $montoExonerar;
            $cuota->save();

            DB::commit();

            \App\Models\Auditoria::log('Exoneró Mora', 'Cuota', $cuota->id_cuota, "Monto exonerado: $" . number_format($montoExonerar, 2));
            return redirect()->back()->with('success', 'Mora exonerada correctamente por un monto de $' . number_format($montoExonerar, 2));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Ocurrió un error al exonerar la mora: ' . $e->getMessage());
        }
    }
}
