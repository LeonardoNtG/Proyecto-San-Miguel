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
                    // El dinero del lote desistido se registra como compromiso contable a liquidar por Contabilidad durante el mes (no sale de caja diaria)
                    $montoADevolver = $request->monto_decision ? (float)$request->monto_decision : $proporcionAbonosRescindidos;
                    $montoDevuelto = $montoADevolver;
                    $montoTransferido = 0;
                }

                // Regenerar plan de cuotas y recalcular
                Cuota::where('id_venta', $venta->id_venta)->delete();
                $fechaBase = $venta->fecha_venta ?: now()->format('Y-m-d');
                \App\Http\Controllers\ClienteController::generarPlanCuotas($venta, $fechaBase);
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
                    // La devolución se registra como compromiso contable a ser pagado por Contabilidad durante el mes (no sale de caja diaria)
                    $montoADevolver = $request->monto_decision ? (float)$request->monto_decision : $totalAbonadoVenta;
                    $montoDevuelto = $montoADevolver;
                    $montoTransferido = 0;
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

    /**
     * Muestra el formulario de Edición Integral ("Editar Todo") para una venta,
     * permitiendo modificar datos del cliente, contrato, lote y abonos históricos.
     */
    public function editCompleto($id_venta)
    {
        $venta = Venta::withoutGlobalScope('lotificacion')->with([
            'cliente',
            'lotificacion',
            'lotes' => function($q) {
                $q->withoutGlobalScope('lotificacion')->with('bloque');
            },
            'abonos' => function($q) {
                $q->orderBy('fecha_pago', 'asc')->orderBy('id_abono', 'asc');
            },
            'cuotas'
        ])->findOrFail($id_venta);

        $cliente = $venta->cliente;
        $loteActual = $venta->lotes->first();

        $lotificaciones = \App\Models\Lotificacion::orderBy('nombre')->get();
        $bloques = \App\Models\Bloque::where('lotificacion_id', $venta->lotificacion_id)->orderBy('nombre')->get();
        
        $lotesDisponibles = \App\Models\Lote::where(function($q) use ($loteActual) {
            $q->where('estado', 'Disponible');
            if ($loteActual) {
                $q->orWhere('id_lote', $loteActual->id_lote);
            }
        })->whereIn('id_bloque', $bloques->pluck('id_bloque'))
          ->orderBy('numero_lote')
          ->get();

        $cuentasBancarias = \App\Models\CuentaBancaria::where('estado', 'Activa')->orderBy('banco')->get();

        return view('ventas.edit_completo', compact(
            'venta',
            'cliente',
            'loteActual',
            'lotificaciones',
            'bloques',
            'lotesDisponibles',
            'cuentasBancarias'
        ));
    }

    /**
     * Procesa la actualización integral del contrato, cliente y abonos.
     */
    public function updateCompleto(Request $request, $id_venta)
    {
        $request->validate([
            'nombres_apellidos'   => 'required|string|max:255',
            'identificacion'      => 'required|string|max:50',
            'telefono'            => 'nullable|string|max:50',
            'direccion'           => 'nullable|string|max:500',
            'expediente_num'      => 'nullable|string|max:50',
            'pv_num'              => 'nullable|string|max:50',
            'id_lote'             => 'required|integer|exists:lotes,id_lote',
            'fecha_venta'         => 'required|date',
            'precio_final'        => 'required|numeric|min:0.01',
            'plazo_meses'         => 'required|integer|min:1',
            'cuota_mensual'       => 'required|numeric|min:0.01',
            'beneficiario_final'  => 'nullable|string|max:255',
            'nota_beneficiario'   => 'nullable|string|max:500',
            'motivo_modificacion' => 'required|string|min:3|max:1000',
            'abonos'              => 'nullable|array',
        ], [
            'nombres_apellidos.required'   => 'El nombre del cliente es obligatorio.',
            'identificacion.required'      => 'La cédula o identificación es obligatoria.',
            'id_lote.required'             => 'Debe seleccionar un lote asignado.',
            'fecha_venta.required'         => 'La fecha del contrato es obligatoria.',
            'precio_final.required'        => 'El precio final es obligatorio.',
            'plazo_meses.required'         => 'El plazo en meses es obligatorio.',
            'cuota_mensual.required'       => 'La cuota mensual es obligatoria.',
            'motivo_modificacion.required' => 'Debe ingresar el motivo de la modificación para la bitácora de auditoría.',
        ]);

        DB::beginTransaction();
        try {
            $venta = Venta::withoutGlobalScope('lotificacion')->with(['cliente', 'lotes.bloque', 'abonos'])->findOrFail($id_venta);
            $cliente = $venta->cliente;
            $cambios = [];

            // 1. Actualizar Datos del Cliente
            $datosCliente = [
                'nombres_apellidos' => mb_strtoupper(trim($request->nombres_apellidos), 'UTF-8'),
                'identificacion'    => mb_strtoupper(trim($request->identificacion), 'UTF-8'),
                'telefono'          => trim((string)$request->telefono),
                'direccion'         => $request->direccion ? mb_strtoupper(trim($request->direccion), 'UTF-8') : null,
                'expediente_num'    => $request->expediente_num ? mb_strtoupper(trim($request->expediente_num), 'UTF-8') : $cliente->expediente_num,
                'pv_num'            => $request->pv_num ? mb_strtoupper(trim($request->pv_num), 'UTF-8') : $cliente->pv_num,
            ];

            foreach ($datosCliente as $key => $val) {
                $oldVal = trim((string)$cliente->{$key});
                if ($oldVal !== (string)$val) {
                    $cambios[] = "• <strong>Cliente {$key}:</strong> '{$oldVal}' ➔ '{$val}'";
                }
            }
            $cliente->update($datosCliente);

            // 2. Reasignación de Lote si cambió
            $loteAnterior = $venta->lotes->first();
            $nuevoLoteId = (int)$request->id_lote;
            
            if (!$loteAnterior || $loteAnterior->id_lote !== $nuevoLoteId) {
                $nuevoLote = Lote::with('bloque')->findOrFail($nuevoLoteId);
                
                if ($loteAnterior) {
                    $loteAnterior->estado = 'Disponible';
                    $loteAnterior->save();
                    HistorialLote::where('id_venta', $venta->id_venta)
                        ->where('id_lote', $loteAnterior->id_lote)
                        ->update(['estado' => 'Reasignado', 'fecha_liberacion' => now()]);
                    $cambios[] = "• <strong>Lote cambiado:</strong> de '{$loteAnterior->numero_lote}' (Bloque {$loteAnterior->bloque?->nombre}) a '{$nuevoLote->numero_lote}' (Bloque {$nuevoLote->bloque?->nombre})";
                } else {
                    $cambios[] = "• <strong>Lote asignado:</strong> '{$nuevoLote->numero_lote}' (Bloque {$nuevoLote->bloque?->nombre})";
                }

                $nuevoLote->estado = 'Vendido';
                $nuevoLote->save();

                HistorialLote::create([
                    'id_lote'          => $nuevoLote->id_lote,
                    'id_venta'         => $venta->id_venta,
                    'estado'           => 'Activo',
                    'fecha_asignacion' => now(),
                ]);

                $venta->lotificacion_id = $nuevoLote->bloque->lotificacion_id;
                $venta->extension_lote = (float)$nuevoLote->area_metros;
            }

            // 3. Actualizar Parámetros Financieros del Contrato
            $camposFinancieros = [
                'fecha_venta'        => \Carbon\Carbon::parse($request->fecha_venta)->format('Y-m-d'),
                'precio_final'       => (float)$request->precio_final,
                'plazo_meses'        => (int)$request->plazo_meses,
                'cuota_mensual'      => (float)$request->cuota_mensual,
                'beneficiario_final' => $request->beneficiario_final ? mb_strtoupper(trim($request->beneficiario_final), 'UTF-8') : null,
                'nota_beneficiario'  => $request->nota_beneficiario ? trim($request->nota_beneficiario) : null,
            ];

            $etiquetasCampos = [
                'fecha_venta'        => 'Fecha de Venta',
                'precio_final'       => 'Precio Final del Contrato',
                'plazo_meses'        => 'Plazo',
                'cuota_mensual'      => 'Cuota Mensual',
                'beneficiario_final' => 'Beneficiario',
                'nota_beneficiario'  => 'Nota de Beneficiario',
            ];

            foreach ($camposFinancieros as $key => $val) {
                $oldVal = is_float($val) || is_int($val) ? (float)$venta->{$key} : trim((string)$venta->{$key});
                if ($oldVal != $val) {
                    $nombreLegible = $etiquetasCampos[$key] ?? $key;
                    if ($key === 'precio_final' || $key === 'cuota_mensual') {
                        $oldFormatted = '$' . number_format((float)$oldVal, 2);
                        $newFormatted = '$' . number_format((float)$val, 2);
                    } elseif ($key === 'plazo_meses') {
                        $oldFormatted = $oldVal . ' meses';
                        $newFormatted = $val . ' meses';
                    } else {
                        $oldFormatted = empty($oldVal) ? 'Sin asignar' : "'{$oldVal}'";
                        $newFormatted = empty($val) ? 'Sin asignar' : "'{$val}'";
                    }
                    $cambios[] = "• <strong>{$nombreLegible}:</strong> <span class='badge bg-danger-subtle text-danger border border-danger-subtle'>{$oldFormatted}</span> ➔ <span class='badge bg-success-subtle text-success border border-success-subtle fw-bold'>{$newFormatted}</span>";
                }
            }
            $venta->update($camposFinancieros);

            // 4. Procesar Abonos (Actualizar, Crear o Eliminar)
            $abonosInput = $request->input('abonos', []);

            foreach ($abonosInput as $item) {
                $idAbono = !empty($item['id_abono']) ? (int)$item['id_abono'] : null;
                $eliminar = !empty($item['eliminar']) && ($item['eliminar'] == '1' || $item['eliminar'] === true);

                if ($idAbono) {
                    $abonoExistente = Abono::where('id_venta', $venta->id_venta)->where('id_abono', $idAbono)->first();
                    if ($abonoExistente) {
                        if ($eliminar) {
                            $cambios[] = "• <strong>Abono Eliminado:</strong> Recibo #{$abonoExistente->numero_recibo} por <span class='text-danger fw-bold'>\${$abonoExistente->monto_abonado}</span> ({$abonoExistente->fecha_pago})";
                            $abonoExistente->delete();
                            continue;
                        }

                        $montoNuevo = (float)($item['monto_abonado'] ?? 0);
                        $fechaNueva = !empty($item['fecha_pago']) ? \Carbon\Carbon::parse($item['fecha_pago'])->format('Y-m-d') : $abonoExistente->fecha_pago;
                        $fechaTransfNueva = !empty($item['fecha_transferencia']) ? \Carbon\Carbon::parse($item['fecha_transferencia'])->format('Y-m-d') : $abonoExistente->fecha_transferencia;
                        $reciboNuevo = !empty($item['numero_recibo']) ? (int)$item['numero_recibo'] : $abonoExistente->numero_recibo;
                        $metodoNuevo = $item['metodo_pago'] ?? $abonoExistente->metodo_pago;
                        $tipoNuevo = $item['tipo_pago'] ?? $abonoExistente->tipo_pago;
                        $refNueva = $item['referencia'] ?? $abonoExistente->referencia;
                        $cuentaNueva = $item['cuenta_destino'] ?? $abonoExistente->cuenta_destino;
                        $comentNuevo = $item['comentario'] ?? $abonoExistente->comentario;

                        $detallesAbonoCambios = [];
                        if (round((float)$abonoExistente->monto_abonado, 2) != round($montoNuevo, 2)) {
                            $detallesAbonoCambios[] = "Monto: <span class='text-danger'>$" . number_format($abonoExistente->monto_abonado, 2) . "</span> ➔ <span class='text-success fw-bold'>$" . number_format($montoNuevo, 2) . "</span>";
                        }
                        if ($abonoExistente->fecha_pago != $fechaNueva) {
                            $detallesAbonoCambios[] = "F. Pago: <span class='text-danger'>" . ($abonoExistente->fecha_pago ? \Carbon\Carbon::parse($abonoExistente->fecha_pago)->format('d/m/Y') : 'N/D') . "</span> ➔ <span class='text-success fw-bold'>" . \Carbon\Carbon::parse($fechaNueva)->format('d/m/Y') . "</span>";
                        }
                        if ($abonoExistente->fecha_transferencia != $fechaTransfNueva && !empty($fechaTransfNueva)) {
                            $detallesAbonoCambios[] = "F. Transf: <span class='text-danger'>" . ($abonoExistente->fecha_transferencia ? \Carbon\Carbon::parse($abonoExistente->fecha_transferencia)->format('d/m/Y') : 'Ninguna') . "</span> ➔ <span class='text-success fw-bold'>" . \Carbon\Carbon::parse($fechaTransfNueva)->format('d/m/Y') . "</span>";
                        }
                        if ($abonoExistente->metodo_pago != $metodoNuevo) {
                            $detallesAbonoCambios[] = "Método: <span class='text-danger'>" . ($abonoExistente->metodo_pago ?: 'Efectivo') . "</span> ➔ <span class='text-success fw-bold'>{$metodoNuevo}</span>";
                        }
                        if ($abonoExistente->numero_recibo != $reciboNuevo) {
                            $detallesAbonoCambios[] = "N° Recibo: <span class='text-danger'>#{$abonoExistente->numero_recibo}</span> ➔ <span class='text-success fw-bold'>#{$reciboNuevo}</span>";
                        }
                        if ($abonoExistente->referencia != $refNueva) {
                            $detallesAbonoCambios[] = "Referencia: <span class='text-danger'>" . ($abonoExistente->referencia ?: 'Sin ref') . "</span> ➔ <span class='text-success fw-bold'>" . ($refNueva ?: 'Sin ref') . "</span>";
                        }

                        if (!empty($detallesAbonoCambios)) {
                            $cambios[] = "• <strong>Abono Recibo #{$reciboNuevo}:</strong> " . implode(' | ', $detallesAbonoCambios);
                        }

                        $abonoExistente->update([
                            'numero_recibo'       => $reciboNuevo,
                            'codigo_recibo'       => (string)$reciboNuevo,
                            'fecha_pago'          => $fechaNueva,
                            'fecha_transferencia' => $fechaTransfNueva,
                            'monto_abonado'       => $montoNuevo,
                            'tipo_pago'           => $tipoNuevo,
                            'metodo_pago'         => $metodoNuevo,
                            'referencia'          => $refNueva,
                            'cuenta_destino'      => $cuentaNueva,
                            'comentario'          => $comentNuevo,
                        ]);
                    }
                } else {
                    // Nuevo abono agregado desde el formulario
                    $montoNuevo = (float)($item['monto_abonado'] ?? 0);
                    if ($montoNuevo > 0 && !empty($item['fecha_pago']) && !$eliminar) {
                        $fechaNueva = \Carbon\Carbon::parse($item['fecha_pago'])->format('Y-m-d');
                        $reciboData = !empty($item['numero_recibo']) 
                            ? ['numero_recibo' => (int)$item['numero_recibo'], 'codigo_recibo' => (string)$item['numero_recibo']]
                            : Abono::generarSiguienteNumeroRecibo($venta->lotificacion_id);

                        $nuevoAbono = Abono::create([
                            'id_venta'       => $venta->id_venta,
                            'numero_recibo'  => $reciboData['numero_recibo'],
                            'codigo_recibo'  => $reciboData['codigo_recibo'],
                            'fecha_pago'     => $fechaNueva,
                            'monto_abonado'  => $montoNuevo,
                            'tipo_pago'      => $item['tipo_pago'] ?? 'Cuota',
                            'metodo_pago'    => $item['metodo_pago'] ?? 'Efectivo',
                            'referencia'     => $item['referencia'] ?? null,
                            'cuenta_destino' => $item['cuenta_destino'] ?? null,
                            'comentario'     => $item['comentario'] ?? null,
                            'user_id'        => Auth::id() ?? 1,
                        ]);
                        $cambios[] = "• <strong>Nuevo Abono Agregado:</strong> Recibo #{$nuevoAbono->numero_recibo} por \${$nuevoAbono->monto_abonado} ({$nuevoAbono->fecha_pago})";
                    }
                }
            }

            // 5. Reconstruir Plan de Cuotas y Recalcular
            Cuota::where('id_venta', $venta->id_venta)->delete();
            $fechaBase = $venta->fecha_venta ?: now()->format('Y-m-d');
            \App\Http\Controllers\ClienteController::generarPlanCuotas($venta, $fechaBase);
            \App\Http\Controllers\AbonoController::recalcularCuotas($venta->id_venta);

            // 6. Registrar Auditoría
            $motivo = $request->input('motivo_modificacion');
            $detalles = "<strong>Modificación Integral de Contrato y Pagos:</strong><br>" .
                        (!empty($cambios) ? implode('<br>', $cambios) : 'Sin cambios en valores principales.') .
                        "<br><br><strong>Motivo / Justificación:</strong> " . e($motivo);

            Auditoria::log('Edición Integral de Contrato y Pagos', 'Venta', $venta->id_venta, $detalles);

            DB::commit();

            return redirect()->route('registro.show', ['cliente' => $cliente->id_cliente, 'venta_id' => $venta->id_venta])
                ->with('success', '¡El contrato, cliente y abonos se actualizaron y recalcularon exitosamente!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error al actualizar el contrato: ' . $e->getMessage());
        }
    }
}
