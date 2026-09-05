<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Venta;
use App\Models\Lote;
use App\Models\HistorialLote;
use App\Models\Abono;
use App\Models\Cuota;
use App\Models\Salida;
use App\Models\Rescision;
use App\Models\Auditoria;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class VentaController extends Controller
{
    public function rescindir(Request $request, $id_venta)
    {
        $request->validate([
            'motivo_rescision'    => 'required|string|min:5|max:1000',
            'lotes_a_rescindir'   => 'required|array|min:1',
            'destino_abonos'      => 'required|in:acreditar_otro_lote,devolucion_efectivo,sin_devolucion',
            'id_venta_destino'    => 'nullable|integer|exists:ventas,id_venta',
            'monto_decision'      => 'nullable|numeric|min:0',
            'nuevo_precio_final'  => 'nullable|numeric|min:0',
            'nueva_cuota_mensual' => 'nullable|numeric|min:0',
            'nuevo_plazo_meses'   => 'nullable|integer|min:1',
            'nuevo_pv_num'        => 'nullable|string|max:255',
        ], [
            'motivo_rescision.required' => 'El comentario o motivo del desistimiento es obligatorio.',
            'motivo_rescision.min'      => 'El comentario debe tener al menos 5 caracteres.',
            'lotes_a_rescindir.required'=> 'Debe seleccionar al menos un lote a desistir.',
            'destino_abonos.required'   => 'Debe seleccionar el destino del dinero abonado.',
        ]);

        try {
            DB::beginTransaction();

            $venta = Venta::withoutGlobalScope('lotificacion')->findOrFail($id_venta);
            $cliente = $venta->cliente;
            $lotificacionId = $venta->lotificacion_id;

            // 1. Obtener lotes activos antes del cambio
            $historialesActivosPrevios = HistorialLote::where('id_venta', $venta->id_venta)
                ->where('estado', 'Activo')
                ->with('lote.bloque')
                ->get();

            $totalLotesPrevios = $historialesActivosPrevios->count();
            $lotesOriginalesNombres = $historialesActivosPrevios->map(function($h) {
                $bloque = $h->lote?->bloque?->nombre ? "Bloque {$h->lote->bloque->nombre} - " : "";
                return "{$bloque}Lote {$h->lote?->numero_lote}";
            })->implode(', ');

            $precioAnterior = (float)$venta->precio_final;
            $cuotaAnterior = (float)$venta->cuota_mensual;
            $totalAbonadoVenta = (float)$venta->abonos()->where('monto_abonado', '>', 0)->sum('monto_abonado');

            // Determinar si es rescisión total o parcial dentro de esta venta
            $lotesARescindir = count($request->lotes_a_rescindir);
            $esParcial = ($lotesARescindir > 0 && $lotesARescindir < $totalLotesPrevios);

            // Identificar nombres de lotes que se devuelven y que se conservan
            $lotesRescindidosNombres = $historialesActivosPrevios->whereIn('id_lote', $request->lotes_a_rescindir)->map(function($h) {
                $bloque = $h->lote?->bloque?->nombre ? "Bloque {$h->lote->bloque->nombre} - " : "";
                return "{$bloque}Lote {$h->lote?->numero_lote}";
            })->implode(', ');

            $lotesConservadosNombres = $historialesActivosPrevios->whereNotIn('id_lote', $request->lotes_a_rescindir)->map(function($h) {
                $bloque = $h->lote?->bloque?->nombre ? "Bloque {$h->lote->bloque->nombre} - " : "";
                return "{$bloque}Lote {$h->lote?->numero_lote}";
            })->implode(', ');

            // Proporción del dinero pagado correspondiente a los lotes que se desisten
            $proporcionAbonosRescindidos = $totalLotesPrevios > 0
                ? round(($totalAbonadoVenta / $totalLotesPrevios) * $lotesARescindir, 2)
                : $totalAbonadoVenta;

            $montoTransferido = 0;
            $montoDevuelto = 0;
            $idVentaDestino = null;

            // 2. Liberar los lotes seleccionados a estado 'Disponible'
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

            // 3. Manejo financiero según Rescisión Parcial o Total
            if ($esParcial) {
                // ── RESCISIÓN PARCIAL (El cliente conserva 1 o más lotes en este contrato) ──
                if (!$request->nuevo_precio_final || !$request->nueva_cuota_mensual || !$request->nuevo_plazo_meses) {
                    throw new \Exception("Para una rescisión parcial debe ingresar el nuevo precio final, cuota mensual y plazo.");
                }

                if ($request->nuevo_pv_num && $cliente) {
                    $cliente->pv_num = $request->nuevo_pv_num;
                    $cliente->save();
                }

                // Actualizar datos de la venta conservada
                $venta->precio_final = max(0, (float)$request->nuevo_precio_final);
                $venta->cuota_mensual = max(0, (float)$request->nueva_cuota_mensual);
                $venta->plazo_meses = max(1, (int)$request->nuevo_plazo_meses);

                // Recalcular extensión de los lotes conservados
                $lotesActivosRestantes = HistorialLote::where('id_venta', $venta->id_venta)
                    ->where('estado', 'Activo')
                    ->with('lote')
                    ->get();
                $nuevaExtension = $lotesActivosRestantes->sum(fn($h) => $h->lote ? (float)$h->lote->area_metros : 0);
                $venta->extension_lote = max(0, $nuevaExtension);
                $venta->save();

                // Manejo de los abonos según decisión
                if ($request->destino_abonos === 'acreditar_otro_lote') {
                    // El dinero abonado se queda 100% en esta venta a favor del lote conservado
                    $montoTransferido = $proporcionAbonosRescindidos;
                    $montoDevuelto = 0;
                } elseif ($request->destino_abonos === 'devolucion_efectivo') {
                    // El dinero del lote desistido se devuelve en efectivo
                    $montoADevolver = $request->monto_decision ? (float)$request->monto_decision : $proporcionAbonosRescindidos;
                    $montoDevuelto = $montoADevolver;
                    $montoTransferido = 0;

                    // Ajustar abonos de la venta con un registro de liquidación negativa
                    if ($montoADevolver > 0) {
                        $reciboData = Abono::generarSiguienteNumeroRecibo($lotificacionId);
                        Abono::create([
                            'id_venta'       => $venta->id_venta,
                            'numero_recibo'  => $reciboData['numero_recibo'],
                            'codigo_recibo'  => $reciboData['codigo_recibo'],
                            'fecha_pago'     => now(),
                            'monto_abonado'  => -$montoADevolver,
                            'tipo_pago'      => 'Devolución/Liquidación',
                            'metodo_pago'    => 'Efectivo',
                            'referencia'     => "Liquidación por desistimiento de {$lotesRescindidosNombres}",
                            'user_id'        => Auth::id(),
                        ]);

                        // Registrar Salida en Caja
                        Salida::create([
                            'monto'          => $montoADevolver,
                            'descripcion'    => "Devolución por desistimiento de {$lotesRescindidosNombres} - Cliente: {$cliente->nombres_apellidos}",
                            'metodo_pago'    => 'Efectivo',
                            'fecha'          => now(),
                            'user_id'        => Auth::id(),
                            'lotificacion_id'=> $lotificacionId
                        ]);
                    }
                }

                // Regenerar plan de cuotas y recalcular
                Cuota::where('id_venta', $venta->id_venta)->delete();
                $fechaVencimiento = \Carbon\Carbon::parse($venta->fecha_venta ?? now());
                $primerAbono = Abono::where('id_venta', $venta->id_venta)->where('monto_abonado', '>', 0)->orderBy('fecha_pago', 'asc')->first();
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
                        Cuota::create([
                            'id_venta'          => $venta->id_venta,
                            'numero_cuota'      => $i,
                            'fecha_vencimiento' => $fechaVencimiento->format('Y-m-d'),
                            'monto_total'       => $montoCuota,
                            'capital'           => $montoCuota,
                            'interes'           => 0,
                            'saldo_restante'    => $montoCuota,
                            'estado'            => 'Pendiente',
                        ]);
                        $saldoRestante = max(0, $saldoRestante - $montoCuota);
                    }
                }

                AbonoController::recalcularCuotas($venta->id_venta);

                $mensaje = "Rescisión parcial completada. Los lotes ({$lotesRescindidosNombres}) han sido liberados (Disponible) y el plan de pagos del lote conservado fue actualizado.";

            } else {
                // ── RESCISIÓN TOTAL (Se cancela toda la venta / lote individual) ──
                $venta->estado_contrato = 'Rescindido';
                $venta->save();

                if ($request->destino_abonos === 'acreditar_otro_lote') {
                    // Transferir a otro contrato del mismo cliente
                    $montoATransferir = $request->monto_decision ? (float)$request->monto_decision : $totalAbonadoVenta;
                    $montoTransferido = $montoATransferir;
                    $montoDevuelto = 0;

                    if ($request->id_venta_destino) {
                        $idVentaDestino = $request->id_venta_destino;
                        $ventaDestino = Venta::withoutGlobalScope('lotificacion')->findOrFail($idVentaDestino);

                        if ($montoATransferir > 0) {
                            $reciboData = Abono::generarSiguienteNumeroRecibo($ventaDestino->lotificacion_id);
                            Abono::create([
                                'id_venta'       => $ventaDestino->id_venta,
                                'numero_recibo'  => $reciboData['numero_recibo'],
                                'codigo_recibo'  => $reciboData['codigo_recibo'],
                                'fecha_pago'     => now(),
                                'monto_abonado'  => $montoATransferir,
                                'tipo_pago'      => 'Abono / Crédito por Desistimiento',
                                'metodo_pago'    => 'Transferencia Interna',
                                'referencia'     => "Acreditado por desistimiento de Contrato #{$venta->id_venta} ({$lotesOriginalesNombres})",
                                'user_id'        => Auth::id(),
                            ]);

                            AbonoController::recalcularCuotas($ventaDestino->id_venta);
                        }
                    }
                } elseif ($request->destino_abonos === 'devolucion_efectivo') {
                    // Devolver en efectivo
                    $montoADevolver = $request->monto_decision ? (float)$request->monto_decision : $totalAbonadoVenta;
                    $montoDevuelto = $montoADevolver;
                    $montoTransferido = 0;

                    if ($montoADevolver > 0) {
                        Salida::create([
                            'monto'          => $montoADevolver,
                            'descripcion'    => "Devolución en efectivo por rescisión total de {$lotesOriginalesNombres} - Cliente: {$cliente->nombres_apellidos}",
                            'metodo_pago'    => 'Efectivo',
                            'fecha'          => now(),
                            'user_id'        => Auth::id(),
                            'lotificacion_id'=> $lotificacionId
                        ]);
                    }
                } else {
                    // Sin devolución por cláusula/penalización
                    $montoDevuelto = 0;
                    $montoTransferido = 0;
                }

                $mensaje = "El contrato fue rescindido en su totalidad. Los lotes ({$lotesOriginalesNombres}) han sido liberados (Disponible).";
            }

            // 4. Guardar registro permanente en la tabla 'rescisiones'
            Rescision::create([
                'id_venta'           => $venta->id_venta,
                'id_cliente'         => $cliente->id_cliente,
                'lotificacion_id'    => $lotificacionId,
                'tipo'               => $esParcial ? 'Parcial' : 'Total',
                'lotes_afectados'    => $lotesRescindidosNombres ?: $lotesOriginalesNombres,
                'lotes_conservados'  => $esParcial ? $lotesConservadosNombres : null,
                'destino_abonos'     => $request->destino_abonos,
                'monto_abonos_lote'  => $proporcionAbonosRescindidos,
                'monto_transferido'  => $montoTransferido,
                'monto_devuelto'     => $montoDevuelto,
                'id_venta_destino'   => $idVentaDestino,
                'comentario'         => $request->motivo_rescision,
                'user_id'            => Auth::id(),
            ]);

            // 5. Guardar en Auditoría del Sistema
            $destinoTexto = match($request->destino_abonos) {
                'acreditar_otro_lote' => "Acreditado a lote/contrato conservado (\$" . number_format($montoTransferido, 2) . ")",
                'devolucion_efectivo' => "Devuelto en efectivo (\$" . number_format($montoDevuelto, 2) . ")",
                default => "Sin devolución según condiciones"
            };

            $detallesAudit = "• <strong>Tipo:</strong> " . ($esParcial ? 'Rescisión Parcial' : 'Rescisión Total') . "<br>" .
                             "• <strong>Lotes Liberados (Disponible):</strong> <span class='text-danger'>{$lotesRescindidosNombres}</span><br>" .
                             ($esParcial ? "• <strong>Lotes Conservados:</strong> <span class='text-success'>{$lotesConservadosNombres}</span><br>" : "") .
                             "• <strong>Destino de lo Abonado:</strong> {$destinoTexto}<br>" .
                             "• <strong>Comentario / Justificación:</strong> " . e($request->motivo_rescision);

            Auditoria::log('Rescisión de Lote(s)', 'Cliente', $cliente->id_cliente, $detallesAudit);

            DB::commit();

            return redirect()->back()->with('success', "✅ {$mensaje}");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al rescindir la venta: ' . $e->getMessage());
        }
    }
}
