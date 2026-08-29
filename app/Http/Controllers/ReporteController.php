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
        $proyectoFiltro = $request->get('proyecto_id', 'actual');

        $esAdmin = auth()->check() && auth()->user()->hasRole('Administrador');
        $activeLotificacionId = session('lotificacion_id');

        $esGlobal = false;
        $targetLotificacionId = null;

        $abonosRelations = [
            'venta' => fn($q) => $q->withoutGlobalScope('lotificacion'),
            'venta.cliente' => fn($q) => $q->withoutGlobalScope('lotificacion'),
            'venta.lotes' => fn($q) => $q->withoutGlobalScope('lotificacion'),
            'venta.lotes.bloque' => fn($q) => $q->withoutGlobalScope('lotificacion'),
            'venta.lotificacion' => fn($q) => $q->withoutGlobalScope('lotificacion')
        ];

        if ($esAdmin && ($proyectoFiltro === 'global' || $proyectoFiltro === 'todos')) {
            $esGlobal = true;
            $etiquetaProyecto = 'CONSOLIDADO GLOBAL (TODAS LAS LOTIFICACIONES)';
            $abonosQuery = Abono::withoutGlobalScope('lotificacion')->with($abonosRelations);
            $salidasQuery = Salida::withoutGlobalScope('lotificacion');
        } elseif ($esAdmin && is_numeric($proyectoFiltro)) {
            $targetLotificacionId = (int) $proyectoFiltro;
            $lotObj = \App\Models\Lotificacion::find($targetLotificacionId);
            $etiquetaProyecto = $lotObj ? $lotObj->nombre : 'Proyecto Seleccionado';
            $abonosQuery = Abono::withoutGlobalScope('lotificacion')
                ->whereHas('venta', fn($q) => $q->withoutGlobalScope('lotificacion')->where('lotificacion_id', $targetLotificacionId))
                ->with($abonosRelations);
            $salidasQuery = Salida::withoutGlobalScope('lotificacion')->where('lotificacion_id', $targetLotificacionId);
        } else {
            // Usuario normal o Admin en modo proyecto actual
            $targetLotificacionId = $activeLotificacionId;
            $lotObj = \App\Models\Lotificacion::find($activeLotificacionId);
            $etiquetaProyecto = $lotObj ? $lotObj->nombre : 'Proyecto Actual';
            $abonosQuery = Abono::withoutGlobalScope('lotificacion')
                ->whereHas('venta', fn($q) => $q->withoutGlobalScope('lotificacion')->where('lotificacion_id', $activeLotificacionId))
                ->with($abonosRelations);
            $salidasQuery = Salida::withoutGlobalScope('lotificacion')->where('lotificacion_id', $activeLotificacionId);
        }

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

        $abonos = $abonosQuery
            ->whereBetween('fecha_pago', [$inicio->format('Y-m-d'), $fin->format('Y-m-d')])
            ->orderBy('fecha_pago')
            ->orderBy('created_at')
            ->get();

        $salidas = $salidasQuery
            ->whereBetween('fecha', [$inicio->format('Y-m-d'), $fin->format('Y-m-d')])
            ->orderBy('fecha')
            ->orderBy('created_at')
            ->get();

        $totalIngresos = (float) $abonos->sum('monto_abonado');
        $totalGastos = (float) $salidas->sum('monto');
        $balanceNeto = $totalIngresos - $totalGastos;

        // Efectivo que venía arrastrado (sin cerrar) antes de iniciar el periodo filtrado
        $saldoAnterior = $this->calcularSaldoAnterior($inicio->format('Y-m-d'), $esGlobal, $targetLotificacionId);
        $totalConSaldoAnterior = $saldoAnterior + $totalIngresos;

        $clientesAbonaron = $abonos->pluck('venta.cliente.id_cliente')->filter()->unique()->count();

        // Mapa de lotificaciones para nombres rápidos en salidas
        $mapaLotificaciones = \App\Models\Lotificacion::pluck('nombre', 'id')->toArray();

        $filasAbonos = $abonos->map(function ($abono) {
            $venta = $abono->venta;
            $cliente = $venta ? $venta->cliente : null;
            $lotes = $venta ? $venta->lotes : collect();

            $lotesTexto = $lotes->isNotEmpty()
                ? $lotes->map(function ($lote) {
                    $bloqueNom = $lote->bloque->nombre ?? '';
                    $numLote = $lote->numero_lote;
                    if ($bloqueNom && !str_starts_with(strtoupper($numLote), strtoupper($bloqueNom))) {
                        return $bloqueNom . '-' . $numLote;
                    }
                    return $numLote;
                })->implode(', ')
                : 'N/A';

            $bloquesTexto = $lotes->isNotEmpty()
                ? $lotes->pluck('bloque.nombre')->filter()->unique()->implode(', ')
                : 'N/A';

            $proyectoNombre = $venta && $venta->lotificacion ? $venta->lotificacion->nombre : 'N/A';

            return [
                'fecha' => Carbon::parse($abono->fecha_pago)->format('d/m/Y'),
                'hora' => $abono->created_at ? $abono->created_at->format('h:i A') : '-',
                'cliente' => $cliente ? $cliente->nombres_apellidos : 'Cliente Desconocido',
                'pv' => $cliente ? $cliente->pv_num : '-',
                'bloques' => $bloquesTexto ?: 'N/A',
                'lotes' => $lotesTexto ?: 'N/A',
                'proyecto' => $proyectoNombre,
                'monto' => (float) $abono->monto_abonado,
                'tipo' => $abono->tipo_pago,
                'referencia' => $abono->referencia ?: '-',
            ];
        })->values();

        $filasSalidas = $salidas->map(function ($salida) use ($mapaLotificaciones) {
            $proyectoNombre = $salida->lotificacion_id && isset($mapaLotificaciones[$salida->lotificacion_id])
                ? $mapaLotificaciones[$salida->lotificacion_id]
                : 'N/A';

            return [
                'fecha' => $salida->fecha ? Carbon::parse($salida->fecha)->format('d/m/Y') : '-',
                'hora' => $salida->created_at ? $salida->created_at->format('h:i A') : '-',
                'descripcion' => $salida->descripcion,
                'proyecto' => $proyectoNombre,
                'monto' => (float) $salida->monto,
            ];
        })->values();

        return [
            'periodo' => $periodo,
            'anio' => $anio,
            'mes' => $mes,
            'fechaSeleccionada' => $fechaSeleccionada,
            'etiquetaPeriodo' => $etiquetaPeriodo,
            'esGlobal' => $esGlobal,
            'etiquetaProyecto' => $etiquetaProyecto,
            'proyectoFiltro' => $proyectoFiltro,
            'esAdmin' => $esAdmin,
            'proyectosDisponibles' => \App\Models\Lotificacion::orderBy('nombre')->get(),
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

        // Obtener el último registro de apertura del día para este usuario
        $apertura = \App\Models\AperturaCaja::where('fecha', $fecha)->where('user_id', auth()->id())->latest()->first();
        $cajaAbierta = $apertura ? true : false;

        // Determinar si esa última apertura ya fue cerrada
        $cierre = \App\Models\CierreCaja::where('fecha', $fecha)->where('user_id', auth()->id())->latest()->first();
        $cajaCerrada = false;

        if ($cierre && $apertura && $cierre->created_at >= $apertura->created_at) {
            $cajaCerrada = true;
        }

        if ($cajaAbierta) {
            $saldoInicial = $apertura->monto_inicial;
        } else {
            // Se calcula como sugerencia para la primera apertura del día
            $saldoInicial = $this->calcularSaldoAnterior($fecha);
        }

        // Obtener transacciones basándonos en la hora de apertura (turno actual)
        if ($cajaAbierta && !$cajaCerrada) {
            $listaIngresos = Abono::with('venta.cliente')
                ->where('user_id', auth()->id())
                ->where('created_at', '>=', $apertura->created_at)
                ->get();
                
            $listaSalidas = Salida::where('user_id', auth()->id())
                ->where('created_at', '>=', $apertura->created_at)
                ->get();
        } else {
            $listaIngresos = collect();
            $listaSalidas = collect();
        }

        $ingresosHoy = $listaIngresos->sum('monto_abonado');
        $egresosHoy = $listaSalidas->sum('monto');
        $saldoFinalCaja = $saldoInicial + $ingresosHoy - $egresosHoy;
        
        $cierresHoy = \App\Models\CierreCaja::where('fecha', $fecha)->where('user_id', auth()->id())->latest()->get();

        return view('reportes.diario', compact(
            'saldoInicial',
            'ingresosHoy',
            'egresosHoy',
            'saldoFinalCaja',
            'fecha',
            'cajaAbierta',
            'cajaCerrada',
            'listaSalidas',
            'listaIngresos',
            'cierresHoy'
        ));
    }

    public function abrirCaja(Request $request)
    {
        $request->validate([
            'fecha' => 'required|date',
            'monto_inicial' => 'required|numeric|min:0'
        ]);

        \App\Models\AperturaCaja::create([
            'fecha' => $request->fecha,
            'monto_inicial' => $request->monto_inicial,
            'user_id' => auth()->id()
        ]);

        return redirect()->back()->with('success', 'Caja abierta correctamente. Ahora puede operar en su nuevo turno.');
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
        
        // Prevent double close on the same shift
        $apertura = \App\Models\AperturaCaja::where('fecha', $fecha)->where('user_id', auth()->id())->latest()->first();
        $cierrePrevio = \App\Models\CierreCaja::where('fecha', $fecha)->where('user_id', auth()->id())->latest()->first();
        
        if ($cierrePrevio && $apertura && $cierrePrevio->created_at >= $apertura->created_at) {
            return back()->with('error', 'La caja ya fue cerrada para este turno. Debe abrir una nueva caja para registrar más transacciones.');
        }
        
        $saldoInicial = $apertura ? $apertura->monto_inicial : 0;
        
        // Calcular ingresos y egresos de este turno
        if ($apertura) {
            $ingresos = Abono::where('user_id', auth()->id())
                ->where('created_at', '>=', $apertura->created_at)
                ->sum('monto_abonado');
                
            $egresos = Salida::where('user_id', auth()->id())
                ->where('created_at', '>=', $apertura->created_at)
                ->sum('monto');
        } else {
            $ingresos = 0;
            $egresos = 0;
        }

        $saldoFinal = ($saldoInicial + $ingresos) - $egresos;
        
        $efectivoReal = round((float)$request->efectivo_real, 2);
        $saldoFinal = round((float)$saldoFinal, 2);
        $diferencia = round($efectivoReal - $saldoFinal, 2);

        if ($diferencia != 0.00 && empty($request->comentario)) {
            return back()->withInput()->with('error', 'Debe proporcionar una justificación para la diferencia detectada en el arqueo.');
        }

        CierreCaja::create([
            'fecha' => $fecha,
            'user_id' => auth()->id(),
            'saldo_inicial' => $saldoInicial,
            'ingresos' => $ingresos,
            'egresos' => $egresos,
            'saldo_final' => $saldoFinal,
            'efectivo_real' => $efectivoReal,
            'diferencia' => $diferencia,
            'comentario' => $request->comentario
        ]);

        return redirect()->back()->with('success', 'Arqueo y cierre de caja realizados correctamente.');
    }

    /**
     * Saldo que se arrastra hacia $fecha: el efectivo rara vez permanece en
     * caja de un cierre a otro (se retira/deposita al cerrar), así que un
     * "Realizar Cierre de Caja" ya realizado deja el saldo en $0 para el
     * día siguiente. Solo se acumulan los abonos y salidas de los días
     * posteriores al último cierre que todavía NO se han cerrado (aunque
     * hayan pasado varios días sin usar "Realizar Cierre de Caja").
     */
    private function calcularSaldoAnterior(string $fecha, bool $esGlobal = false, ?int $targetLotificacionId = null): float
    {
        if (!$esGlobal && $targetLotificacionId === null) {
            $targetLotificacionId = session('lotificacion_id');
        }

        $cierreQuery = CierreCaja::withoutGlobalScope('lotificacion')
            ->where('fecha', '<', $fecha)
            ->where('user_id', auth()->id());

        if (!$esGlobal && $targetLotificacionId) {
            $cierreQuery->where('lotificacion_id', $targetLotificacionId);
        }

        $ultimoCierre = $cierreQuery->orderByDesc('fecha')->first();

        $saldo = 0.0;
        $desde = $ultimoCierre ? Carbon::parse($ultimoCierre->fecha)->addDay()->format('Y-m-d') : null;
        $hasta = Carbon::parse($fecha)->subDay()->format('Y-m-d');

        if (!$desde || $desde <= $hasta) {
            $ingresosQuery = Abono::withoutGlobalScope('lotificacion')
                ->when($desde, fn ($q) => $q->where('fecha_pago', '>=', $desde))
                ->where('fecha_pago', '<=', $hasta)
                ->where('user_id', auth()->id());

            $egresosQuery = Salida::withoutGlobalScope('lotificacion')
                ->when($desde, fn ($q) => $q->where('fecha', '>=', $desde))
                ->where('fecha', '<=', $hasta)
                ->where('user_id', auth()->id());

            if (!$esGlobal && $targetLotificacionId) {
                $ingresosQuery->whereHas('venta', fn($q) => $q->where('lotificacion_id', $targetLotificacionId));
                $egresosQuery->where('lotificacion_id', $targetLotificacionId);
            }

            $ingresosPendientes = $ingresosQuery->sum('monto_abonado');
            $egresosPendientes = $egresosQuery->sum('monto');

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

    public function imprimirCierreTurnoPdf($id)
    {
        $cierre = \App\Models\CierreCaja::with('user')->findOrFail($id);
        
        if ($cierre->user_id !== auth()->id() && !auth()->user()->hasRole('Administrador')) {
            abort(403, 'No autorizado para ver este cierre.');
        }

        $apertura = \App\Models\AperturaCaja::where('user_id', $cierre->user_id)
            ->where('created_at', '<=', $cierre->created_at)
            ->latest()
            ->first();

        $inicioTurno = $apertura ? $apertura->created_at : $cierre->created_at->startOfDay();
        $finTurno = $cierre->created_at;

        $abonos = \App\Models\Abono::with(['venta.cliente', 'venta.lotes.bloque'])
            ->where('user_id', $cierre->user_id)
            ->whereBetween('created_at', [$inicioTurno, $finTurno])
            ->get();

        $salidas = \App\Models\Salida::where('user_id', $cierre->user_id)
            ->whereBetween('created_at', [$inicioTurno, $finTurno])
            ->get();

        $abonosEfectivo = [];
        $abonosTransferencia = [];
        $totalEfectivo = 0;
        $totalTransferencias = 0;

        foreach ($abonos as $abono) {
            $cliente = $abono->venta && $abono->venta->cliente ? $abono->venta->cliente->nombres_apellidos : 'Cliente Desconocido';
            $lotes = '';
            $bloques = '';
            if ($abono->venta) {
                $lotesArr = [];
                $bloquesArr = [];
                foreach ($abono->venta->lotes as $lote) {
                    $lotesArr[] = $lote->numero_lote;
                    if ($lote->bloque) {
                        $bloquesArr[] = $lote->bloque->nombre;
                    }
                }
                $lotes = implode(', ', array_unique($lotesArr));
                $bloques = implode(', ', array_unique($bloquesArr));
            }

            $item = [
                'cliente' => $cliente,
                'lotes' => $lotes,
                'bloques' => $bloques,
                'monto' => $abono->monto_abonado,
                'hora' => $abono->created_at->format('h:i a'),
                'referencia' => $abono->referencia ?? 'N/A',
                'metodo_pago' => $abono->metodo_pago,
                'cuenta_destino' => $abono->cuenta_destino ?? 'N/A',
            ];

            if ($abono->metodo_pago === 'Efectivo') {
                $abonosEfectivo[] = $item;
                $totalEfectivo += $abono->monto_abonado;
            } else {
                $abonosTransferencia[] = $item;
                $totalTransferencias += $abono->monto_abonado;
            }
        }

        // Salidas en efectivo vs otras salidas
        $totalSalidasEfectivo = 0;
        foreach ($salidas as $salida) {
            if (empty($salida->metodo_pago) || $salida->metodo_pago === 'Efectivo') {
                $totalSalidasEfectivo += $salida->monto;
            }
        }

        $totalSalidas = $salidas->sum('monto');
        
        // Existencia real de efectivo en la gaveta
        $saldoInicial = $cierre->saldo_inicial;
        $existenciaEnCaja = $saldoInicial + $totalEfectivo - $totalSalidasEfectivo;
        
        $lotificacionNombre = null;
        if (!empty($cierre->lotificacion_id)) {
            $lot = \App\Models\Lotificacion::find($cierre->lotificacion_id);
            $lotificacionNombre = $lot ? $lot->nombre : null;
        }
        if (!$lotificacionNombre) {
            try {
                $lotificacion = app(\App\Services\LotificacionService::class)->getActiveLotificacion();
                $lotificacionNombre = $lotificacion ? $lotificacion->nombre : 'Proyecto';
            } catch (\Exception $e) {
                $lotificacionNombre = 'Proyecto';
            }
        }

        $data = [
            'fechaFormateada' => \Carbon\Carbon::parse($cierre->fecha)->format('d/m/Y'),
            'horaGeneracion' => now()->format('h:i a'),
            'cajero' => $cierre->user ? $cierre->user->name : 'Cajero',
            'lotificacionNombre' => $lotificacionNombre,
            'saldoInicial' => $saldoInicial,
            'totalEfectivo' => $totalEfectivo,
            'totalSalidas' => $totalSalidasEfectivo,
            'saldoFinalCaja' => $existenciaEnCaja,
            'totalTransferencias' => $totalTransferencias,
            'abonosEfectivo' => $abonosEfectivo,
            'abonosTransferencia' => $abonosTransferencia,
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reportes.cierre_turno_pdf', $data)
            ->setPaper('letter', 'portrait');

        return $pdf->download('Cierre_Turno_' . \Carbon\Carbon::parse($cierre->fecha)->format('Ymd') . '_' . $cierre->id . '.pdf');
    }
}
