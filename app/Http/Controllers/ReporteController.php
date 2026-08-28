<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Abono;
use App\Models\Salida;
use App\Models\CierreCaja;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ReporteController extends Controller
{
    /**
     * Muestra la vista interactiva del Reporte Financiero (filtros + tablas).
     */
    public function financiero(Request $request)
    {
        $data = $this->datosFinancieros($request);

        return view('reportes.financiero', $data);
    }

    /**
     * Genera el Reporte Financiero en PDF respetando los mismos filtros.
     */
    public function financieroPdf(Request $request)
    {
        $data = $this->datosFinancieros($request);

        $pdf = Pdf::loadView('reportes.financiero_pdf', $data)
            ->setPaper('letter', 'portrait');

        return $pdf->download('reporte-financiero-' . $data['rangoArchivo'] . '.pdf');
    }

    /**
     * Genera el Reporte Financiero en Excel (.xls) respetando los mismos filtros.
     */
    public function financieroExcel(Request $request)
    {
        $data = $this->datosFinancieros($request);

        $html = view('reportes.financiero_excel', $data)->render();

        $nombreArchivo = 'reporte-financiero-' . $data['rangoArchivo'] . '.xls';

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $nombreArchivo . '"',
        ]);
    }

    /**
     * Calcula los totales, listados y opciones de filtro del Reporte Financiero
     * de acuerdo al periodo solicitado (día actual, mes, año completo o año hasta hoy).
     */
    private function datosFinancieros(Request $request): array
    {
        $periodo = $request->get('periodo', 'mes');
        $anio = (int) $request->get('anio', now()->year);
        $mes = (int) $request->get('mes', now()->month);
        $fechaSeleccionada = $request->get('fecha', now()->format('Y-m-d'));

        if (!in_array($periodo, ['hoy', 'dia', 'mes', 'anio', 'ytd'], true)) {
            $periodo = 'mes';
        }
        if ($mes < 1 || $mes > 12) {
            $mes = now()->month;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaSeleccionada) || !strtotime($fechaSeleccionada)) {
            $fechaSeleccionada = now()->format('Y-m-d');
        }

        switch ($periodo) {
            case 'hoy':
                $inicio = Carbon::today()->startOfDay();
                $fin = Carbon::today()->endOfDay();
                $anio = $inicio->year;
                $etiquetaPeriodo = 'Día de hoy, ' . $inicio->locale('es')->translatedFormat('d \d\e F \d\e Y');
                break;

            case 'dia':
                $inicio = Carbon::parse($fechaSeleccionada)->startOfDay();
                $fin = Carbon::parse($fechaSeleccionada)->endOfDay();
                $anio = $inicio->year;
                $etiquetaPeriodo = $inicio->locale('es')->translatedFormat('d \d\e F \d\e Y');
                break;

            case 'anio':
                $inicio = Carbon::create($anio, 1, 1)->startOfDay();
                $fin = Carbon::create($anio, 12, 31)->endOfDay();
                $etiquetaPeriodo = 'Año completo ' . $anio;
                break;

            case 'ytd':
                $inicio = Carbon::create($anio, 1, 1)->startOfDay();
                $fin = $anio === now()->year
                    ? Carbon::today()->endOfDay()
                    : Carbon::create($anio, 12, 31)->endOfDay();
                $etiquetaPeriodo = 'Del 1 de enero al ' . $fin->locale('es')->translatedFormat('d \d\e F') . ' de ' . $anio;
                break;

            case 'mes':
            default:
                $periodo = 'mes';
                $inicio = Carbon::create($anio, $mes, 1)->startOfDay();
                $fin = $inicio->copy()->endOfMonth()->endOfDay();
                $etiquetaPeriodo = ucfirst($inicio->locale('es')->translatedFormat('F \d\e Y'));
                break;
        }

        $abonos = Abono::with(['venta.cliente', 'venta.lotes.bloque'])
            ->whereBetween('fecha_pago', [$inicio->format('Y-m-d'), $fin->format('Y-m-d')])
            ->orderBy('fecha_pago')
            ->orderBy('created_at')
            ->get();

        $salidas = Salida::whereBetween('fecha', [$inicio->format('Y-m-d'), $fin->format('Y-m-d')])
            ->orderBy('fecha')
            ->orderBy('created_at')
            ->get();

        $totalIngresos = (float) $abonos->sum('monto_abonado');
        $totalGastos = (float) $salidas->sum('monto');
        $balanceNeto = $totalIngresos - $totalGastos;

        // Efectivo que venía arrastrado (sin cerrar) antes de iniciar el periodo filtrado
        $saldoAnterior = $this->calcularSaldoAnterior($inicio->format('Y-m-d'));
        $totalConSaldoAnterior = $saldoAnterior + $totalIngresos;

        $clientesAbonaron = $abonos->pluck('venta.cliente.id_cliente')->filter()->unique()->count();

        $filasAbonos = $abonos->map(function ($abono) {
            $venta = $abono->venta;
            $cliente = $venta->cliente ?? null;
            $lotes = $venta->lotes ?? collect();

            $lotesTexto = $lotes->isNotEmpty()
                ? $lotes->map(fn ($lote) => ($lote->bloque->nombre ?? 'N/A') . '-' . $lote->numero_lote)->implode(', ')
                : 'N/A';

            $bloquesTexto = $lotes->isNotEmpty()
                ? $lotes->pluck('bloque.nombre')->filter()->unique()->implode(', ')
                : 'N/A';

            return [
                'fecha' => Carbon::parse($abono->fecha_pago)->format('d/m/Y'),
                'hora' => $abono->created_at ? $abono->created_at->format('h:i A') : '-',
                'cliente' => $cliente->nombres_apellidos ?? 'Cliente Desconocido',
                'pv' => $cliente->pv_num ?? '-',
                'bloques' => $bloquesTexto,
                'lotes' => $lotesTexto,
                'monto' => (float) $abono->monto_abonado,
                'tipo' => $abono->tipo_pago,
                'referencia' => $abono->referencia ?: '-',
            ];
        })->values();

        $filasSalidas = $salidas->map(function ($salida) {
            return [
                'fecha' => $salida->fecha ? Carbon::parse($salida->fecha)->format('d/m/Y') : '-',
                'hora' => $salida->created_at ? $salida->created_at->format('h:i A') : '-',
                'descripcion' => $salida->descripcion,
                'monto' => (float) $salida->monto,
            ];
        })->values();

        return [
            'periodo' => $periodo,
            'anio' => $anio,
            'mes' => $mes,
            'fechaSeleccionada' => $fechaSeleccionada,
            'etiquetaPeriodo' => $etiquetaPeriodo,
            'inicio' => $inicio,
            'fin' => $fin,
            'totalIngresos' => $totalIngresos,
            'totalGastos' => $totalGastos,
            'balanceNeto' => $balanceNeto,
            'saldoAnterior' => $saldoAnterior,
            'totalConSaldoAnterior' => $totalConSaldoAnterior,
            'clientesAbonaron' => $clientesAbonaron,
            'cantidadAbonos' => $abonos->count(),
            'cantidadSalidas' => $salidas->count(),
            'filasAbonos' => $filasAbonos,
            'filasSalidas' => $filasSalidas,
            'aniosDisponibles' => $this->aniosDisponibles(),
            'rangoArchivo' => $inicio->format('Ymd') . '-' . $fin->format('Ymd'),
            'generadoEl' => now()->locale('es')->translatedFormat('d/m/Y h:i A'),
        ];
    }

    /**
     * Lista de años disponibles para el filtro (desde el primer abono registrado hasta el año actual).
     */
    private function aniosDisponibles(): array
    {
        $anioActual = (int) now()->year;
        $primeraFecha = Abono::min('fecha_pago');
        $primerAnio = $primeraFecha ? Carbon::parse($primeraFecha)->year : $anioActual;
        $desde = min($primerAnio, $anioActual);

        return range($anioActual, $desde);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index(Request $request)
    {
        $fecha = $request->get('fecha', Carbon::today()->format('Y-m-d'));

        $apertura = \App\Models\AperturaCaja::where('fecha', $fecha)->where('user_id', auth()->id())->first();
        $cajaAbierta = $apertura ? true : false;

        if ($cajaAbierta) {
            $saldoInicial = $apertura->monto_inicial;
        } else {
            // Se calcula como sugerencia para la apertura
            $saldoInicial = $this->calcularSaldoAnterior($fecha);
        }

        $ingresosHoy = Abono::whereDate('fecha_pago', $fecha)->where('user_id', auth()->id())->sum('monto_abonado');
        $listaSalidas = Salida::whereDate('fecha', $fecha)->where('user_id', auth()->id())->get();
        $egresosHoy = $listaSalidas->sum('monto');

        $efectivoTotalSuma = $saldoInicial + $ingresosHoy;
        $saldoFinalCaja = $efectivoTotalSuma - $egresosHoy;

        return view('reportes.diario', compact(
            'fecha', 'saldoInicial', 'ingresosHoy', 
            'egresosHoy', 'listaSalidas', 'efectivoTotalSuma', 'saldoFinalCaja', 'cajaAbierta'
        ));
    }

    public function abrirCaja(Request $request)
    {
        $request->validate([
            'fecha' => 'required|date',
            'monto_inicial' => 'required|numeric|min:0'
        ]);

        \App\Models\AperturaCaja::updateOrCreate(
            ['fecha' => $request->fecha],
            [
                'monto_inicial' => $request->monto_inicial,
                'user_id' => auth()->id()
            ]
        );

        return redirect()->back()->with('success', 'Caja abierta correctamente. Ahora puede operar.');
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
            'metodo_pago' => 'required|string',
            'fecha' => 'required|date'
        ]);

        $salida = Salida::create([
            'monto' => $request->monto,
            'descripcion' => $request->descripcion,
            'metodo_pago' => $request->metodo_pago,
            'fecha' => $request->fecha,
            'user_id' => auth()->id()
        ]);

        \App\Models\Auditoria::log('Registró Egreso', 'Salida', $salida->id, "Monto: $" . number_format($request->monto, 2));

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
        $request->validate([
            'fecha' => 'required|date',
            'efectivo_real' => 'required|numeric|min:0',
        ]);

        $fecha = $request->fecha;
        $saldoInicial = $this->calcularSaldoAnterior($fecha);
        $ingresos = Abono::whereDate('fecha_pago', $fecha)->where('user_id', auth()->id())->sum('monto_abonado');
        $egresos = Salida::whereDate('fecha', $fecha)->where('user_id', auth()->id())->sum('monto');
        $saldoFinal = ($saldoInicial + $ingresos) - $egresos;
        
        $efectivoReal = $request->efectivo_real;
        $diferencia = $efectivoReal - $saldoFinal;

        if ($diferencia != 0 && empty($request->comentario)) {
            return back()->withInput()->with('error', 'Debe proporcionar una justificación para la diferencia detectada en el arqueo.');
        }

        CierreCaja::updateOrCreate(
            ['fecha' => $fecha, 'user_id' => auth()->id()],
            [
                'saldo_inicial' => $saldoInicial,
                'ingresos' => $ingresos,
                'egresos' => $egresos,
                'saldo_final' => $saldoFinal,
                'efectivo_real' => $efectivoReal,
                'diferencia' => $diferencia,
                'comentario' => $request->comentario
            ]
        );

        return redirect()->back()->with('success', 'Caja cerrada correctamente para esta fecha.');
    }

    /**
     * Saldo que se arrastra hacia $fecha: el efectivo rara vez permanece en
     * caja de un cierre a otro (se retira/deposita al cerrar), así que un
     * "Realizar Cierre de Caja" ya realizado deja el saldo en $0 para el
     * día siguiente. Solo se acumulan los abonos y salidas de los días
     * posteriores al último cierre que todavía NO se han cerrado (aunque
     * hayan pasado varios días sin usar "Realizar Cierre de Caja").
     */
    private function calcularSaldoAnterior(string $fecha): float
    {
        $ultimoCierre = CierreCaja::where('fecha', '<', $fecha)
            ->where('user_id', auth()->id())
            ->orderByDesc('fecha')
            ->first();

        $saldo = 0.0;
        $desde = $ultimoCierre ? Carbon::parse($ultimoCierre->fecha)->addDay()->format('Y-m-d') : null;
        $hasta = Carbon::parse($fecha)->subDay()->format('Y-m-d');

        if (!$desde || $desde <= $hasta) {
            $ingresosPendientes = Abono::when($desde, fn ($q) => $q->where('fecha_pago', '>=', $desde))
                ->where('fecha_pago', '<=', $hasta)
                ->where('user_id', auth()->id())
                ->sum('monto_abonado');

            $egresosPendientes = Salida::when($desde, fn ($q) => $q->where('fecha', '>=', $desde))
                ->where('fecha', '<=', $hasta)
                ->where('user_id', auth()->id())
                ->sum('monto');

            $saldo += (float) $ingresosPendientes - (float) $egresosPendientes;
        }

        return $saldo;
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
        $salida = Salida::findOrFail($id);
        $monto = $salida->monto;
        $salida->delete();
        
        \App\Models\Auditoria::log('Eliminó Egreso', 'Salida', $id, "Monto: $" . number_format($monto, 2));

        return redirect()->back()->with('success', 'Salida eliminada correctamente.');
    }
}
