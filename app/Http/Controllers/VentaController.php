<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Venta;
use App\Models\Lote;
use App\Models\HistorialLote;
use Illuminate\Support\Facades\DB;

class VentaController extends Controller
{
    public function rescindir(Request $request, $id_venta)
    {
        $request->validate([
            'motivo_rescision' => 'required|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            $venta = Venta::findOrFail($id_venta);

            // 1. Actualizar estado del contrato
            $venta->estado_contrato = 'Rescindido';
            $venta->save();

            // 2. Liberar el Historial de Lotes
            $historiales = HistorialLote::where('id_venta', $venta->id_venta)
                                        ->where('estado', 'Activo')
                                        ->get();

            foreach ($historiales as $historial) {
                // Actualizar historial
                $historial->estado = 'Rescindido';
                $historial->fecha_liberacion = now();
                $historial->observaciones = $request->motivo_rescision;
                $historial->save();

                // 3. Liberar el Lote (pasa a Disponible)
                $lote = Lote::find($historial->id_lote);
                if ($lote) {
                    $lote->estado = 'Disponible';
                    $lote->save();
                }
            }

            DB::commit();

            return redirect()->back()->with('success', 'La venta ha sido rescindida y los lotes han sido liberados (estado: Disponible).');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al rescindir la venta: ' . $e->getMessage());
        }
    }
}
