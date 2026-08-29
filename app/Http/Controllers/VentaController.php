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
            
            // Obtener información previa de los lotes y datos financieros antes del cambio
            $historialesActivosPrevios = HistorialLote::where('id_venta', $venta->id_venta)
                                                      ->where('estado', 'Activo')
                                                      ->with('lote.bloque')
                                                      ->get();

            $lotesOriginalesNombres = $historialesActivosPrevios->map(function($h) {
                $bloque = $h->lote?->bloque?->nombre ? "Bloque {$h->lote->bloque->nombre} - " : "";
                return "{$bloque}Lote {$h->lote?->numero_lote}";
            })->implode(', ');

            $precioAnterior = (float)$venta->precio_final;
            $cuotaAnterior = (float)$venta->cuota_mensual;
            $totalLotesPrevios = $historialesActivosPrevios->count();

            // Determinar si es rescisión total o parcial
            $lotesActivos = $totalLotesPrevios;
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

                // Identificar nombres de lotes que se devuelven y que se conservan
                $lotesRescindidosNombres = $historialesActivosPrevios->whereIn('id_lote', $request->lotes_a_rescindir)->map(function($h) {
                    $bloque = $h->lote?->bloque?->nombre ? "Bloque {$h->lote->bloque->nombre} - " : "";
                    return "{$bloque}Lote {$h->lote?->numero_lote}";
                })->implode(', ');

                $lotesConservadosNombres = $historialesActivosPrevios->whereNotIn('id_lote', $request->lotes_a_rescindir)->map(function($h) {
                    $bloque = $h->lote?->bloque?->nombre ? "Bloque {$h->lote->bloque->nombre} - " : "";
                    return "{$bloque}Lote {$h->lote?->numero_lote}";
                })->implode(', ');

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
                
                // Registro detallado en el Historial de Auditoría del Cliente
                $detallesAudit = "• <strong>Lotes que Tenía Antes:</strong> {$lotesOriginalesNombres} ({$totalLotesPrevios} lotes)<br>" .
                                 "• <strong>Lotes Rescindidos / Devueltos:</strong> <span class='text-danger'>{$lotesRescindidosNombres}</span> (Liberados a Disponible)<br>" .
                                 "• <strong>Lotes Conservados Actuales:</strong> <span class='text-success fw-bold'>{$lotesConservadosNombres}</span> ({$cantConservados} lotes)<br>" .
                                 "• <strong>Ajuste de Precio Total:</strong> $" . number_format($precioAnterior, 2) . " ➔ $" . number_format($venta->precio_final, 2) . "<br>" .
                                 "• <strong>Ajuste de Cuota Mensual:</strong> $" . number_format($cuotaAnterior, 2) . "/mes ➔ $" . number_format($venta->cuota_mensual, 2) . "/mes ($" . number_format($cuotaPorLote, 2) . " por lote)<br>" .
                                 "• <strong>Motivo / Justificación:</strong> " . e($request->motivo_rescision);

                \App\Models\Auditoria::log('Rescisión Parcial de Lotes', 'Cliente', $venta->id_cliente, $detallesAudit);
                $mensaje = 'Rescisión Parcial exitosa. Lotes liberados, cuota proporcional por lote recalculada y plan de pagos actualizado.';

            } else {
                // Rescisión TOTAL
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
                
                $detallesAudit = "• <strong>Lotes que Tenía:</strong> {$lotesOriginalesNombres} ({$totalLotesPrevios} lotes)<br>" .
                                 "• <strong>Resultado:</strong> Contrato cancelado en su totalidad y todos los lotes liberados a Disponible.<br>" .
                                 "• <strong>Motivo / Justificación:</strong> " . e($request->motivo_rescision);

                \App\Models\Auditoria::log('Rescisión Total de Contrato', 'Cliente', $venta->id_cliente, $detallesAudit);
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
