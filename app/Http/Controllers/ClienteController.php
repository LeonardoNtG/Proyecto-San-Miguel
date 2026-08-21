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

        $clientesQuery = Cliente::with('ventas')
            ->orderBy('id_cliente', 'desc');

            if ($search) {
            $clientesQuery->where('expediente_num', 'like', "%{$search}%")
                          ->orWhere('nombres_apellidos', 'like', "%{$search}%")
                          ->orWhere('identificacion', 'like', "%{$search}%");
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
    
    public function create()
    {
        // Muestra todos los bloques disponibles
    $bloques = Bloque::all();

    // Pasa los bloques a la vista
    return view('registro', compact('bloques'));
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

        // CREA LA VENTA/PROMESA
       
        $venta = Venta::create([
            'id_cliente' => $cliente->id_cliente,
            'fecha_venta' => now(), 
            'precio_final' => $request->precio_final,
            'plazo_meses' => $request->plazo_meses,
            'estado_contrato' => 'Vigente',
            'extension_lote' => $request->extension_value,
            'cuota_mensual' => $request->cuota_mensual,
        ]);

        // ASOCIA LOS LOTES A LA VENTA (un lote pertenece a una única venta)
        Lote::whereIn('id_lote', $request->lotes_ids)->update([
            'id_venta' => $venta->id_venta,
            'estado' => 'Vendido',
        ]);

        // CREA EL PRIMER ABONO (Igual)
        Abono::create([
                'id_venta' => $venta->id_venta,
                'fecha_pago' => $request->fecha_ultimo_abono ?? now(),
                'monto_abonado' => $request->primer_abono,
                'tipo_pago' => 'Prima/Primer Abono',
                'referencia' => 'Registro Inicial de Venta',
        ]);

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
        
         $cliente->load(['ventas.lotes', 'ventas.abonos' => function ($query) {
        
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
