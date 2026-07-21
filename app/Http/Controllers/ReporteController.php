<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Abono;
use App\Models\Salida;
use App\Models\CierreCaja;
use Carbon\Carbon;

class ReporteController extends Controller
{
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index(Request $request)
    {
        // Gestionar la fecha (por defecto hoy)
        $fecha = $request->get('fecha', Carbon::today()->format('Y-m-d'));
        $fechaAyer = Carbon::parse($fecha)->subDay()->format('Y-m-d');

        // Obtener Saldo Inicial (Cierre de ayer)
        $cierreAyer = CierreCaja::where('fecha', $fechaAyer)->first();
        $saldoInicial = $cierreAyer ? $cierreAyer->saldo_final : 0;

        // Calcula Ingresos (Abonos registrados hoy)
        $ingresosHoy = Abono::whereDate('fecha_pago', $fecha)->sum('monto_abonado');

        // Calcular Egresos (Salidas manuales)
        $listaSalidas = Salida::whereDate('fecha', $fecha)->get();
        $egresosHoy = $listaSalidas->sum('monto');

        // Totales Dinámicos
        $efectivoTotalSuma = $saldoInicial + $ingresosHoy;
        $saldoFinalCaja = $efectivoTotalSuma - $egresosHoy;

        return view('reportes.diario', compact(
            'fecha', 'saldoInicial', 'ingresosHoy', 
            'egresosHoy', 'listaSalidas', 'efectivoTotalSuma', 'saldoFinalCaja'
        ));
    }

    public function create()
    {
        
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'monto' => 'required|numeric|min:0.01',
            'descripcion' => 'required|string|max:255',
            'fecha' => 'required|date'
        ]);

        Salida::create([
            'monto' => $request->monto,
            'descripcion' => $request->descripcion,
            'fecha' => $request->fecha,
        ]);

        return redirect()->back()->with('success', 'Salida de efectivo registrada.');
      }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function cerrarCaja(Request $request)
    {
        // Este método guarda el estado final del día para que mañana sea el inicial
        $fecha = $request->fecha;

        $saldoInicial = CierreCaja::where('fecha', Carbon::parse($fecha)->subDay()->format('Y-m-d'))->value('saldo_final') ?? 0;
        $ingresos = Abono::whereDate('fecha_pago', $fecha)->sum('monto_abonado');
        $egresos = Salida::whereDate('fecha', $fecha)->sum('monto');
        $saldoFinal = ($saldoInicial + $ingresos) - $egresos;

        CierreCaja::updateOrCreate(
            ['fecha' => $fecha],
            [
                'saldo_inicial' => $saldoInicial,
                'ingresos' => $ingresos,
                'egresos' => $egresos,
                'saldo_final' => $saldoFinal
            ]
        );

        return redirect()->back()->with('success', 'Caja cerrada correctamente para esta fecha.');
    }

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
    public function destroy($id)
    {
        //
    }
}
