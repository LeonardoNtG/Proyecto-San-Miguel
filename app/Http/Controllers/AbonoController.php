<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Abono;
use App\Models\Venta;
Use App\Models\Cliente;
Use App\Models\Lote;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage; 
use Illuminate\Validation\Rule;

class AbonoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Cliente $cliente, Request $request)
    {
        // Si el cliente tiene múltiples ventas, permitir seleccionar cuál pagar o modo consolidado
        $ventas = $cliente->ventas()->with(['lotes.bloque', 'abonos'])->where('estado_contrato', '!=', 'Rescindido')->get();

        if ($ventas->isEmpty()) {
            return redirect()->route('registro.index')->with('error', 'El cliente no tiene una venta activa para registrar abonos.');
        }

        $ventaId = $request->get('venta_id');
        $esModoTodos = ($ventaId === 'todos');

        // Si tiene múltiples ventas y no se especifica venta_id, por defecto seleccionamos 'todos'
        if (!$ventaId && $ventas->count() > 1) {
            $esModoTodos = true;
        }

        if ($esModoTodos) {
            $venta = $ventas->first(); // Referencia principal
            
            // Cargar abonos de todas las ventas
            $ventaIds = $ventas->pluck('id_venta');
            $todosAbonos = \App\Models\Abono::whereIn('id_venta', $ventaIds)
                ->with('venta.lotes.bloque')
                ->orderBy('fecha_pago', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            $totalAbonado = (float) $todosAbonos->sum('monto_abonado');
            $precioTotal = (float) $ventas->sum('precio_final');
            $saldoPendiente = max(0, $precioTotal - $totalAbonado);

            $cuotasPendientesList = \App\Models\Cuota::whereIn('id_venta', $ventaIds)
                ->whereIn('estado', ['Pendiente', 'Mora', 'Parcial'])
                ->get();
            $cuotasPendientes = $cuotasPendientesList->count();
            $deudaMaximaExacta = (float) $cuotasPendientesList->sum(fn($c) => $c->saldo_restante + $c->mora_pendiente);
            if ($deudaMaximaExacta <= 0) {
                $deudaMaximaExacta = $saldoPendiente;
            }

            $fechaPagoTeorica = $venta->created_at ? $venta->created_at->format('d') : ($venta->fecha_venta ? \Carbon\Carbon::parse($venta->fecha_venta)->format('d') : '01');

            $detallesLotes = $ventas->flatMap(function($v) {
                return $v->lotes->map(function ($lote) {
                    return [
                        'bloque' => $lote->bloque->nombre ?? 'N/A',
                        'lote'   => $lote->numero_lote,
                        'area'   => $lote->area_metros,
                    ];
                });
            });

            $cuentasBancarias = \App\Models\CuentaBancaria::where('estado', 'Activa')->orderBy('banco')->orderBy('moneda')->get();

            $data = [
                'cliente'          => $cliente,
                'venta'            => $venta,
                'ventas'           => $ventas,
                'esModoTodos'      => true,
                'todosAbonos'      => $todosAbonos,
                'totalAbonado'     => $totalAbonado,
                'saldoPendiente'   => $saldoPendiente,
                'deudaMaximaExacta'=> $deudaMaximaExacta,
                'cuotasPendientes' => max(0, $cuotasPendientes),
                'fechaPagoTeorica' => $fechaPagoTeorica,
                'detallesLotes'    => $detallesLotes,
                'cuotaSugeridaTotal' => $ventas->sum('cuota_mensual'),
                'cuentasBancarias' => $cuentasBancarias,
            ];

            return view('abonos.create', $data);
        }

        // Modo venta individual
        if ($ventaId) {
            $venta = $ventas->firstWhere('id_venta', $ventaId) ?? $ventas->first();
        } else {
            $venta = $ventas->first();
        }

        // Cargar los abonos ordenados por fecha de pago
        $venta->load(['abonos' => function ($query) {
            $query->orderBy('fecha_pago', 'desc')->orderBy('created_at', 'desc');
        }]);

        // Cálculos Financieros
        $totalAbonado = (float) $venta->abonos->sum('monto_abonado');
        $saldoPendiente = max(0, (float) $venta->precio_final - $totalAbonado);
        
        $cuotasPendientesList = \App\Models\Cuota::where('id_venta', $venta->id_venta)
            ->whereIn('estado', ['Pendiente', 'Mora', 'Parcial'])
            ->get();
        $cuotasPendientes = $cuotasPendientesList->count();
        $deudaMaximaExacta = (float) $cuotasPendientesList->sum(fn($c) => $c->saldo_restante + $c->mora_pendiente);
        if ($deudaMaximaExacta <= 0) {
            $deudaMaximaExacta = $saldoPendiente;
        }
        
        $fechaPagoTeorica = $venta->created_at ? $venta->created_at->format('d') : ($venta->fecha_venta ? \Carbon\Carbon::parse($venta->fecha_venta)->format('d') : '01'); 

        $detallesLotes = $venta->lotes->map(function ($lote) {
            return [
                'bloque' => $lote->bloque->nombre ?? 'N/A',
                'lote'   => $lote->numero_lote,
                'area'   => $lote->area_metros,
            ];
        });

        $cuentasBancarias = \App\Models\CuentaBancaria::where('estado', 'Activa')->orderBy('banco')->orderBy('moneda')->get();

        $data = [
            'cliente'          => $cliente,
            'venta'            => $venta,
            'ventas'           => $ventas,
            'esModoTodos'      => false,
            'todosAbonos'      => $venta->abonos,
            'totalAbonado'     => $totalAbonado,
            'saldoPendiente'   => $saldoPendiente,
            'deudaMaximaExacta'=> $deudaMaximaExacta,
            'cuotasPendientes' => max(0, $cuotasPendientes),
            'fechaPagoTeorica' => $fechaPagoTeorica,
            'detallesLotes'    => $detallesLotes,
            'cuotaSugeridaTotal' => $venta->cuota_mensual,
            'cuentasBancarias' => $cuentasBancarias,
        ];

        return view('abonos.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Cliente $cliente)
    {
        $request->validate([
            'monto_abonado' => 'required|numeric|min:0.01',
            'fecha_pago'    => 'required|date',
            'tipo_pago'     => 'required|string',
            'metodo_pago'   => 'required|string',
            'referencia'    => 'nullable|string',
            'cuenta_destino'=> 'nullable|string',
            'fecha_transferencia' => 'nullable|date',
            'comentario'    => 'nullable|string|max:1000',
            'ruta_recibo'   => 'nullable|file|mimes:jpeg,png,jpg,pdf,webp|max:10240',
        ]);

        $ruta_imagen = null;
        if ($request->hasFile('ruta_recibo')) {
            $ruta_imagen = $request->file('ruta_recibo')->store('abonos_recibos', 'public');
        }

        $ventasIds = $request->input('ventas_ids');
        $ventaId = $request->input('id_venta');

        // Determinar qué ventas se van a abonar
        if (!empty($ventasIds) && is_array($ventasIds)) {
            $ventasTarget = $cliente->ventas()->with('lotes')->whereIn('id_venta', $ventasIds)->where('estado_contrato', '!=', 'Rescindido')->get();
        } elseif ($ventaId && $ventaId !== 'todos') {
            $ventasTarget = $cliente->ventas()->with('lotes')->where('id_venta', $ventaId)->where('estado_contrato', '!=', 'Rescindido')->get();
        } else {
            $ventasTarget = $cliente->ventas()->with('lotes')->where('estado_contrato', '!=', 'Rescindido')->get();
        }

        if ($ventasTarget->isEmpty()) {
            return back()->with('error', 'No se encontraron contratos activos seleccionados para abonar.');
        }

        $fechaTransferencia = ($request->metodo_pago === 'Transferencia Bancaria' || $request->metodo_pago === 'Depósito Bancario') 
            ? ($request->fecha_transferencia ?: $request->fecha_pago) 
            : null;

        // Asegurar que la columna grupo_recibo exista antes de abrir la transacción
        if (!\Illuminate\Support\Facades\Schema::hasColumn('abonos', 'grupo_recibo')) {
            try {
                \Illuminate\Support\Facades\Schema::table('abonos', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->string('grupo_recibo', 64)->nullable()->index()->after('codigo_recibo');
                });
            } catch (\Exception $e) {}
        }
        $tieneGrupoRecibo = \Illuminate\Support\Facades\Schema::hasColumn('abonos', 'grupo_recibo');

        DB::beginTransaction();
        try {
            $montoTotalAbonado = (float)$request->monto_abonado;
            $totalVentas = $ventasTarget->count();

            // Si es una sola venta
            if ($totalVentas === 1) {
                $venta = $ventasTarget->first();

                // Validar deuda máxima
                $cuotasPendientes = \App\Models\Cuota::where('id_venta', $venta->id_venta)
                    ->whereIn('estado', ['Pendiente', 'Mora', 'Parcial'])
                    ->get();
                $maximoAPagar = (float) $cuotasPendientes->sum(fn($c) => $c->saldo_restante + $c->mora_pendiente);
                if ($maximoAPagar <= 0) {
                    $totalAbonadoPrev = (float) $venta->abonos()->sum('monto_abonado');
                    $maximoAPagar = max(0, (float)$venta->precio_final - $totalAbonadoPrev);
                }

                if ($maximoAPagar > 0 && round($montoTotalAbonado, 2) > round($maximoAPagar, 2)) {
                    if (DB::transactionLevel() > 0) DB::rollBack();
                    return back()->withInput()->with('error', 'El monto del abono ($' . number_format($montoTotalAbonado, 2) . ') supera la deuda pendiente ($' . number_format($maximoAPagar, 2) . ').');
                }

                $referenciaFinal = ($request->metodo_pago === 'Efectivo') 
                    ? ($request->referencia_efectivo_coment ?: 'Pago en Efectivo') 
                    : $request->referencia;

                $datosRecibo = Abono::generarSiguienteNumeroRecibo($venta->lotificacion_id ?? 1);

                $abonoData = [
                    'id_venta'      => $venta->id_venta,
                    'numero_recibo' => $datosRecibo['numero_recibo'],
                    'codigo_recibo' => $datosRecibo['codigo_recibo'],
                    'monto_abonado' => $montoTotalAbonado,
                    'fecha_pago'    => $request->fecha_pago,
                    'tipo_pago'     => $request->tipo_pago,
                    'metodo_pago'   => $request->metodo_pago,
                    'referencia'    => $referenciaFinal,
                    'cuenta_destino'=> $request->cuenta_destino,
                    'fecha_transferencia' => $fechaTransferencia,
                    'comentario'    => $request->comentario,
                    'es_migracion'  => $request->boolean('es_migracion', false),
                    'ruta_recibo'   => $ruta_imagen,
                    'user_id'       => auth()->id()
                ];

                $abono = Abono::create($abonoData);

                self::recalcularCuotas($venta->id_venta);

                if (DB::transactionLevel() > 0) DB::commit();
                \App\Models\Auditoria::log('Registró Abono', 'Abono', $abono->id_abono, "Recibo: {$datosRecibo['codigo_recibo']} - Monto: $" . number_format($montoTotalAbonado, 2) . " - " . $request->metodo_pago);
                return redirect()->route('registro.show', $cliente->id_cliente)
                    ->with('success', "¡Abono registrado exitosamente! Recibo N° {$datosRecibo['codigo_recibo']}")
                    ->with('imprimir_abonos', [$abono->id_abono]);
            }

            // Si son múltiples ventas (pago consolidado de varios lotes)
            // Validar deuda máxima consolidada
            $cuotasPendientesTotal = \App\Models\Cuota::whereIn('id_venta', $ventasTarget->pluck('id_venta'))
                ->whereIn('estado', ['Pendiente', 'Mora', 'Parcial'])
                ->get();
            $maximoConsolidado = (float) $cuotasPendientesTotal->sum(fn($c) => $c->saldo_restante + $c->mora_pendiente);
            if ($maximoConsolidado <= 0) {
                $totalAbonadoMultiPrev = (float) \App\Models\Abono::whereIn('id_venta', $ventasTarget->pluck('id_venta'))->sum('monto_abonado');
                $maximoConsolidado = max(0, (float) $ventasTarget->sum('precio_final') - $totalAbonadoMultiPrev);
            }

            if ($maximoConsolidado > 0 && round($montoTotalAbonado, 2) > round($maximoConsolidado, 2)) {
                if (DB::transactionLevel() > 0) DB::rollBack();
                return back()->withInput()->with('error', 'El monto total a ingresar ($' . number_format($montoTotalAbonado, 2) . ') supera la deuda total pendiente de los lotes seleccionados ($' . number_format($maximoConsolidado, 2) . ').');
            }

            // Distribuir el abono proporcional a la cuota mensual o equitativamente
            $sumaCuotas = $ventasTarget->sum('cuota_mensual');
            $acumulado = 0;
            $abonosCreadosIds = [];
            $referenciaBase = ($request->metodo_pago === 'Efectivo') 
                ? $request->referencia_efectivo_coment 
                : $request->referencia;

            $grupoRecibo = (string) \Illuminate\Support\Str::uuid();
            $datosRecibo = Abono::generarSiguienteNumeroRecibo($ventasTarget->first()->lotificacion_id ?? 1);

            foreach ($ventasTarget as $index => $v) {
                if ($index === $totalVentas - 1) {
                    $montoParaEsta = round($montoTotalAbonado - $acumulado, 2);
                } else {
                    if ($sumaCuotas > 0) {
                        $montoParaEsta = round($montoTotalAbonado * ($v->cuota_mensual / $sumaCuotas), 2);
                    } else {
                        $montoParaEsta = round($montoTotalAbonado / $totalVentas, 2);
                    }
                    $acumulado += $montoParaEsta;
                }

                if ($montoParaEsta <= 0) continue;

                $nombreLotes = $v->lotes->map(fn($l) => 'Lote '.$l->numero_lote)->implode(', ');
                $ref = $referenciaBase ? ($referenciaBase . ' [' . $nombreLotes . ']') : ('Pago Consolidado - ' . $nombreLotes);

                $abonoData = [
                    'id_venta'      => $v->id_venta,
                    'numero_recibo' => $datosRecibo['numero_recibo'],
                    'codigo_recibo' => $datosRecibo['codigo_recibo'],
                    'monto_abonado' => $montoParaEsta,
                    'fecha_pago'    => $request->fecha_pago,
                    'tipo_pago'     => $request->tipo_pago,
                    'metodo_pago'   => $request->metodo_pago,
                    'referencia'    => $ref,
                    'cuenta_destino'=> $request->cuenta_destino,
                    'fecha_transferencia' => $fechaTransferencia,
                    'comentario'    => $request->comentario,
                    'es_migracion'  => $request->boolean('es_migracion', false),
                    'ruta_recibo'   => $ruta_imagen,
                    'user_id'       => auth()->id()
                ];

                if ($tieneGrupoRecibo) {
                    $abonoData['grupo_recibo'] = $grupoRecibo;
                }

                $abono = Abono::create($abonoData);

                $abonosCreadosIds[] = $abono->id_abono;
                self::recalcularCuotas($v->id_venta);
            }

            if (DB::transactionLevel() > 0) DB::commit();
            \App\Models\Auditoria::log('Registró Abono Múltiple', 'Cliente', $cliente->id_cliente, "Monto Total: $" . number_format($montoTotalAbonado, 2) . " distribuido en {$totalVentas} lotes - Recibo N° {$datosRecibo['codigo_recibo']}");
            return redirect()->route('registro.show', $cliente->id_cliente)
                ->with('success', "¡Abono consolidado de $" . number_format($montoTotalAbonado, 2) . " registrado exitosamente para {$totalVentas} lotes!")
                ->with('imprimir_abonos', $abonosCreadosIds)
                ->with('imprimir_consolidado_id', $abonosCreadosIds[0] ?? null);

        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) DB::rollBack();
            return back()->withInput()->with('error', 'Error al registrar el abono: ' . $e->getMessage());
        }
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

        public function destroy($abono) 
    {
        abort_if(!auth()->user()->can('borrar-abonos'), 403, 'No tienes permiso para borrar abonos.');

     try {
        // Buscamos el abono por el ID que llega en la URL
        $registro = Abono::findOrFail($abono);
        $id_venta = $registro->id_venta;
        
        $registro->delete();
        
        // Recalcular el estado de las cuotas tras eliminar un abono
        self::recalcularCuotas($id_venta);

        \App\Models\Auditoria::log('Eliminó Abono', 'Abono', $abono, "Abono ID: " . $abono);
        return redirect()->back()->with('success', 'Abono eliminado correctamente. Saldos recalculados.');

        } catch (\Exception $e) {
         // Si hay error, lo mostramos para saber qué pasó
          return redirect()->back()->with('error', 'No se pudo eliminar: ' . $e->getMessage());
        }
    }

    public static function recalcularCuotas($id_venta) {
        $venta = \App\Models\Venta::withoutGlobalScope('lotificacion')->findOrFail($id_venta);

        // Rec.5: Registrar en auditoría cada vez que se recalculan cuotas
        \App\Models\Auditoria::log('Recalculó Cuotas', 'Venta', $id_venta,
            'Recálculo disparado para contrato #' . $id_venta .
            ' (cliente #' . $venta->id_cliente . ')'
        );
        
        // 1. Sincronizar fechas de vencimiento de las cuotas con la fecha base del contrato (Mes 0 para Cuota 1)
        $fechaBase = $venta->fecha_venta ?: ($venta->created_at ? $venta->created_at->format('Y-m-d') : now()->format('Y-m-d'));
        $fechaInicial = \Carbon\Carbon::parse($fechaBase);

        $cuotasExistentes = \App\Models\Cuota::where('id_venta', $id_venta)->orderBy('numero_cuota', 'asc')->get();
        if ($cuotasExistentes->isEmpty()) {
            \App\Http\Controllers\ClienteController::generarPlanCuotas($venta, $fechaBase);
            $cuotasExistentes = \App\Models\Cuota::where('id_venta', $id_venta)->orderBy('numero_cuota', 'asc')->get();
        } else {
            // Asegurar que las fechas de vencimiento inicien exactamente en fecha_venta (Mes 0)
            foreach ($cuotasExistentes as $c) {
                $nuevaFechaVenc = (clone $fechaInicial)->addMonths($c->numero_cuota - 1)->format('Y-m-d');
                if ($c->fecha_vencimiento !== $nuevaFechaVenc) {
                    $c->fecha_vencimiento = $nuevaFechaVenc;
                    $c->save();
                }
            }
        }

        // 2. Restaurar todas las cuotas a su estado original
        \App\Models\Cuota::where('id_venta', $id_venta)->update([
            'saldo_restante' => DB::raw('monto_total'),
            'mora_pagada' => 0,
            'mora_calculada' => 0,
            'estado' => 'Pendiente'
        ]);

        if ($venta->estado_contrato === 'Finalizado') {
            $venta->estado_contrato = 'Vigente';
            $venta->save();
        }

        // 3. Obtener abonos ordenados cronológicamente
        $abonos = \App\Models\Abono::where('id_venta', $id_venta)
            ->orderBy('fecha_pago', 'asc')->orderBy('id_abono', 'asc')->get();

        // 4. Reaplicar los abonos a las cuotas con la regla:
        // - Primero liquidar cuotas vencidas (y moras) a la fecha de pago en orden ascendente (1, 2, 3...)
        // - Si no es prima de capital, cubrir 1 cuota regular del mes actual (la próxima pendiente)
        // - Todo excedente o abono a capital se amortiza a las ÚLTIMAS CUOTAS en orden descendente (60, 59, 58...)
        foreach ($abonos as $abono) {
            $montoRestante = (float) $abono->monto_abonado;
            $fechaAbono = $abono->fecha_pago ? \Carbon\Carbon::parse($abono->fecha_pago)->format('Y-m-d') : now()->format('Y-m-d');
            
            // 4.1. Cubrir cuotas vencidas a la fecha del pago (o con mora) en orden ascendente
            $cuotasVencidas = \App\Models\Cuota::where('id_venta', $id_venta)
                ->where('saldo_restante', '>', 0)
                ->where('fecha_vencimiento', '<=', $fechaAbono)
                ->orderBy('numero_cuota', 'asc')
                ->get();

            foreach ($cuotasVencidas as $cuota) {
                if ($montoRestante <= 0) break;

                // Cobro de mora pendiente
                $moraPendiente = $cuota->mora_pendiente;
                if ($moraPendiente > 0) {
                    if ($montoRestante >= $moraPendiente) {
                        $montoRestante -= $moraPendiente;
                        $cuota->mora_pagada += $moraPendiente;
                    } else {
                        $cuota->mora_pagada += $montoRestante;
                        $montoRestante = 0;
                        $cuota->save();
                        continue;
                    }
                }

                // Cobro del saldo de la cuota vencida
                if ($montoRestante >= (float) $cuota->saldo_restante) {
                    $montoRestante -= (float) $cuota->saldo_restante;
                    $cuota->saldo_restante = 0;
                    $cuota->estado = 'Pagada';
                } else {
                    $cuota->saldo_restante = round((float)$cuota->saldo_restante - $montoRestante, 2);
                    $cuota->estado = 'Parcial';
                    $montoRestante = 0;
                }
                $cuota->save();
            }

            // 4.2. Si aún queda dinero y NO es una Prima pura de capital, cubrir 1 cuota regular más próxima
            $esTipoPrima = (stripos($abono->tipo_pago, 'prima') !== false && !stripos($abono->tipo_pago, 'cuota'));

            if ($montoRestante > 0 && !$esTipoPrima) {
                $proximaCuota = \App\Models\Cuota::where('id_venta', $id_venta)
                    ->where('saldo_restante', '>', 0)
                    ->orderBy('numero_cuota', 'asc')
                    ->first();

                if ($proximaCuota) {
                    if ($montoRestante >= (float) $proximaCuota->saldo_restante) {
                        $montoRestante -= (float) $proximaCuota->saldo_restante;
                        $proximaCuota->saldo_restante = 0;
                        $proximaCuota->estado = 'Pagada';
                    } else {
                        $proximaCuota->saldo_restante = round((float)$proximaCuota->saldo_restante - $montoRestante, 2);
                        $proximaCuota->estado = 'Parcial';
                        $montoRestante = 0;
                    }
                    $proximaCuota->save();
                }
            }

            // 4.3. Todo el EXCEDENTE restante ($montoRestante > 0) se amortiza a las ÚLTIMAS CUOTAS en orden descendente (60, 59, 58...)
            if ($montoRestante > 0) {
                $cuotasFinales = \App\Models\Cuota::where('id_venta', $id_venta)
                    ->where('saldo_restante', '>', 0)
                    ->orderBy('numero_cuota', 'desc')
                    ->get();

                foreach ($cuotasFinales as $cuotaFin) {
                    if ($montoRestante <= 0) break;

                    if ($montoRestante >= (float) $cuotaFin->saldo_restante) {
                        $montoRestante -= (float) $cuotaFin->saldo_restante;
                        $cuotaFin->saldo_restante = 0;
                        $cuotaFin->estado = 'Pagada';
                    } else {
                        $cuotaFin->saldo_restante = round((float)$cuotaFin->saldo_restante - $montoRestante, 2);
                        $cuotaFin->estado = 'Parcial';
                        $montoRestante = 0;
                    }
                    $cuotaFin->save();
                }
            }
        }

        // 5. Ajustar estados y moras actuales para cuotas no pagadas
        $cuotasFinal = \App\Models\Cuota::where('id_venta', $id_venta)->orderBy('numero_cuota', 'asc')->get();
        $hoyStr = now()->format('Y-m-d');

        foreach ($cuotasFinal as $cuota) {
            if ($cuota->saldo_restante <= 0) {
                $cuota->saldo_restante = 0;
                $cuota->mora_calculada = 0;
                $cuota->estado = 'Pagada';
                $cuota->save();
            } else {
                if ($cuota->fecha_vencimiento < $hoyStr) {
                    $cuota->recalcularMoraSegunConfiguracion();
                } else {
                    $cuota->mora_calculada = 0;
                    $cuota->estado = ((float)$cuota->saldo_restante < (float)$cuota->monto_total) ? 'Parcial' : 'Pendiente';
                    $cuota->save();
                }
            }
        }

        // 6. Revisar si con los abonos restantes se finaliza el contrato
        $saldoTotalRestante = \App\Models\Cuota::where('id_venta', $id_venta)->sum('saldo_restante');
        if ($saldoTotalRestante <= 0 && $venta->estado_contrato === 'Vigente') {
            $venta->estado_contrato = 'Finalizado';
            $venta->save();
        }
    }

    /**
     * Imprime el recibo consolidado para múltiples abonos/lotes.
     */
    public function imprimirReciboConsolidado(Request $request)
    {
        $ids = $request->input('ids');
        $grupo = $request->input('grupo');
        $abonoId = $request->input('abono_id');

        $abonos = collect();

        if ($grupo) {
            $abonos = Abono::withoutGlobalScope('lotificacion')
                ->where('grupo_recibo', $grupo)
                ->with(['venta' => fn($q) => $q->withoutGlobalScope('lotificacion')->with(['cliente', 'lotes.bloque', 'abonos', 'lotificacion'])])
                ->get();
        } elseif ($ids) {
            $idList = is_array($ids) ? $ids : explode(',', $ids);
            $abonos = Abono::withoutGlobalScope('lotificacion')
                ->whereIn('id_abono', $idList)
                ->with(['venta' => fn($q) => $q->withoutGlobalScope('lotificacion')->with(['cliente', 'lotes.bloque', 'abonos', 'lotificacion'])])
                ->get();
        } elseif ($abonoId) {
            $abono = Abono::withoutGlobalScope('lotificacion')
                ->with(['venta' => fn($q) => $q->withoutGlobalScope('lotificacion')->with(['cliente', 'lotes.bloque', 'abonos', 'lotificacion'])])
                ->findOrFail($abonoId);

            if (!empty($abono->grupo_recibo)) {
                $abonos = Abono::withoutGlobalScope('lotificacion')
                    ->where('grupo_recibo', $abono->grupo_recibo)
                    ->with(['venta' => fn($q) => $q->withoutGlobalScope('lotificacion')->with(['cliente', 'lotes.bloque', 'abonos', 'lotificacion'])])
                    ->get();
            } elseif ($abono->venta && $abono->venta->id_cliente && $abono->created_at) {
                $abonos = Abono::withoutGlobalScope('lotificacion')
                    ->whereHas('venta', fn($q) => $q->withoutGlobalScope('lotificacion')->where('id_cliente', $abono->venta->id_cliente))
                    ->where('fecha_pago', $abono->fecha_pago)
                    ->where('metodo_pago', $abono->metodo_pago)
                    ->whereBetween('created_at', [
                        $abono->created_at->copy()->subMinutes(5),
                        $abono->created_at->copy()->addMinutes(5)
                    ])
                    ->with(['venta' => fn($q) => $q->withoutGlobalScope('lotificacion')->with(['cliente', 'lotes.bloque', 'abonos', 'lotificacion'])])
                    ->get();
            }

            if ($abonos->isEmpty()) {
                $abonos = collect([$abono]);
            }
        }

        if ($abonos->isEmpty()) {
            abort(404, 'No se encontraron registros de abono para imprimir.');
        }

        if ($abonos->count() === 1) {
            return $this->imprimirRecibo($abonos->first()->id_abono, $request);
        }

        $datosConsolidados = $this->prepararDatosReciboConsolidado($abonos);
        return view('abonos.recibo_imprimir', $datosConsolidados);
    }

    /**
     * Prepara la estructura de datos unificada para el recibo consolidado de múltiples lotes.
     */
    public function prepararDatosReciboConsolidado($abonos)
    {
        $pagoPrincipal = $abonos->first();
        $ventas = $abonos->map(fn($a) => $a->venta)->filter()->unique('id_venta');
        $cliente = ($ventas->first() && $ventas->first()->cliente) ? $ventas->first()->cliente : (object) [
            'nombres_apellidos' => 'Cliente Desconocido',
            'token_seguimiento' => null
        ];
        $lotificacion = $ventas->first()?->lotificacion;

        // Listar todos los lotes de las ventas involucradas
        $lotesList = $ventas->flatMap(fn($v) => $v->lotes)->unique('id_lote');
        $lotesTexto = $lotesList->isNotEmpty()
            ? $lotesList->map(fn($l) => $l->numero_lote)->implode(', ')
            : 'N/A';

        $montoTotalAbono = (float) $abonos->sum('monto_abonado');

        // Totales combinados y proporcionales de todos los lotes involucrados en el recibo
        $valor_total = (float) $ventas->sum('precio_final');
        $total_abonado = (float) \App\Models\Abono::withoutGlobalScope('lotificacion')
            ->whereIn('id_venta', $ventas->pluck('id_venta'))
            ->sum('monto_abonado');
        $saldo_pendiente = max(0, $valor_total - $total_abonado);
        $cuota_mensual_total = (float) $ventas->sum('cuota_mensual');

        $plazo_meses = (int) ($ventas->max('plazo_meses') ?? 60);

        // Máximo de cuotas pendientes entre los contratos
        $abonos_faltantes = (int) (\App\Models\Cuota::whereIn('id_venta', $ventas->pluck('id_venta'))
            ->whereIn('estado', ['Pendiente', 'Mora', 'Parcial'])
            ->groupBy('id_venta')
            ->selectRaw('count(*) as cant')
            ->pluck('cant')
            ->max() ?? 0);

        $monto_en_letras = $this->convertirMontoALetras($montoTotalAbono);

        $imprimirDoble = (bool) setting('imprimir_doble_recibo', true, $lotificacion?->id);
        $proporcionDoble = (string) setting('proporcion_recibo_doble', '50_50', $lotificacion?->id);
        $mostrarQr = (bool) setting('mostrar_qr_recibo', true, $lotificacion?->id);
        $sufijoMoneda = (string) setting('sufijo_moneda_letras', 'DÓLARES NETOS', $lotificacion?->id);
        $leyendaPie = (string) setting('leyenda_pie_recibo', 'Conserve este comprobante como constancia legal de su pago.', $lotificacion?->id);
        $numeroReciboMostrar = $pagoPrincipal->numero_recibo_formateado;

        switch ($proporcionDoble) {
            case '55_45':
                $anchoCliente = 'calc(55% - 4px)';
                $anchoEmpresa = 'calc(45% - 4px)';
                break;
            case '60_40':
                $anchoCliente = 'calc(60% - 4px)';
                $anchoEmpresa = 'calc(40% - 4px)';
                break;
            case '65_35':
                $anchoCliente = 'calc(65% - 4px)';
                $anchoEmpresa = 'calc(35% - 4px)';
                break;
            case '50_50':
            default:
                $anchoCliente = 'calc(50% - 4px)';
                $anchoEmpresa = 'calc(50% - 4px)';
                break;
        }

        $pagoConsolidado = clone $pagoPrincipal;
        $pagoConsolidado->monto_abonado = $montoTotalAbono;

        return [
            'modoProvisional'      => false,
            'imprimirDoble'        => $imprimirDoble,
            'anchoCliente'         => $anchoCliente,
            'anchoEmpresa'         => $anchoEmpresa,
            'mostrarQr'            => $mostrarQr,
            'sufijoMoneda'         => $sufijoMoneda,
            'leyendaPie'           => $leyendaPie,
            'numeroReciboMostrar'  => $numeroReciboMostrar,
            'pago'                 => $pagoConsolidado,
            'cliente'              => $cliente,
            'venta'                => (object)[
                'cuota_mensual' => $cuota_mensual_total,
                'plazo_meses'   => $plazo_meses,
                'lotes'         => $lotesList,
                'precio_final'  => $valor_total,
                'id_venta'      => $pagoPrincipal->id_venta
            ],
            'lotes_texto'          => $lotesTexto,
            'lotes_count'          => $lotesList->count(),
            'valor_total'          => $valor_total,
            'total_abonado'        => $total_abonado,
            'saldo_pendiente'      => $saldo_pendiente,
            'abonos_faltantes'     => $abonos_faltantes,
            'monto_en_letras'      => $monto_en_letras,
            'lotificacion'         => $lotificacion,
            'esConsolidado'        => true,
        ];
    }

    public function imprimirRecibo($abono_id, Request $request = null)
    {
        if ($abono_id instanceof Request) {
            $temp = $abono_id;
            $abono_id = $request;
            $request = $temp;
        }
        $request = $request ?: request();

        // Carga el Abono e inmediatamente carga la Venta, Cliente y Lotes relacionados
        $abono = Abono::withoutGlobalScope('lotificacion')
            ->with(['venta' => function($q) {
                $q->withoutGlobalScope('lotificacion')->with([
                    'cliente' => fn($cq) => $cq->withoutGlobalScope('lotificacion'),
                    'lotes' => fn($lq) => $lq->withoutGlobalScope('lotificacion')->with(['bloque' => fn($bq) => $bq->withoutGlobalScope('lotificacion')]),
                    'abonos',
                    'lotificacion'
                ]);
            }])
            ->findOrFail($abono_id);

        // Si NO se fuerza la impresión individual (?individual=1), detectar si pertenece a un pago consolidado
        if (!$request->boolean('individual')) {
            $abonosGrupo = collect();

            if (!empty($abono->grupo_recibo)) {
                $abonosGrupo = Abono::withoutGlobalScope('lotificacion')
                    ->where('grupo_recibo', $abono->grupo_recibo)
                    ->with(['venta' => fn($q) => $q->withoutGlobalScope('lotificacion')->with(['cliente', 'lotes.bloque', 'abonos', 'lotificacion'])])
                    ->get();
            } elseif ($abono->venta && $abono->venta->id_cliente && $abono->created_at) {
                $abonosGrupo = Abono::withoutGlobalScope('lotificacion')
                    ->whereHas('venta', fn($q) => $q->withoutGlobalScope('lotificacion')->where('id_cliente', $abono->venta->id_cliente))
                    ->where('fecha_pago', $abono->fecha_pago)
                    ->where('metodo_pago', $abono->metodo_pago)
                    ->whereBetween('created_at', [
                        $abono->created_at->copy()->subMinutes(5),
                        $abono->created_at->copy()->addMinutes(5)
                    ])
                    ->with(['venta' => fn($q) => $q->withoutGlobalScope('lotificacion')->with(['cliente', 'lotes.bloque', 'abonos', 'lotificacion'])])
                    ->get();
            }

            if ($abonosGrupo->count() > 1) {
                $datosConsolidados = $this->prepararDatosReciboConsolidado($abonosGrupo);
                return view('abonos.recibo_imprimir', $datosConsolidados);
            }
        }

        $venta = $abono->venta;
        $lotificacion = $venta ? $venta->lotificacion : null;

        $cliente = ($venta && $venta->cliente) ? $venta->cliente : (object) [
            'nombres_apellidos' => 'Cliente Desconocido',
            'token_seguimiento' => null
        ];

        // Una venta puede tener varios lotes: se listan todos en el recibo
        $lotesTexto = $venta->lotes->isNotEmpty()
            ? $venta->lotes->map(function ($lote) {
                return $lote->numero_lote;
            })->implode(', ')
            : 'N/A';

        $valor_total = (float) $venta->precio_final;
        $total_abonado = (float) $venta->abonos->sum('monto_abonado');
        $saldo_pendiente = max(0, $valor_total - $total_abonado);
        $abonos_realizados = $venta->abonos->count();

        // El número real de cuotas pendientes basado en la tabla cuotas
        $abonos_faltantes = \App\Models\Cuota::where('id_venta', $venta->id_venta)
            ->whereIn('estado', ['Pendiente', 'Mora', 'Parcial'])
            ->count();

        $monto_en_letras = $this->convertirMontoALetras($abono->monto_abonado);

        $modoProvisional = ($abono->tipo_pago === 'Recibo Provisional');

        // Configuración de impresión de recibo del proyecto
        $imprimirDoble = (bool) setting('imprimir_doble_recibo', true, $lotificacion?->id);
        $proporcionDoble = (string) setting('proporcion_recibo_doble', '50_50', $lotificacion?->id);
        $mostrarQr = $modoProvisional ? false : (bool) setting('mostrar_qr_recibo', true, $lotificacion?->id);
        $sufijoMoneda = (string) setting('sufijo_moneda_letras', 'DÓLARES NETOS', $lotificacion?->id);
        $leyendaPie = (string) setting('leyenda_pie_recibo', 'Conserve este comprobante como constancia legal de su pago.', $lotificacion?->id);
        $numeroReciboMostrar = $abono->numero_recibo_formateado;

        // Calcular anchos porcentuales según la proporción elegida
        switch ($proporcionDoble) {
            case '55_45':
                $anchoCliente = 'calc(55% - 4px)';
                $anchoEmpresa = 'calc(45% - 4px)';
                break;
            case '60_40':
                $anchoCliente = 'calc(60% - 4px)';
                $anchoEmpresa = 'calc(40% - 4px)';
                break;
            case '65_35':
                $anchoCliente = 'calc(65% - 4px)';
                $anchoEmpresa = 'calc(35% - 4px)';
                break;
            case '50_50':
            default:
                $anchoCliente = 'calc(50% - 4px)';
                $anchoEmpresa = 'calc(50% - 4px)';
                break;
        }

        return view('abonos.recibo_imprimir', [
            'modoProvisional'      => $modoProvisional,
            'imprimirDoble'        => $imprimirDoble,
            'anchoCliente'         => $anchoCliente,
            'anchoEmpresa'         => $anchoEmpresa,
            'mostrarQr'            => $mostrarQr,
            'sufijoMoneda'         => $sufijoMoneda,
            'leyendaPie'           => $leyendaPie,
            'numeroReciboMostrar'  => $numeroReciboMostrar,
            'pago'                 => $abono,
            'cliente'              => $cliente,
            'venta'                => $venta,
            'lotes_texto'          => $lotesTexto,
            'lotes_count'          => $venta->lotes->count(),
            'lotificacion'         => $lotificacion,
            'valor_total'          => $valor_total,
            'total_abonado'        => $total_abonado,
            'saldo_pendiente'      => $saldo_pendiente,
            'abonos_faltantes'     => $abonos_faltantes,
            'monto_en_letras'      => $monto_en_letras,
            'esConsolidado'        => false,
        ]);
    }

    /**
     * Genera un recibo provisional (en blanco o con datos manuales) para llenado por la cajera.
     * Registra un abono de $0 tipo 'Recibo Provisional' en el historial de la venta
     * para que quede trazabilidad y registro auditable de los recibos emitidos.
     */
    public function reciboProvisional(\Illuminate\Http\Request $request, $id_venta)
    {
        $venta = \App\Models\Venta::withoutGlobalScope('lotificacion')
            ->with([
                'cliente' => fn($q) => $q->withoutGlobalScope('lotificacion'),
                'lotes' => fn($q) => $q->withoutGlobalScope('lotificacion')->with('bloque'),
                'lotificacion',
                'abonos',
            ])
            ->findOrFail($id_venta);

        $cliente      = $venta->cliente;
        $lotificacion = $venta->lotificacion;

        // Datos manuales ingresados desde el formulario (o null si se dejó en blanco)
        $dejarEnBlanco = $request->boolean('dejar_en_blanco');
        if ($dejarEnBlanco) {
            $nombreManual   = null;
            $montoManual    = null;
            $conceptoManual = null;
        } else {
            $nombreManual   = $request->filled('nombre_cliente') ? trim($request->input('nombre_cliente')) : ($cliente->nombres_apellidos ?? '');
            $montoManual    = $request->filled('monto') ? (float) $request->input('monto') : null;
            $conceptoManual = $request->filled('concepto') ? trim($request->input('concepto')) : null;
        }
        $fechaPago = $request->filled('fecha_pago') ? $request->input('fecha_pago') : now()->format('Y-m-d');
        $motivo    = $request->filled('motivo') ? trim($request->input('motivo')) : 'Generado manualmente';

        // Generar el siguiente número de recibo consecutivo
        $reciboData = \App\Models\Abono::generarSiguienteNumeroRecibo($venta->lotificacion_id);

        $comentarioFinal = "PROVISIONAL — " . $motivo;
        if ($montoManual) $comentarioFinal .= " | Monto: $" . number_format($montoManual, 2);
        if ($conceptoManual) $comentarioFinal .= " | Concepto: " . $conceptoManual;
        if ($nombreManual && $nombreManual !== ($cliente->nombres_apellidos ?? '')) $comentarioFinal .= " | Cliente: " . $nombreManual;

        // Guardar en historial como $0 para tener trazabilidad sin alterar saldos financieros
        $abono = \App\Models\Abono::create([
            'id_venta'      => $id_venta,
            'numero_recibo' => $reciboData['numero_recibo'],
            'codigo_recibo' => $reciboData['codigo_recibo'],
            'fecha_pago'    => $fechaPago,
            'monto_abonado' => 0,
            'tipo_pago'     => 'Recibo Provisional',
            'metodo_pago'   => 'Efectivo',
            'referencia'    => $montoManual ? 'Monto manual: $' . number_format($montoManual, 2) : 'Llenado en blanco',
            'comentario'    => $comentarioFinal,
            'user_id'       => auth()->id(),
        ]);

        // Texto de lotes
        $lotesTexto = $venta->lotes->isNotEmpty()
            ? $venta->lotes->map(fn($l) => $l->numero_lote)->implode(', ')
            : 'N/A';

        $sufijoMoneda = (string) setting('sufijo_moneda_letras', 'DÓLARES NETOS', $lotificacion?->id);
        $montoEnLetras = ($montoManual && $montoManual > 0) ? $this->convertirMontoALetras($montoManual) : '';

        return view('abonos.recibo_imprimir', [
            'modoProvisional'      => true,
            'imprimirDoble'        => true,
            'anchoCliente'         => null,
            'anchoEmpresa'         => null,
            'mostrarQr'            => false,
            'sufijoMoneda'         => $sufijoMoneda,
            'leyendaPie'           => (string) setting('leyenda_pie_recibo', 'Conserve este comprobante como constancia legal de su pago.', $lotificacion?->id),
            'numeroReciboMostrar'  => $abono->numero_recibo_formateado ?? $reciboData['codigo_recibo'],
            'pago'                 => $abono,
            'cliente'              => $cliente,
            'venta'                => $venta,
            'lotes_texto'          => $lotesTexto,
            'lotificacion'         => $lotificacion,
            'valor_total'          => 0,
            'total_abonado'        => 0,
            'saldo_pendiente'      => 0,
            'abonos_faltantes'     => 0,
            'monto_en_letras'      => $montoEnLetras,
            'nombreManual'         => $nombreManual,
            'montoManual'          => $montoManual,
            'conceptoManual'       => $conceptoManual,
        ]);
    }


        /**
         * Convierte el monto numérico a texto en palabras (sin sufijo de moneda redundante)
         */
        public function convertirMontoALetras($monto)
        {
            $monto = number_format((float) $monto, 2, '.', '');

            [$entero, $decimal] = explode('.', $monto);

            $entero = (int) $entero;
            $decimal = (int) $decimal;

            $texto = strtoupper(
                $this->numeroALetras($entero)
            );

            if ($decimal > 0) {
                $texto .= ' CON '
                    . strtoupper($this->numeroALetras($decimal))
                    . ' CENTAVOS';
            }

            return $texto;
        }

        
        public function numeroALetras($numero)
        {
    $unidades = [
        '',
        'uno',
        'dos',
        'tres',
        'cuatro',
        'cinco',
        'seis',
        'siete',
        'ocho',
        'nueve',
        'diez',
        'once',
        'doce',
        'trece',
        'catorce',
        'quince',
        'dieciséis',
        'diecisiete',
        'dieciocho',
        'diecinueve',
        'veinte'
    ];

    $decenas = [
        '',
        '',
        'veinte',
        'treinta',
        'cuarenta',
        'cincuenta',
        'sesenta',
        'setenta',
        'ochenta',
        'noventa'
    ];

    $centenas = [
        '',
        'ciento',
        'doscientos',
        'trescientos',
        'cuatrocientos',
        'quinientos',
        'seiscientos',
        'setecientos',
        'ochocientos',
        'novecientos'
    ];

    if ($numero == 0) {
        return 'cero';
    }

    if ($numero < 21) {
        return $unidades[$numero];
    }

    if ($numero < 100) {

        if ($numero % 10 == 0) {
            return $decenas[(int) ($numero / 10)];
        }

        if ($numero < 30) {
            return 'veinti' . $unidades[$numero - 20];
        }

        return $decenas[(int) ($numero / 10)]
            . ' y '
            . $unidades[$numero % 10];
    }

    if ($numero < 1000) {

        if ($numero == 100) {
            return 'cien';
        }

        return $centenas[(int) ($numero / 100)]
            . ($numero % 100 != 0
                ? ' ' . $this->numeroALetras($numero % 100)
                : '');
    }

    if ($numero < 1000000) {

        $miles = intdiv($numero, 1000);
        $resto = $numero % 1000;

        if ($miles == 1) {
            $texto = 'mil';
        } else {
            $texto = $this->numeroALetras($miles) . ' mil';
        }

        if ($resto > 0) {
            $texto .= ' ' . $this->numeroALetras($resto);
        }

        return $texto;
    }

    if ($numero < 1000000000) {

        $millones = intdiv($numero, 1000000);
        $resto = $numero % 1000000;

        if ($millones == 1) {
            $texto = 'un millón';
        } else {
            $texto = $this->numeroALetras($millones) . ' millones';
        }

        if ($resto > 0) {
            $texto .= ' ' . $this->numeroALetras($resto);
        }

        return $texto;
    }

    return 'cantidad demasiado grande';
        }

    /**
     * Módulo de Recibos Firmados por el Cliente
     */
    public function auditoriaRecibos(Request $request)
    {
        $estadoFirma = $request->input('estado_firma', 'todos');
        $search = $request->input('search');
        $fechaDesde = $request->input('fecha_desde');
        $fechaHasta = $request->input('fecha_hasta');

        $query = Abono::withoutGlobalScope('lotificacion')
            ->with([
                'venta' => fn($q) => $q->withoutGlobalScope('lotificacion')->with([
                    'cliente' => fn($cq) => $cq->withoutGlobalScope('lotificacion'),
                    'lotes' => fn($lq) => $lq->withoutGlobalScope('lotificacion')->with([
                        'bloque' => fn($bq) => $bq->withoutGlobalScope('lotificacion')
                    ]),
                    'lotificacion'
                ]),
                'user',
                'userReciboFirmado'
            ]);

        $activeLotId = session('lotificacion_id');
        if ($activeLotId) {
            $query->whereHas('venta', function($vq) use ($activeLotId) {
                $vq->withoutGlobalScope('lotificacion')->where('ventas.lotificacion_id', $activeLotId);
            });
        }

        if ($estadoFirma === 'firmados') {
            $query->whereNotNull('abonos.recibo_firmado');
        } elseif ($estadoFirma === 'pendientes') {
            $query->whereNull('abonos.recibo_firmado');
        }

        if ($fechaDesde) {
            $query->whereDate('abonos.fecha_pago', '>=', $fechaDesde);
        }
        if ($fechaHasta) {
            $query->whereDate('abonos.fecha_pago', '<=', $fechaHasta);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('abonos.numero_recibo', 'like', "%{$search}%")
                  ->orWhere('abonos.id_abono', 'like', "%{$search}%")
                  ->orWhere('abonos.referencia', 'like', "%{$search}%")
                  ->orWhereHas('venta', function($vq) use ($search) {
                      $vq->withoutGlobalScope('lotificacion')
                         ->where(function($vq2) use ($search) {
                             $vq2->whereHas('cliente', function($cq) use ($search) {
                                 $cq->withoutGlobalScope('lotificacion')
                                    ->where(function($cq2) use ($search) {
                                        $cq2->where('clientes.nombres_apellidos', 'like', "%{$search}%")
                                            ->orWhere('clientes.expediente_num', 'like', "%{$search}%")
                                            ->orWhere('clientes.pv_num', 'like', "%{$search}%")
                                            ->orWhere('clientes.identificacion', 'like', "%{$search}%");
                                    });
                             })->orWhereHas('lotes', function($lq) use ($search) {
                                 $lq->withoutGlobalScope('lotificacion')
                                    ->where('lotes.numero_lote', 'like', "%{$search}%");
                             });
                         });
                  });
            });
        }

        // Estadísticas globales
        $baseQuery = Abono::withoutGlobalScope('lotificacion');
        if ($activeLotId) {
            $baseQuery->whereHas('venta', function($vq) use ($activeLotId) {
                $vq->withoutGlobalScope('lotificacion')->where('ventas.lotificacion_id', $activeLotId);
            });
        }
        if ($fechaDesde) $baseQuery->whereDate('abonos.fecha_pago', '>=', $fechaDesde);
        if ($fechaHasta) $baseQuery->whereDate('abonos.fecha_pago', '<=', $fechaHasta);

        $totalRecibos = (clone $baseQuery)->count();
        $totalFirmados = (clone $baseQuery)->whereNotNull('abonos.recibo_firmado')->count();
        $totalPendientes = (clone $baseQuery)->whereNull('abonos.recibo_firmado')->count();
        $porcentajeCumplimiento = $totalRecibos > 0 ? round(($totalFirmados / $totalRecibos) * 100, 1) : 100;
        $montoTotalAuditoria = (clone $baseQuery)->sum('abonos.monto_abonado') ?? 0;

        $abonos = $query->orderBy('abonos.fecha_pago', 'desc')
            ->orderBy('abonos.id_abono', 'desc')
            ->paginate(20)
            ->withQueryString();

        return view('abonos.auditoria', compact(
            'abonos',
            'estadoFirma',
            'search',
            'fechaDesde',
            'fechaHasta',
            'totalRecibos',
            'totalFirmados',
            'totalPendientes',
            'porcentajeCumplimiento',
            'montoTotalAuditoria'
        ));
    }

    /**
     * Subir soporte/archivo del recibo firmado físicamente por el cliente
     */
    public function subirReciboFirmado(Request $request, $id)
    {
        $request->validate([
            'recibo_firmado' => 'required|file|mimes:jpeg,png,jpg,pdf,webp|max:15360',
        ], [
            'recibo_firmado.required' => 'Debe seleccionar un archivo (imagen o PDF).',
            'recibo_firmado.mimes' => 'El formato debe ser PDF, JPG, PNG o WEBP.',
            'recibo_firmado.max' => 'El tamaño del archivo no puede superar los 15MB.'
        ]);

        $abono = Abono::withoutGlobalScope('lotificacion')->findOrFail($id);

        if ($request->hasFile('recibo_firmado')) {
            if ($abono->recibo_firmado && Storage::disk('public')->exists($abono->recibo_firmado)) {
                Storage::disk('public')->delete($abono->recibo_firmado);
            }

            $rutaArchivo = $request->file('recibo_firmado')->store('recibos_firmados', 'public');
            
            $abono->update([
                'recibo_firmado' => $rutaArchivo,
                'fecha_recibo_firmado' => now(),
                'user_recibo_firmado_id' => auth()->id()
            ]);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Recibo firmado por el cliente guardado exitosamente para auditoría.',
                'url' => asset('storage/' . $abono->recibo_firmado)
            ]);
        }

        return redirect()->back()->with('success', 'Recibo firmado por el cliente guardado exitosamente para auditoría.');
    }

    /**
     * Eliminar el recibo firmado (por si se subió un archivo erróneo)
     */
    public function eliminarReciboFirmado(Request $request, $id)
    {
        $abono = Abono::withoutGlobalScope('lotificacion')->findOrFail($id);

        if ($abono->recibo_firmado && Storage::disk('public')->exists($abono->recibo_firmado)) {
            Storage::disk('public')->delete($abono->recibo_firmado);
        }

        $abono->update([
            'recibo_firmado' => null,
            'fecha_recibo_firmado' => null,
            'user_recibo_firmado_id' => null
        ]);

        return redirect()->back()->with('success', 'Recibo firmado eliminado correctamente.');
    }
}
