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
            'motivo_rescision' => 'required|string|max:500',
            'lotes_a_rescindir' => 'required|array',
            'nuevo_precio_final' => 'nullable|numeric|min:0',
            'nueva_cuota_mensual' => 'nullable|numeric|min:0',
            'nuevo_plazo_meses' => 'nullable|integer|min:1',
            'nuevo_pv_num' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            $venta = Venta::findOrFail($id_venta);
            
            // Determinar si es rescisión total o parcial
            $lotesActivos = HistorialLote::where('id_venta', $venta->id_venta)->where('estado', 'Activo')->count();
            $lotesARescindir = count($request->lotes_a_rescindir);
            
            $esParcial = ($lotesARescindir > 0 && $lotesARescindir < $lotesActivos);

            if ($esParcial) {
                // Validar que vengan los datos financieros
                if (!$request->nuevo_precio_final || !$request->nueva_cuota_mensual || !$request->nuevo_plazo_meses) {
                    throw new \Exception("Para una rescisión parcial debe ingresar el nuevo precio final, cuota mensual y plazo.");
                }
                
                // Actualizar PV num si lo enviaron
                if ($request->nuevo_pv_num) {
                    $cliente = $venta->cliente;
                    $cliente->pv_num = $request->nuevo_pv_num;
                    $cliente->save();
                }

                // Liberar los lotes seleccionados
                $historiales = HistorialLote::where('id_venta', $venta->id_venta)
                                            ->where('estado', 'Activo')
                                            ->whereIn('id_lote', $request->lotes_a_rescindir)
                                            ->get();

                foreach ($historiales as $historial) {
                    $historial->estado = 'Rescindido';
                    $historial->fecha_liberacion = now();
                    $historial->observaciones = $request->motivo_rescision;
                    $historial->save();

                    $lote = Lote::find($historial->id_lote);
                    if ($lote) {
                        $lote->estado = 'Disponible';
                        $lote->save();
                    }
                }

                // Actualizar la venta (garantizando valores no negativos)
                $venta->precio_final = max(0, (float)$request->nuevo_precio_final);
                $venta->cuota_mensual = max(0, (float)$request->nueva_cuota_mensual);
                $venta->plazo_meses = max(1, (int)$request->nuevo_plazo_meses);

                // Recalcular la extensión total de los lotes que continúan activos
                $lotesActivosRestantes = HistorialLote::where('id_venta', $venta->id_venta)
                                                     ->where('estado', 'Activo')
                                                     ->with('lote')
                                                     ->get();
                $nuevaExtension = $lotesActivosRestantes->sum(fn($h) => $h->lote ? (float)$h->lote->area_metros : 0);
                $venta->extension_lote = max(0, $nuevaExtension);
                $venta->save();

                // Eliminar todas las cuotas (se regenerarán)
                \App\Models\Cuota::where('id_venta', $venta->id_venta)->delete();
                
                // Generar nuevo plan de cuotas
                $fechaVencimiento = \Carbon\Carbon::parse($venta->fecha_venta);
                $primerAbono = \App\Models\Abono::where('id_venta', $venta->id_venta)->orderBy('fecha_pago', 'asc')->first();
                if ($primerAbono) {
                    $fechaVencimiento = \Carbon\Carbon::parse($primerAbono->fecha_pago);
                }

                $saldoRestante = $venta->precio_final;
                $cuotaMensual = $venta->cuota_mensual;
                $plazoRestante = $venta->plazo_meses;

                if ($plazoRestante > 0 && $saldoRestante > 0) {
                    for ($i = 1; $i <= $plazoRestante; $i++) {
                        $fechaVencimiento->addMonth();
                        
                        $montoCuota = max(0, ($i == $plazoRestante) ? $saldoRestante : $cuotaMensual);
                        
                        \App\Models\Cuota::create([
                            'id_venta' => $venta->id_venta,
                            'numero_cuota' => $i,
                            'fecha_vencimiento' => $fechaVencimiento->format('Y-m-d'),
                            'monto_total' => $montoCuota,
                            'capital' => $montoCuota,
                            'interes' => 0,
                            'saldo_restante' => $montoCuota,
                            'estado' => 'Pendiente',
                        ]);
                        
                        $saldoRestante = max(0, $saldoRestante - $montoCuota);
                    }
                }

                // Reaplicar Abonos íntegramente
                \App\Http\Controllers\AbonoController::recalcularCuotas($venta->id_venta);
                
                $cantConservados = $lotesActivosRestantes->count();
                $cuotaPorLote = $cantConservados > 0 ? ($venta->cuota_mensual / $cantConservados) : 0;
                $detallesAudit = "Rescisión Parcial: {$lotesARescindir} lote(s) devuelto(s), {$cantConservados} conservado(s). " .
                                 "Nuevo Precio: $" . number_format($venta->precio_final, 2) . " | " .
                                 "Nueva Cuota: $" . number_format($venta->cuota_mensual, 2) . " ($" . number_format($cuotaPorLote, 2) . "/lote) | " .
                                 "Motivo: " . $request->motivo_rescision;
                \App\Models\Auditoria::log('Rescindió Parcialmente', 'Venta', $venta->id_venta, $detallesAudit);
                $mensaje = 'Rescisión Parcial exitosa. Lotes liberados, cuota proporcional por lote recalculada y plan de pagos actualizado.';

            } else {
                // Rescisión TOTAL (Lógica original)
                $venta->estado_contrato = 'Rescindido';
                $venta->save();

                $historiales = HistorialLote::where('id_venta', $venta->id_venta)
                                            ->where('estado', 'Activo')
                                            ->get();

                foreach ($historiales as $historial) {
                    $historial->estado = 'Rescindido';
                    $historial->fecha_liberacion = now();
                    $historial->observaciones = $request->motivo_rescision;
                    $historial->save();

                    $lote = Lote::find($historial->id_lote);
                    if ($lote) {
                        $lote->estado = 'Disponible';
                        $lote->save();
                    }
                }
                
                \App\Models\Auditoria::log('Rescindió Contrato', 'Venta', $venta->id_venta, "Motivo: " . $request->motivo_rescision);
                $mensaje = 'La venta ha sido rescindida totalmente y todos los lotes han sido liberados (estado: Disponible).';
            }

            DB::commit();

            return redirect()->back()->with('success', $mensaje);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al rescindir la venta: ' . $e->getMessage());
        }
    }
}
