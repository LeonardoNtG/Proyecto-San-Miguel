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

        $clientesQuery = Cliente::with('ventas')->orderBy('id_cliente', 'desc');

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
        // Proyectos (Lotificaciones) disponibles
        $proyectos = \App\Models\Lotificacion::orderBy('nombre')->get();

        return view('registro', compact('proyectos'));
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
        'pv_num' => 'required|string|unique:clientes,pv_num|max:20',
        'expediente_num' => 'required|string|unique:clientes,expediente_num|max:20',    
        'nombres_apellidos' => 'required|string|max:255', // ¿Estás enviando este campo?
        'identificacion' => 'required|string|max:30', // Sin unique: una persona puede tener varios contratos
        'lotes_ids' => 'required|array|min:1|max:20', // NUEVA VALIDACIÓN
        'lotes_ids.*' => 'integer|exists:lotes,id_lote', // Asegura que los IDs sean válidos
        'extension_value' => 'required|numeric|min:0', // Validar el campo oculto
    ]);
    

    DB::beginTransaction();

    try {
          // CREAR EL CLIENTE (Igual)
         $cliente = Cliente::create([
        'expediente_num' => $request->expediente_num,
        'pv_num' => $request->pv_num,
        'nombres_apellidos' => $request->nombres_apellidos, 
        'identificacion' => $request->identificacion,       
        'telefono' => $request->telefono,                   
        'direccion' => $request->direccion,                 
         'estado_civil' => $request->estado_civil,          
        'oficio' => $request->oficio,                       
    ]);

        // Proyecto: se hereda del Bloque de los lotes seleccionados
        $primerLote = Lote::with('bloque')->whereIn('id_lote', $request->lotes_ids)->first();
        $lotificacionId = $primerLote?->bloque?->lotificacion_id;

        // CREA LA VENTA/PROMESA
        $venta = Venta::create([
            'id_cliente' => $cliente->id_cliente,
            'lotificacion_id' => $lotificacionId,
            'fecha_venta' => now(),
            'precio_final' => $request->precio_final,
            'plazo_meses' => $request->plazo_meses,
            'estado_contrato' => 'Vigente',
            'extension_lote' => $request->extension_value,
            'cuota_mensual' => $request->cuota_mensual,
        ]);

        // ASOCIA LOS LOTES A LA VENTA (A través del historial)
        Lote::whereIn('id_lote', $request->lotes_ids)->update([
            'estado' => 'Vendido',
        ]);
        
        foreach ($request->lotes_ids as $loteId) {
            \App\Models\HistorialLote::create([
                'id_lote' => $loteId,
                'id_venta' => $venta->id_venta,
                'estado' => 'Activo',
                'fecha_asignacion' => now(),
            ]);
        }

        // CREA EL PRIMER ABONO (Igual)
        $abonoInicial = Abono::create([
                'id_venta' => $venta->id_venta,
                'fecha_pago' => $request->fecha_ultimo_abono ?? now(),
                'monto_abonado' => $request->primer_abono,
                'tipo_pago' => 'Prima/Primer Abono',
                'metodo_pago' => $request->metodo_pago_prima ?? 'Efectivo',
                'referencia' => $request->referencia_prima ?? 'Registro Inicial de Venta',
                'cuenta_destino' => $request->cuenta_destino_prima ?? null,
                'user_id' => auth()->id()
        ]);

        // GENERAR PLAN DE PAGOS (CUOTAS)
        $plazoRestante = $venta->plazo_meses - 1;
        $saldoRestante = $venta->precio_final - $request->primer_abono;
        $cuotaMensual = $venta->cuota_mensual;

        if ($plazoRestante > 0 && $saldoRestante > 0) {
            $fechaVencimiento = \Carbon\Carbon::parse($abonoInicial->fecha_pago);
            
            for ($i = 1; $i <= $plazoRestante; $i++) {
                $fechaVencimiento->addMonth();
                
                // La última cuota puede variar ligeramente por redondeos, la ajustamos
                $montoCuota = ($i == $plazoRestante) ? $saldoRestante : $cuotaMensual;
                
                \App\Models\Cuota::create([
                    'id_venta' => $venta->id_venta,
                    'numero_cuota' => $i,
                    'fecha_vencimiento' => $fechaVencimiento->format('Y-m-d'),
                    'monto_total' => $montoCuota,
                    'capital' => $montoCuota, // Asumimos sin interés desglosado por ahora
                    'interes' => 0,
                    'saldo_restante' => $montoCuota,
                    'estado' => 'Pendiente',
                ]);
                
                $saldoRestante -= $montoCuota;
            }
        }

        DB::commit();

        return redirect()->route('registro.index')->with('success', 'Cliente y Ventas de múltiples lotes registrados exitosamente.');

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->withInput()->with('error', 'Ocurrió un error al registrar: ' . $e->getMessage());
    }
}

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Cliente $cliente)
    {
        
         $cliente->load(['ventas.lotes', 'ventas.cuotas', 'ventas.abonos' => function ($query) {
        
         $query->orderBy('created_at','asc', 'desc');
     }]);

        $cliente->ventas->each(function ($venta) {
          $venta->total_abonado = $venta->abonos->sum('monto_abonado');
        });

        return view('show', compact('cliente'));
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
    // Validacion de datos actualizados
    DB::beginTransaction();
    try {
        $cliente->update($request->only([
            'expediente_num', 'pv_num', 'nombres_apellidos', 'identificacion',
            'telefono', 'direccion', 'estado_civil', 'oficio'
        ]));

        $venta = $cliente->ventas()->first();
        if ($venta) {
    
        if ($venta->getOriginal('estado_contrato') === 'Rescindido') {
         $nuevoEstado = 'Rescindido';
         } else {
         $nuevoEstado = $request->input('estado_contrato');
            }

        $venta->update(['estado_contrato' => $nuevoEstado]);

         if ($nuevoEstado === 'Rescindido') {
         $loteIds = $venta->lotes()->pluck('lotes.id_lote')->toArray();

            if (!empty($loteIds)) {
            \App\Models\Lote::whereIn('id_lote', $loteIds)->update(['estado' => 'Disponible']);
         }
            }
    }
            DB::commit();
            return redirect()->route('registro.show', $cliente->id_cliente)
                         ->with('success', 'Información de Cliente y Estado de Venta actualizados.');
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
