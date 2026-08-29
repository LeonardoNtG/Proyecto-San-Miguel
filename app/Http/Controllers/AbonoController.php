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
        // Si el cliente tiene múltiples ventas, permitir seleccionar cuál pagar
        $ventas = $cliente->ventas()->with(['lotes.bloque'])->where('estado_contrato', '!=', 'Rescindido')->get();

        // Si se especifica id_venta en la URL, usarla; si no, tomar la primera activa
        $ventaId = $request->get('venta_id');
        if ($ventaId) {
            $venta = $ventas->firstWhere('id_venta', $ventaId);
        } else {
            $venta = $ventas->first();
        }

        if (!$venta) {
            return redirect()->route('registro.index')->with('error', 'El cliente no tiene una venta activa para registrar abonos.');
        }

        // Cargar los abonos ordenados por fecha de pago
        $venta->load(['abonos' => function ($query) {
            $query->orderBy('created_at', 'asc', 'desc');
        }]);

        // Cálculos Financieros
        $totalAbonado = $venta->abonos->sum('monto_abonado');
        $saldoPendiente = $venta->precio_final - $totalAbonado;
        
        // El número real de cuotas pendientes basado en la tabla cuotas
        $cuotasPendientes = \App\Models\Cuota::where('id_venta', $venta->id_venta)
            ->whereIn('estado', ['Pendiente', 'Mora', 'Parcial'])
            ->count();
        
        // Fecha de Pago 
        $fechaPagoTeorica = $venta->created_at->format('d'); 

        // Obtener Detalles de Lotes para mostrar en el encabezado
        $detallesLotes = $venta->lotes->map(function ($lote) {
            return [
                'bloque' => $lote->bloque->nombre,
                'lote'   => $lote->numero_lote,
                'area'   => $lote->area_metros,
            ];
        });

        // Preparar datos para la vista
        $data = [
            'cliente'          => $cliente,
            'venta'            => $venta,
            'ventas'           => $ventas,            // todas las ventas del cliente
            'totalAbonado'     => $totalAbonado,
            'saldoPendiente'   => $saldoPendiente,
            'cuotasPendientes' => max(0, $cuotasPendientes),
            'fechaPagoTeorica' => $fechaPagoTeorica,
            'detallesLotes'    => $detallesLotes,
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
    // Buscamos la primera venta del cliente (asumimos 1 venta por cliente para el abono)
    $venta = $cliente->ventas->first();
    if (!$venta) {
        return back()->with('error', 'No se encontró una venta activa para este cliente.');
    }
    if ($venta && $venta->estado_contrato === 'Rescindido') {
        return back()->with('error', 'Operación denegada: No se pueden registrar abonos en un contrato rescindido.');
    }
    
    DB::beginTransaction();
    try {
        $request->validate([
            'monto_abonado' => 'required|numeric|min:0.01',
            'fecha_pago'    => 'required|date',
            'tipo_pago'     => 'required|string',
            'metodo_pago'   => 'required|string',
            'referencia'    => 'nullable|string',
            'cuenta_destino'=> 'nullable|string',
            'recibo_imagen' => 'nullable|image|max:5120',
        ]);

        $ruta_imagen = null;
        if ($request->hasFile('ruta_recibo')) {
            $ruta_imagen = $request->file('ruta_recibo')->store('abonos_recibos', 'public');
        }

        // Calcular máximo a pagar (Capital restante + Mora pendiente)
        $cuotasPendientes = \App\Models\Cuota::where('id_venta', $venta->id_venta)
            ->whereIn('estado', ['Pendiente', 'Mora', 'Parcial'])
            ->orderBy('numero_cuota', 'asc')
            ->get();

        $maximoAPagar = $cuotasPendientes->sum(function($cuota) {
            return $cuota->saldo_restante + $cuota->mora_pendiente;
        });

        if (round($request->monto_abonado, 2) > round($maximoAPagar, 2)) {
            return back()->withInput()->with('error', 'El monto del abono ($' . number_format($request->monto_abonado, 2) . ') supera la deuda total pendiente ($' . number_format($maximoAPagar, 2) . ' incluyendo mora).');
        }

        // Crear el abono base
        $abono = Abono::create([
            'id_venta'      => $venta->id_venta,
            'monto_abonado' => $request->monto_abonado,
            'fecha_pago'    => $request->fecha_pago,
            'tipo_pago'     => $request->tipo_pago,
            'metodo_pago'   => $request->metodo_pago,
            'referencia'    => $request->referencia,
            'cuenta_destino'=> $request->cuenta_destino,
            'ruta_recibo'   => $ruta_imagen,
            'user_id'       => auth()->id()
        ]);

        // APLICAR ABONO A LAS CUOTAS
        $montoRestante = $request->monto_abonado;
        $cuotasPendientes = \App\Models\Cuota::where('id_venta', $venta->id_venta)
            ->whereIn('estado', ['Pendiente', 'Mora', 'Parcial'])
            ->orderBy('numero_cuota', 'asc')
            ->get();

        foreach ($cuotasPendientes as $cuota) {
            if ($montoRestante <= 0) break;

            // 1. Pagar Mora Pendiente primero
            $moraPendiente = $cuota->mora_pendiente;
            if ($moraPendiente > 0) {
                if ($montoRestante >= $moraPendiente) {
                    $montoRestante -= $moraPendiente;
                    $cuota->mora_pagada += $moraPendiente;
                } else {
                    $cuota->mora_pagada += $montoRestante;
                    $montoRestante = 0;
                    $cuota->save();
                    continue; // Se acabó el dinero, no abonamos a saldo_restante
                }
            }

            // 2. Pagar Saldo Restante (Capital)
            if ($montoRestante >= $cuota->saldo_restante) {
                // Paga la cuota completa
                $montoRestante -= $cuota->saldo_restante;
                $cuota->saldo_restante = 0;
                $cuota->estado = 'Pagada';
            } else {
                // Pago parcial de la cuota
                $cuota->saldo_restante -= $montoRestante;
                // Si la cuota ya estaba en mora y pagamos toda la mora pero solo una parte del capital, sigue en Mora o Parcial?
                // Según lógica bancaria, si aún debe capital y está atrasada, sigue en mora. Lo mantenemos como estaba.
                $cuota->estado = ($cuota->estado === 'Mora') ? 'Mora' : 'Parcial';
                $montoRestante = 0;
            }
            $cuota->save();
        }

        // Si después de aplicar a todas las cuotas, ya no hay cuotas pendientes y el saldo es 0, finalizar venta
        $saldoTotalRestante = \App\Models\Cuota::where('id_venta', $venta->id_venta)->sum('saldo_restante');
        if ($saldoTotalRestante <= 0 && $venta->estado_contrato === 'Vigente') {
            $venta->estado_contrato = 'Finalizado';
            $venta->save();
        }

        DB::commit();
        \App\Models\Auditoria::log('Registró Abono', 'Abono', $abono->id_abono, "Monto: $" . number_format($request->monto_abonado, 2) . " - " . $request->metodo_pago);
        return redirect()->back()->with('success', 'Abono registrado exitosamente.');

    } catch (\Exception $e) {
        DB::rollBack();
        // Si el archivo se subió antes de la falla, debe ser borrado (Lógica más avanzada)
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
        // Carga el Abono e inmediatamente carga la Venta y el Cliente relacionado
         $abono = Abono::with('venta.cliente','venta.lotes.bloque','venta.abonos', 'venta.lotificacion')->findOrFail($abono_id);

         $venta = $abono->venta;
         $lotificacion = $venta->lotificacion;

         $cliente = $abono->venta->cliente ?? null;

         // Crea un objeto genérico si el cliente no se encontró (Seguridad)
         if (!$cliente) {
             $cliente = (object) ['nombres_apellidos' => 'Cliente Desconocido'];
         }

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
    
              return view('abonos.recibo_imprimir', [

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
