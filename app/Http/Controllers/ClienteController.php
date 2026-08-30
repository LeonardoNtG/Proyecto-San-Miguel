<?php

namespace App\Http\Controllers;
use App\Models\Lote;
use Illuminate\Support\Facades\DB;


use Illuminate\Http\Request;
use App\Models\Bloque;
use App\Models\Abono;
use App\Models\Venta;
Use App\Models\Cliente;

class ClienteController extends Controller

{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
        public function index(Request $request)
    {
        $search = $request->get('search');
        $filtro = $request->get('filtro', 'activos'); // activos o rescindidos

        $clientesQuery = Cliente::with([
            'ventas.lotes.bloque',
            'ventas.lotificacion',
            'ventas.abonos'
        ])->orderBy('id_cliente', 'desc');

        if ($filtro === 'rescindidos') {
            // Clientes que SOLO tienen ventas rescindidas, o al menos mostrar los rescindidos
            $clientesQuery->whereHas('ventas', function($q) {
                $q->where('estado_contrato', 'Rescindido');
            });
        } else {
            // Por defecto solo clientes con ventas activas
            $clientesQuery->whereHas('ventas', function($q) {
                $q->where('estado_contrato', '!=', 'Rescindido');
            });
        }

        if ($search) {
            $clientesQuery->where(function($q) use ($search) {
                $q->where('expediente_num', 'like', "%{$search}%")
                  ->orWhere('nombres_apellidos', 'like', "%{$search}%")
                  ->orWhere('identificacion', 'like', "%{$search}%")
                  ->orWhereHas('ventas.lotes', function($qLote) use ($search) {
                      $qLote->where('numero_lote', 'like', "%{$search}%")
                            ->orWhereHas('bloque', function($qBloque) use ($search) {
                                $qBloque->where('nombre', 'like', "%{$search}%");
                            });
                  });
            });
        }

        $clientes = $clientesQuery->paginate(15);
        $clientes->each(function ($cliente) {
            $cliente->ventas->each(function ($venta) {
                // Sumar los abonos relacionados con esta venta
                $totalAbonado = $venta->abonos()->sum('monto_abonado');
                $venta->total_abonado = $totalAbonado;
            });
        });
        return view('clientes', compact('clientes', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     * 
     */
    
    public function estadosCuenta(Request $request)
    {
        $search = $request->get('search');
        
        $clientesQuery = Cliente::with(['ventas.cuotas', 'ventas.abonos', 'ventas.lotes.bloque'])->orderBy('id_cliente', 'desc');
        
        // Solo clientes con ventas activas
        $clientesQuery->whereHas('ventas', function($q) {
            $q->where('estado_contrato', '!=', 'Rescindido');
        });

        if ($search) {
            $clientesQuery->where(function($q) use ($search) {
                $q->where('expediente_num', 'like', "%{$search}%")
                  ->orWhere('nombres_apellidos', 'like', "%{$search}%")
                  ->orWhere('identificacion', 'like', "%{$search}%")
                  ->orWhereHas('ventas.lotes', function($qLote) use ($search) {
                      $qLote->where('numero_lote', 'like', "%{$search}%")
                            ->orWhereHas('bloque', function($qBloque) use ($search) {
                                $qBloque->where('nombre', 'like', "%{$search}%");
                            });
                  });
            });
        }

        $clientes = $clientesQuery->paginate(15);
        
        return view('estados_cuenta', compact('clientes', 'search'));
    }

    public function create()
    {
        $activeLotificacionId = session('lotificacion_id');
        $lotificacionActiva = \App\Models\Lotificacion::find($activeLotificacionId);
        $bloques = Bloque::where('lotificacion_id', $activeLotificacionId)->orderBy('nombre')->get();
        $siguienteExpediente = Cliente::generarSiguienteExpediente();

        return view('registro', compact('lotificacionActiva', 'bloques', 'siguienteExpediente'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // 1. VALIDACIÓN
        $request->validate([
            'pv_num' => 'nullable|string|max:50',
            'expediente_num' => 'nullable|string|max:50',    
            'nombres_apellidos' => 'required|string|max:255',
            'identificacion' => 'required|string|max:30',
            'lotes_ids' => 'nullable|array|min:1|max:20',
            'lotes' => 'nullable|array|min:1|max:20',
            'extension_value' => 'required|numeric|min:0',
        ]);
        
        $lotesIds = $request->input('lotes_ids') ?? $request->input('lotes');
        if (empty($lotesIds) || !is_array($lotesIds)) {
            return back()->withInput()->withErrors(['lotes_ids' => 'Debe seleccionar al menos un lote para la venta.']);
        }

        $tipoContrato = $request->input('tipo_contrato', 'unificado');

        DB::beginTransaction();

        try {
            // CREAR EL CLIENTE
            $expedienteNum = $request->expediente_num ?: Cliente::generarSiguienteExpediente();
            $pvNum = $request->pv_num ?: 'PP';

            $cliente = Cliente::create([
                'expediente_num' => $expedienteNum,
                'pv_num'         => $pvNum,
                'nombres_apellidos' => $request->nombres_apellidos,
                'identificacion'   => $request->identificacion,
                'telefono'         => $request->telefono,
                'direccion'        => $request->direccion,
                'estado_civil'     => $request->estado_civil,
                'oficio'           => $request->oficio ?? $request->profesion_oficio,
            ]);

            $activeLotificacionId = session('lotificacion_id');
            $lotificacionId = $activeLotificacionId ?: $request->lotificacion_id;

            // ─── MODO UNIFICADO (flujo original) ────────────────────────────────
            if ($tipoContrato !== 'individual' || count($lotesIds) === 1) {

                $venta = Venta::create([
                    'id_cliente'        => $cliente->id_cliente,
                    'lotificacion_id'   => $lotificacionId,
                    'fecha_venta'       => now(),
                    'precio_final'      => $request->precio_final,
                    'plazo_meses'       => $request->plazo_meses,
                    'estado_contrato'   => 'Vigente',
                    'extension_lote'    => $request->extension_value,
                    'cuota_mensual'     => $request->cuota_mensual,
                    'beneficiario_final'=> $request->beneficiario_final,
                    'nota_beneficiario' => $request->nota_beneficiario,
                ]);

                Lote::whereIn('id_lote', $lotesIds)->update(['estado' => 'Vendido']);

                foreach ($lotesIds as $loteId) {
                    \App\Models\HistorialLote::create([
                        'id_lote'         => $loteId,
                        'id_venta'        => $venta->id_venta,
                        'estado'          => 'Activo',
                        'fecha_asignacion'=> now(),
                    ]);
                }

                $abonoInicial = Abono::create([
                    'id_venta'      => $venta->id_venta,
                    'fecha_pago'    => $request->fecha_ultimo_abono ?? now(),
                    'monto_abonado' => $request->primer_abono,
                    'tipo_pago'     => 'Prima/Primer Abono',
                    'metodo_pago'   => $request->metodo_pago_prima ?? 'Efectivo',
                    'referencia'    => $request->referencia_prima ?? 'Registro Inicial de Venta',
                    'cuenta_destino'=> $request->cuenta_destino_prima ?? null,
                    'user_id'       => auth()->id()
                ]);

                $this->generarPlanCuotas($venta, $abonoInicial->fecha_pago);
                \App\Http\Controllers\AbonoController::recalcularCuotas($venta->id_venta);

            // ─── MODO INDIVIDUAL (un contrato/plan por lote) ────────────────────
            } else {
                $lotes = Lote::with('bloque')->whereIn('id_lote', $lotesIds)->get();
                $totalLotes = $lotes->count();
                $cuotaPorLote = $totalLotes > 0 ? round((float)$request->cuota_mensual / $totalLotes, 2) : (float)$request->cuota_mensual;
                $precioPorLote = $totalLotes > 0 ? round((float)$request->precio_final / $totalLotes, 2) : (float)$request->precio_final;

                // Leer beneficiarios individuales (enviados como arrays por JS)
                $beneficiarios = $request->input('beneficiarios', []);
                $notas         = $request->input('notas_beneficiario', []);
                $primerAbonoTotal = (float)$request->primer_abono;
                $abonosPorLote = $totalLotes > 0 ? round($primerAbonoTotal / $totalLotes, 2) : $primerAbonoTotal;

                foreach ($lotes as $index => $lote) {
                    $venta = Venta::create([
                        'id_cliente'        => $cliente->id_cliente,
                        'lotificacion_id'   => $lotificacionId,
                        'fecha_venta'       => now(),
                        'precio_final'      => $precioPorLote,
                        'plazo_meses'       => $request->plazo_meses,
                        'estado_contrato'   => 'Vigente',
                        'extension_lote'    => (float)$lote->area_metros,
                        'cuota_mensual'     => $cuotaPorLote,
                        'beneficiario_final'=> $beneficiarios[$index] ?? null,
                        'nota_beneficiario' => $notas[$index] ?? null,
                    ]);

                    $lote->estado = 'Vendido';
                    $lote->save();

                    \App\Models\HistorialLote::create([
                        'id_lote'         => $lote->id_lote,
                        'id_venta'        => $venta->id_venta,
                        'estado'          => 'Activo',
                        'fecha_asignacion'=> now(),
                    ]);

                    $abonoInicial = Abono::create([
                        'id_venta'      => $venta->id_venta,
                        'fecha_pago'    => $request->fecha_ultimo_abono ?? now(),
                        'monto_abonado' => $abonosPorLote,
                        'tipo_pago'     => 'Prima/Primer Abono',
                        'metodo_pago'   => $request->metodo_pago_prima ?? 'Efectivo',
                        'referencia'    => $request->referencia_prima ?? 'Registro Inicial - Lote ' . $lote->numero_lote,
                        'cuenta_destino'=> $request->cuenta_destino_prima ?? null,
                        'user_id'       => auth()->id()
                    ]);

                    $this->generarPlanCuotas($venta, $abonoInicial->fecha_pago);
                    \App\Http\Controllers\AbonoController::recalcularCuotas($venta->id_venta);
                }
            }

            DB::commit();
            return redirect()->route('registro.index')->with('success', 'Cliente y Venta(s) registrados exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Ocurrió un error al registrar: ' . $e->getMessage());
        }
    }

    /**
     * Genera el Plan de Cuotas para una venta a partir de la fecha del primer pago.
     */
    private function generarPlanCuotas(Venta $venta, $fechaInicio): void
    {
        $plazoRestante = $venta->plazo_meses;
        $saldoRestante = $venta->precio_final;
        $cuotaMensual  = $venta->cuota_mensual;

        if ($plazoRestante > 0 && $saldoRestante > 0) {
            $fechaVencimiento = \Carbon\Carbon::parse($fechaInicio);

            for ($i = 1; $i <= $plazoRestante; $i++) {
                $fechaVencimiento->addMonth();
                $montoCuota = ($i == $plazoRestante) ? $saldoRestante : $cuotaMensual;

                \App\Models\Cuota::create([
                    'id_venta'          => $venta->id_venta,
                    'numero_cuota'      => $i,
                    'fecha_vencimiento' => $fechaVencimiento->format('Y-m-d'),
                    'monto_total'       => $montoCuota,
                    'capital'           => $montoCuota,
                    'interes'           => 0,
                    'saldo_restante'    => $montoCuota,
                    'estado'            => 'Pendiente',
                ]);

                $saldoRestante -= $montoCuota;
            }
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Cliente $cliente, Request $request)
    {
        $cliente->load([
            'ventas' => function($vq) {
                $vq->withoutGlobalScope('lotificacion')->with([
                    'lotificacion',
                    'lotes' => function($lq) {
                        $lq->withoutGlobalScope('lotificacion')->with([
                            'bloque' => function($bq) {
                                $bq->withoutGlobalScope('lotificacion')->with('lotificacion');
                            }
                        ]);
                    },
                    'lotesRescindidos' => function($lrq) {
                        $lrq->withoutGlobalScope('lotificacion')->with([
                            'bloque' => function($bq) {
                                $bq->withoutGlobalScope('lotificacion')->with('lotificacion');
                            }
                        ]);
                    },
                    'cuotas',
                    'abonos' => function ($query) {
                        $query->orderBy('created_at', 'desc');
                    }
                ]);
            }
        ]);

        $cliente->ventas->each(function ($venta) {
            $venta->total_abonado = $venta->abonos->sum('monto_abonado');
        });

        $ventaIdSeleccionada = $request->get('venta_id');
        $ventaActual = null;
        if ($ventaIdSeleccionada) {
            $ventaActual = $cliente->ventas->firstWhere('id_venta', $ventaIdSeleccionada);
        }
        if (!$ventaActual) {
            $ventaActual = $cliente->ventas->firstWhere('estado_contrato', 'Vigente') ?? $cliente->ventas->first();
        }

        $ventaIds = $cliente->ventas->pluck('id_venta')->toArray();

        $historialModificaciones = \App\Models\Auditoria::where(function($q) use ($cliente, $ventaIds) {
            $q->where(function($sub) use ($cliente) {
                $sub->where('modelo', 'Cliente')->where('modelo_id', $cliente->id_cliente);
            })->orWhere(function($sub) use ($ventaIds) {
                $sub->where('modelo', 'Venta')->whereIn('modelo_id', $ventaIds);
            });
        })->with('user')->orderBy('created_at', 'desc')->get();

        return view('show', compact('cliente', 'historialModificaciones', 'ventaActual'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Cliente $cliente)
    {
         $venta = $cliente->ventas->first(); 
         return view('edit', compact('cliente', 'venta'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Cliente $cliente)
    {
        $request->validate([
            'nombres_apellidos' => 'required|string|max:255',
            'identificacion' => 'required|string|max:255',
            'pv_num' => 'required|string|max:255',
            'expediente_num' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:50',
            'direccion' => 'nullable|string|max:500',
            'estado_civil' => 'nullable|string|max:50',
            'oficio' => 'nullable|string|max:100',
            'motivo_modificacion' => 'required|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $campos = [
                'nombres_apellidos' => 'Nombre / Titular',
                'identificacion' => 'Cédula / Identificación',
                'pv_num' => 'N° Promesa de Venta (PV)',
                'expediente_num' => 'N° Expediente',
                'telefono' => 'Teléfono',
                'direccion' => 'Dirección',
                'estado_civil' => 'Estado Civil',
                'oficio' => 'Oficio',
            ];

            $cambios = [];
            $esCesion = false;

            foreach ($campos as $campo => $etiqueta) {
                $valorAnterior = trim((string)$cliente->getOriginal($campo));
                $valorNuevo = trim((string)$request->input($campo));

                if ($valorAnterior !== $valorNuevo) {
                    $cambios[] = "• <strong>{$etiqueta}:</strong> '{$valorAnterior}' ➔ '{$valorNuevo}'";
                    if ($campo === 'nombres_apellidos' || $campo === 'identificacion') {
                        $esCesion = true;
                    }
                }
            }

            $cliente->update($request->only([
                'expediente_num', 'pv_num', 'nombres_apellidos', 'identificacion',
                'telefono', 'direccion', 'estado_civil', 'oficio'
            ]));

            if (!empty($cambios)) {
                $motivo = $request->input('motivo_modificacion');
                $accion = $esCesion ? 'Cesión de Derechos / Cambio de Titular' : 'Modificación de Datos';
                $detalles = implode('<br>', $cambios) . "<br><strong>Motivo / Justificación:</strong> " . e($motivo);

                \App\Models\Auditoria::log($accion, 'Cliente', $cliente->id_cliente, $detalles);
            }

            DB::commit();
            return redirect()->route('registro.show', $cliente->id_cliente)
                         ->with('success', 'Información del cliente actualizada y registrada en el historial de auditoría correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al actualizar: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Cliente $cliente)
{
    abort_if(!auth()->user()->can('borrar-clientes'), 403, 'No tienes permiso para borrar clientes.');

    DB::beginTransaction();
    try {
        $venta = $cliente->ventas->first();

        // 1. Lotes Asociados
        $loteIds = [];
        if ($venta) {
            // Todos los IDs de los lotes relacionados con esta venta
            $loteIds = $venta->lotes()->pluck('id_lote')->toArray();
        }

        // Elimina al Cliente y relaciones
        
        if ($venta) {
            $venta->abonos()->delete(); // Elimina Abonos
            $venta->delete();           // Elimina Venta
        }
        $cliente->delete();             // Elimina Cliente
        // Cambiar el estado de los lotes de la venta a 'Disponible'
        if (!empty($loteIds)) {
            Lote::whereIn('id_lote', $loteIds)->update(['estado' => 'Disponible']);
        }
        
        DB::commit();

        return redirect()->route('registro.index')
                         ->with('success', 'Cliente, registros asociados y lotes liberados eliminados exitosamente.');

    } catch (\Exception $e) {
        DB::rollBack();
        
        return back()->with('error', 'Error al eliminar el cliente. Verifique las dependencias de la base de datos.');
    }
 }
}
