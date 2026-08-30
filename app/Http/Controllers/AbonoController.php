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

            $totalAbonado = $todosAbonos->sum('monto_abonado');
            $precioTotal = $ventas->sum('precio_final');
            $saldoPendiente = $precioTotal - $totalAbonado;

            $cuotasPendientesList = \App\Models\Cuota::whereIn('id_venta', $ventaIds)
                ->whereIn('estado', ['Pendiente', 'Mora', 'Parcial'])
                ->get();
            $cuotasPendientes = $cuotasPendientesList->count();
            $deudaMaximaExacta = (float) $cuotasPendientesList->sum(fn($c) => $c->saldo_restante + $c->mora_pendiente);

            $fechaPagoTeorica = $venta->created_at->format('d');

            $detallesLotes = $ventas->flatMap(function($v) {
                return $v->lotes->map(function ($lote) {
                    return [
                        'bloque' => $lote->bloque->nombre ?? 'N/A',
                        'lote'   => $lote->numero_lote,
                        'area'   => $lote->area_metros,
                    ];
                });
            });

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
        $totalAbonado = $venta->abonos->sum('monto_abonado');
        $saldoPendiente = $venta->precio_final - $totalAbonado;
        
        $cuotasPendientesList = \App\Models\Cuota::where('id_venta', $venta->id_venta)
            ->whereIn('estado', ['Pendiente', 'Mora', 'Parcial'])
            ->get();
        $cuotasPendientes = $cuotasPendientesList->count();
        $deudaMaximaExacta = (float) $cuotasPendientesList->sum(fn($c) => $c->saldo_restante + $c->mora_pendiente);
        
        $fechaPagoTeorica = $venta->created_at->format('d'); 

        $detallesLotes = $venta->lotes->map(function ($lote) {
            return [
                'bloque' => $lote->bloque->nombre ?? 'N/A',
                'lote'   => $lote->numero_lote,
                'area'   => $lote->area_metros,
            ];
        });

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
                $maximoAPagar = $cuotasPendientes->sum(fn($c) => $c->saldo_restante + $c->mora_pendiente);

                if (round($montoTotalAbonado, 2) > round($maximoAPagar, 2)) {
                    return back()->withInput()->with('error', 'El monto del abono ($' . number_format($montoTotalAbonado, 2) . ') supera la deuda pendiente ($' . number_format($maximoAPagar, 2) . ').');
                }

                $referenciaFinal = ($request->metodo_pago === 'Efectivo') 
                    ? ($request->referencia_efectivo_coment ?: 'Pago en Efectivo') 
                    : $request->referencia;

                $abono = Abono::create([
                    'id_venta'      => $venta->id_venta,
                    'monto_abonado' => $montoTotalAbonado,
                    'fecha_pago'    => $request->fecha_pago,
                    'tipo_pago'     => $request->tipo_pago,
                    'metodo_pago'   => $request->metodo_pago,
                    'referencia'    => $referenciaFinal,
                    'cuenta_destino'=> $request->cuenta_destino,
                    'ruta_recibo'   => $ruta_imagen,
                    'user_id'       => auth()->id()
                ]);

                self::recalcularCuotas($venta->id_venta);

                DB::commit();
                \App\Models\Auditoria::log('Registró Abono', 'Abono', $abono->id_abono, "Monto: $" . number_format($montoTotalAbonado, 2) . " - " . $request->metodo_pago);
                return redirect()->route('registro.show', $cliente->id_cliente)
                    ->with('success', '¡Abono registrado exitosamente!')
                    ->with('imprimir_abonos', [$abono->id_abono]);
            }

            // Si son múltiples ventas (pago consolidado de varios lotes)
            // Validar deuda máxima consolidada
            $cuotasPendientesTotal = \App\Models\Cuota::whereIn('id_venta', $ventasTarget->pluck('id_venta'))
                ->whereIn('estado', ['Pendiente', 'Mora', 'Parcial'])
                ->get();
            $maximoConsolidado = $cuotasPendientesTotal->sum(fn($c) => $c->saldo_restante + $c->mora_pendiente);

            if (round($montoTotalAbonado, 2) > round($maximoConsolidado, 2)) {
                return back()->withInput()->with('error', 'El monto total a ingresar ($' . number_format($montoTotalAbonado, 2) . ') supera la deuda total pendiente de los lotes seleccionados ($' . number_format($maximoConsolidado, 2) . ').');
            }

            // Distribuir el abono proporcional a la cuota mensual o equitativamente
            $sumaCuotas = $ventasTarget->sum('cuota_mensual');
            $acumulado = 0;
            $abonosCreadosIds = [];
            $referenciaBase = ($request->metodo_pago === 'Efectivo') 
                ? $request->referencia_efectivo_coment 
                : $request->referencia;

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

                $abono = Abono::create([
                    'id_venta'      => $v->id_venta,
                    'monto_abonado' => $montoParaEsta,
                    'fecha_pago'    => $request->fecha_pago,
                    'tipo_pago'     => $request->tipo_pago,
                    'metodo_pago'   => $request->metodo_pago,
                    'referencia'    => $ref,
                    'cuenta_destino'=> $request->cuenta_destino,
                    'ruta_recibo'   => $ruta_imagen,
                    'user_id'       => auth()->id()
                ]);

                $abonosCreadosIds[] = $abono->id_abono;
                self::recalcularCuotas($v->id_venta);
            }

            DB::commit();
            \App\Models\Auditoria::log('Registró Abono Múltiple', 'Cliente', $cliente->id_cliente, "Monto Total: $" . number_format($montoTotalAbonado, 2) . " distribuido en {$totalVentas} lotes - " . $request->metodo_pago);
            return redirect()->route('registro.show', $cliente->id_cliente)
                ->with('success', "¡Abono de \${$montoTotalAbonado} registrado exitosamente y distribuido entre los {$totalVentas} lotes seleccionados!")
                ->with('imprimir_abonos', $abonosCreadosIds);

        } catch (\Exception $e) {
            DB::rollBack();
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
        $venta = \App\Models\Venta::findOrFail($id_venta);
        
        // 1. Restaurar todas las cuotas a su estado original
        \App\Models\Cuota::where('id_venta', $id_venta)->update([
            'saldo_restante' => DB::raw('monto_total'),
            'mora_pagada' => 0,
            'estado' => 'Pendiente'
        ]);

        if ($venta->estado_contrato === 'Finalizado') {
            $venta->estado_contrato = 'Vigente';
            $venta->save();
        }

        // 2. Obtener abonos ordenados cronológicamente
        $abonos = \App\Models\Abono::where('id_venta', $id_venta)
            ->orderBy('fecha_pago', 'asc')->orderBy('id_abono', 'asc')->get();

        // 3. Reaplicar los abonos a las cuotas
        foreach($abonos as $abono) {
            $montoRestante = $abono->monto_abonado;
            
            $cuotasPendientes = \App\Models\Cuota::where('id_venta', $id_venta)
                ->whereIn('estado', ['Pendiente', 'Mora', 'Parcial'])
                ->orderBy('numero_cuota', 'asc')
                ->get();

            foreach ($cuotasPendientes as $cuota) {
                if ($montoRestante <= 0) break;

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

                if ($montoRestante >= $cuota->saldo_restante) {
                    $montoRestante -= $cuota->saldo_restante;
                    $cuota->saldo_restante = 0;
                    $cuota->estado = 'Pagada';
                } else {
                    $cuota->saldo_restante -= $montoRestante;
                    $cuota->estado = ($cuota->estado === 'Mora') ? 'Mora' : 'Parcial';
                    $montoRestante = 0;
                }
                $cuota->save();
            }
        }

        // 4. Revisar si con los abonos restantes se finaliza el contrato
        $saldoTotalRestante = \App\Models\Cuota::where('id_venta', $id_venta)->sum('saldo_restante');
        if ($saldoTotalRestante <= 0 && $venta->estado_contrato === 'Vigente') {
            $venta->estado_contrato = 'Finalizado';
            $venta->save();
        }
    }

         public function imprimirRecibo($abono_id)
        {
            // Carga el Abono e inmediatamente carga la Venta, Cliente y Lotes relacionados (libre de scopes para poder reimprimir cualquier recibo)
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

            $venta = $abono->venta;
            $lotificacion = $venta ? $venta->lotificacion : null;

            $cliente = ($venta && $venta->cliente) ? $venta->cliente : (object) [
                'nombres_apellidos' => 'Cliente Desconocido',
                'token_seguimiento' => null
            ];

            // Una venta puede tener varios lotes: se listan todos en el recibo (solo el número/nombre guardado para ahorrar espacio)
            $lotesTexto = $venta->lotes->isNotEmpty()
                ? $venta->lotes->map(function ($lote) {
                    return $lote->numero_lote;
                })->implode(', ')
                : 'N/A';

            $valor_total = (float) $venta->precio_final;
        
             $total_abonado = (float) $venta->abonos->sum('monto_abonado');

             $saldo_pendiente = max(
                 0,
                $valor_total - $total_abonado
                );

                 $abonos_realizados = $venta->abonos->count();

                 // El número real de cuotas pendientes basado en la tabla cuotas
                 $abonos_faltantes = \App\Models\Cuota::where('id_venta', $venta->id_venta)
                     ->whereIn('estado', ['Pendiente', 'Mora', 'Parcial'])
                     ->count();
         // Procesa el monto a letras (mantener la seguridad)
         $monto_en_letras = method_exists($this, 'convertirMontoALetras') 
                              ? $this->convertirMontoALetras($abono->monto_abonado) 
                              : 'CANTIDAD EN PALABRAS N/A';
    
         // Configuración de impresión de recibo del proyecto (Doble vía o Recibo Único 100%)
         $imprimirDoble = (bool) setting('imprimir_doble_recibo', true, $lotificacion?->id);

         return view('abonos.recibo_imprimir', [
            // Configuración
            'imprimirDoble' => $imprimirDoble,

            // Abono actual
            'pago' => $abono,

            // Cliente
            'cliente' => $cliente,

            // Venta
            'venta' => $venta,

            // Lotes asociados a la venta (texto "Bloque-Lote, Bloque-Lote, ...")
            'lotes_texto' => $lotesTexto,
            
            // Lotificación asociada
            'lotificacion' => $lotificacion,

            // Datos económicos
            'valor_total' => $valor_total,
            'total_abonado' => $total_abonado,
            'saldo_pendiente' => $saldo_pendiente,
            'abonos_faltantes' => $abonos_faltantes,

            // Monto actual en letras
            'monto_en_letras' => $monto_en_letras,
        ]);
}


        private function convertirMontoALetras($monto)
{
    $monto = number_format((float) $monto, 2, '.', '');

    [$entero, $decimal] = explode('.', $monto);

    $entero = (int) $entero;
    $decimal = (int) $decimal;

    $texto = strtoupper(
        $this->numeroALetras($entero)
    );

    if ($decimal > 0) {

        $texto .= ' DÓLARES CON '
            . strtoupper($this->numeroALetras($decimal))
            . ' CENTAVOS';

    } else {

        $texto .= ' DÓLARES';

    }

    return $texto;
}

        
        private function numeroALetras($numero)
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
}
