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
    public function create(Cliente $cliente)
    {
        
        //Cargar Venta Activa y Abonos Relacionados
        $venta = $cliente->ventas->first();

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
        
        // Asumiendo pagos mensuales 
        $cuotasPagadas = floor($totalAbonado / $venta->cuota_mensual); 
        $cuotasPendientes = $venta->plazo_meses - $cuotasPagadas;
        
        // Fecha de Pago 
        $fechaPagoTeorica = $venta->created_at->format('d'); 

        // Obtener Detalles de Lotes para mostrar en el encabezado
        $detallesLotes = $venta->lotes->map(function ($lote) {
            return [
                'bloque' => $lote->bloque->nombre,
                'lote' => $lote->numero_lote,
                'area' => $lote->area_metros,
            ];
        });

        // Preparar datos para la vista
        $data = [
            'cliente' => $cliente,
            'venta' => $venta,
            'totalAbonado' => $totalAbonado,
            'saldoPendiente' => $saldoPendiente,
            'cuotasPendientes' => max(0, $cuotasPendientes), // Asegura que no sea negativo
            'fechaPagoTeorica' => $fechaPagoTeorica,
            'detallesLotes' => $detallesLotes,
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
    // 1. Validar (El campo de imagen es 'nullable')
    $validatedData = $request->validate([
        'monto_abonado' => 'required|numeric|min:0.01',
        'fecha_pago' => 'required|date',
        'tipo_pago' => ['required', Rule::in(['Mensualidad', 'Extraordinario'])],
        'referencia' => 'nullable|string|max:255',
        'ruta_recibo' => 'nullable|image|max:2048', // max: 2MB
    ]);

    $venta = $cliente->ventas->first();
    if (!$venta) {
        return back()->with('error', 'No se encontró una venta activa para este cliente.');
    }
    
    DB::beginTransaction();
    try {
        $rutaRecibo = null;

        // 2. Manejo Condicional de la Subida de Archivos
        if ($request->hasFile('ruta_recibo')) {
            // Guardar la imagen en el disco 'public' dentro de una carpeta 'abonos_recibos'
            $rutaRecibo = $request->file('ruta_recibo')->store('abonos_recibos', 'public');
            // La variable $rutaRecibo ahora contiene la ruta dentro del storage (ej: abonos_recibos/imagen_hash.jpg)
        }

        // 3. Crear el Abono
        Abono::create([
            'id_venta' => $venta->id_venta,
            'fecha_pago' => $validatedData['fecha_pago'],
            'monto_abonado' => $validatedData['monto_abonado'],
            'tipo_pago' => $validatedData['tipo_pago'],
            'referencia' => $validatedData['referencia'],
            'ruta_recibo' => $rutaRecibo, // Se guarda la ruta o null
        ]);

        // 4. (Opcional) Lógica de actualización de estado si se liquida el saldo
        // ...

        DB::commit();
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
     try {
        // Buscamos el abono por el ID que llega en la URL
        $registro = Abono::findOrFail($abono);
        
        $registro->delete();

        return redirect()->back()->with('success', 'Abono eliminado correctamente.');

        } catch (\Exception $e) {
         // Si hay error, lo mostramos para saber qué pasó
          return redirect()->back()->with('error', 'No se pudo eliminar: ' . $e->getMessage());
        }
    }

    public function imprimirRecibo($abono_id)
{
   // Carga el Abono e inmediatamente carga la Venta y el Cliente relacionado
    $abono = Abono::with('venta.cliente')->findOrFail($abono_id);
    
    $cliente = $abono->venta->cliente ?? null;
    
    // Crea un objeto genérico si el cliente no se encontró (Seguridad)
    if (!$cliente) {
        $cliente = (object) ['nombres_apellidos' => 'Cliente Desconocido'];
    }

    // Procesa el monto a letras (mantener la seguridad)
    $monto_en_letras = method_exists($this, 'convertirMontoALetras') 
                       ? $this->convertirMontoALetras($abono->monto_abonado) 
                       : 'CANTIDAD EN PALABRAS N/A';
    
    return view('abonos.recibo_imprimir', [
        'pago' => $abono,
        'cliente' => $cliente, 
        'monto_en_letras' => $monto_en_letras
    ]);
}

// Tendrías que definir esta función en algún lugar (puede ser en el controlador 
// o en un Helper) o usar una librería de terceros.
private function convertirMontoALetras($monto_en_letras) {
    // Ejemplo muy simple y no funcional para propósitos de ilustración:
    // if ($monto == 200) return 'Doscientos dólares'; 
    // ... Implementa tu lógica aquí o usa un paquete como 'kwn/number-to-words'
    return 'CANTIDAD EN PALABRAS AQUÍ';
}
}
