<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Abono;
use App\Models\Salida;
use App\Models\CierreCaja;
use App\Models\Rescision;
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
            'user',
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
        } elseif ($esAdmin && is_numeric($proyectoFiltro)) {
            $targetLotificacionId = (int) $proyectoFiltro;
            $lotObj = \App\Models\Lotificacion::find($targetLotificacionId);
            $etiquetaProyecto = $lotObj ? $lotObj->nombre : 'Proyecto Seleccionado';
            $abonosQuery = Abono::withoutGlobalScope('lotificacion')
                ->whereHas('venta', fn($q) => $q->withoutGlobalScope('lotificacion')->where('lotificacion_id', $targetLotificacionId))
                ->with($abonosRelations);
        } else {
            // Usuario normal o Admin en modo proyecto actual
            $targetLotificacionId = $activeLotificacionId;
            $lotObj = \App\Models\Lotificacion::find($activeLotificacionId);
            $etiquetaProyecto = $lotObj ? $lotObj->nombre : 'Proyecto Actual';
            $abonosQuery = Abono::withoutGlobalScope('lotificacion')
                ->whereHas('venta', fn($q) => $q->withoutGlobalScope('lotificacion')->where('lotificacion_id', $activeLotificacionId))
                ->with($abonosRelations);
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

        $totalRecaudado = (float) $abonos->sum('monto_abonado');
        $cantidadAbonos = $abonos->count();
        $clientesUnicos = $abonos->pluck('venta.cliente.id_cliente')->filter()->unique()->count();
        $ticketPromedio = $cantidadAbonos > 0 ? ($totalRecaudado / $cantidadAbonos) : 0;

        // 1. Desglose por Concepto / Tipo de Cobro
        $desgloseConceptos = $abonos->groupBy(function($item) {
            $tipo = trim($item->tipo_pago ?? '');
            if (empty($tipo)) return 'Sin Especificar';
            if (stripos($tipo, 'Prima') !== false || stripos($tipo, 'Primer') !== false) return 'Primas / Enganches';
            if (stripos($tipo, 'Mensualidad') !== false || stripos($tipo, 'Cuota') !== false) return 'Cuotas Ordinarias';
            if (stripos($tipo, 'Reserva') !== false || stripos($tipo, 'Anticipo') !== false) return 'Reservas / Anticipos';
            return $tipo;
        })->map(function($items, $concepto) use ($totalRecaudado) {
            $monto = (float) $items->sum('monto_abonado');
            $cantidad = $items->count();
            $porcentaje = $totalRecaudado > 0 ? ($monto / $totalRecaudado) * 100 : 0;
            return [
                'concepto' => $concepto,
                'cantidad' => $cantidad,
                'monto' => $monto,
                'porcentaje' => round($porcentaje, 1),
            ];
        })->sortByDesc('monto')->values();

        // 2. Desglose por Canal / Método de Pago
        $desgloseMetodos = $abonos->groupBy(function($item) {
            return trim($item->metodo_pago) ?: 'Efectivo';
        })->map(function($items, $metodo) use ($totalRecaudado) {
            $monto = (float) $items->sum('monto_abonado');
            $cantidad = $items->count();
            $porcentaje = $totalRecaudado > 0 ? ($monto / $totalRecaudado) * 100 : 0;
            return [
                'metodo' => $metodo,
                'cantidad' => $cantidad,
                'monto' => $monto,
                'porcentaje' => round($porcentaje, 1),
            ];
        })->sortByDesc('monto')->values();

        // Totales de bancarización
        $totalBancos = (float) $abonos->filter(function($a) {
            $m = strtolower($a->metodo_pago ?? '');
            return str_contains($m, 'transferencia') || str_contains($m, 'depósito') || str_contains($m, 'deposito') || str_contains($m, 'cheque');
        })->sum('monto_abonado');

        $totalEfectivo = $totalRecaudado - $totalBancos;
        $porcentajeBancarizado = $totalRecaudado > 0 ? round(($totalBancos / $totalRecaudado) * 100, 1) : 0;
        $porcentajeEfectivo = $totalRecaudado > 0 ? round(($totalEfectivo / $totalRecaudado) * 100, 1) : 0;

        // 3. Desglose por Proyecto
        $desgloseProyectos = $abonos->groupBy(function($item) {
            return $item->venta && $item->venta->lotificacion ? $item->venta->lotificacion->nombre : 'Sin Proyecto';
        })->map(function($items, $proyNombre) use ($totalRecaudado) {
            $monto = (float) $items->sum('monto_abonado');
            $cantidad = $items->count();
            $clientes = $items->pluck('venta.cliente.id_cliente')->filter()->unique()->count();
            $porcentaje = $totalRecaudado > 0 ? ($monto / $totalRecaudado) * 100 : 0;
            return [
                'proyecto' => $proyNombre,
                'clientes' => $clientes,
                'cantidad' => $cantidad,
                'monto' => $monto,
                'porcentaje' => round($porcentaje, 1),
            ];
        })->sortByDesc('monto')->values();

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
            $cajeroNombre = $abono->user ? $abono->user->name : 'Sistema';

            return [
                'id_abono' => $abono->id_abono,
                'recibo_codigo' => 'REC-' . str_pad($abono->id_abono, 5, '0', STR_PAD_LEFT),
                'fecha' => Carbon::parse($abono->fecha_pago)->format('d/m/Y'),
                'hora' => $abono->created_at ? $abono->created_at->format('h:i A') : '-',
                'cliente' => $cliente ? $cliente->nombres_apellidos : 'Cliente Desconocido',
                'identificacion' => $cliente ? ($cliente->identificacion ?: 'S/C') : '-',
                'expediente' => $cliente ? ($cliente->expediente_num ?: ($cliente->pv_num ?: '-')) : '-',
                'pv' => $cliente ? ($cliente->pv_num ?: '-') : '-',
                'bloques' => $bloquesTexto ?: 'N/A',
                'lotes' => $lotesTexto ?: 'N/A',
                'proyecto' => $proyectoNombre,
                'monto' => (float) $abono->monto_abonado,
                'tipo' => $abono->tipo_pago ?: 'Cuota / Abono',
                'metodo' => $abono->metodo_pago ?: 'Efectivo',
                'referencia' => $abono->referencia ?: '-',
                'cajero' => $cajeroNombre,
            ];
        })->values();

        // 4. Rescisiones y Devoluciones Contables del Periodo
        $rescisionesQuery = Rescision::withoutGlobalScope('lotificacion')
            ->with([
                'cliente' => fn($q) => $q->withoutGlobalScope('lotificacion'),
                'user',
                'lotificacion'
            ]);

        if (!$esGlobal && $targetLotificacionId) {
            $rescisionesQuery->where('lotificacion_id', $targetLotificacionId);
        }

        $rescisiones = $rescisionesQuery
            ->whereBetween('created_at', [$inicio->format('Y-m-d 00:00:00'), $fin->format('Y-m-d 23:59:59')])
            ->orderBy('created_at', 'desc')
            ->get();

        $totalDevolucionesRescisiones = (float) $rescisiones->where('destino_abonos', 'devolucion_efectivo')->sum('monto_devuelto');
        $totalRescisionesTransferidas = (float) $rescisiones->where('destino_abonos', 'acreditar_otro_lote')->sum('monto_transferido');
        $cantidadRescisiones = $rescisiones->count();
        $recaudacionNeta = $totalRecaudado - $totalDevolucionesRescisiones;

        $filasRescisiones = $rescisiones->map(function ($r) {
            $cliente = $r->cliente;
            $usuario = $r->user;
            $proy = $r->lotificacion;

            $destinoLabel = match($r->destino_abonos) {
                'devolucion_efectivo' => 'Devolución Contable a Cliente',
                'acreditar_otro_lote' => 'Acreditado a Lote Conservado / Otro Contrato',
                'sin_devolucion' => 'Sin Devolución (Retenido por Cláusula)',
                default => $r->destino_abonos
            };

            return [
                'id_rescision' => $r->id_rescision,
                'codigo' => 'RESC-' . str_pad($r->id_rescision, 4, '0', STR_PAD_LEFT),
                'fecha' => Carbon::parse($r->created_at)->format('d/m/Y'),
                'hora' => Carbon::parse($r->created_at)->format('h:i A'),
                'cliente' => $cliente ? $cliente->nombres_apellidos : 'Cliente Desconocido',
                'identificacion' => $cliente ? ($cliente->identificacion ?: 'S/C') : '-',
                'expediente' => $cliente ? ($cliente->expediente_num ?: ($cliente->pv_num ?: '-')) : '-',
                'tipo' => $r->tipo, // Parcial o Total
                'lotes_afectados' => $r->lotes_afectados ?: 'N/A',
                'lotes_conservados' => $r->lotes_conservados ?: '-',
                'destino_abonos_raw' => $r->destino_abonos,
                'destino_label' => $destinoLabel,
                'monto_devuelto' => (float) $r->monto_devuelto,
                'monto_transferido' => (float) $r->monto_transferido,
                'comentario' => $r->comentario ?: 'Sin observaciones',
                'cajero' => $usuario ? $usuario->name : 'Sistema',
                'proyecto' => $proy ? $proy->nombre : 'N/A',
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
            'totalRecaudado' => $totalRecaudado,
            'totalIngresos' => $totalRecaudado, // Compatibilidad
            'cantidadAbonos' => $cantidadAbonos,
            'clientesUnicos' => $clientesUnicos,
            'clientesAbonaron' => $clientesUnicos, // Compatibilidad
            'ticketPromedio' => $ticketPromedio,
            'totalBancos' => $totalBancos,
            'totalEfectivo' => $totalEfectivo,
            'porcentajeBancarizado' => $porcentajeBancarizado,
            'porcentajeEfectivo' => $porcentajeEfectivo,
            'desgloseConceptos' => $desgloseConceptos,
            'desgloseMetodos' => $desgloseMetodos,
            'desgloseProyectos' => $desgloseProyectos,
            'filasAbonos' => $filasAbonos,
            'rescisiones' => $rescisiones,
            'filasRescisiones' => $filasRescisiones,
            'cantidadRescisiones' => $cantidadRescisiones,
            'totalDevolucionesRescisiones' => $totalDevolucionesRescisiones,
            'totalRescisionesTransferidas' => $totalRescisionesTransferidas,
            'recaudacionNeta' => $recaudacionNeta,
            'aniosDisponibles' => $this->aniosDisponibles(),
            'rangoArchivo' => $inicio->format('Ymd') . '-' . $fin->format('Ymd'),
            'generadoEl' => now()->locale('es')->translatedFormat('d/m/Y h:i A'),
            'generadoPor' => auth()->check() ? auth()->user()->name : 'Auditor del Sistema',
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

        $fechaCierre = Carbon::parse($cierre->fecha)->format('Y-m-d');
        $abonos = \App\Models\Abono::with(['venta.cliente', 'venta.lotes.bloque'])
            ->where('user_id', $cierre->user_id)
            ->whereBetween('created_at', [$inicioTurno, $finTurno])
            ->whereDate('fecha_pago', $fechaCierre)
            ->get();

        $salidas = \App\Models\Salida::where('user_id', $cierre->user_id)
            ->whereBetween('created_at', [$inicioTurno, $finTurno])
            ->get();

        $rawEfectivo = [];
        $rawTransferencias = [];
        $totalEfectivo = 0.0;
        $totalTransferencias = 0.0;

        foreach ($abonos as $abono) {
            $cliente = $abono->venta && $abono->venta->cliente ? $abono->venta->cliente->nombres_apellidos : 'Cliente Desconocido';
            $lotes = '';
            $bloques = '';
            $lotesBloquesTexto = '';
            if ($abono->venta) {
                $lotesArr = [];
                $bloquesArr = [];
                $lbArr = [];
                foreach ($abono->venta->lotes as $lote) {
                    $lotesArr[] = $lote->numero_lote;
                    if ($lote->bloque) {
                        $bloquesArr[] = $lote->bloque->nombre;
                    }
                    $lbArr[] = "Lote {$lote->numero_lote}";
                }
                $lotes = implode(', ', array_unique($lotesArr));
                $bloques = implode(', ', array_unique($bloquesArr));
                $lotesBloquesTexto = implode(', ', array_unique($lbArr));
            }

            $item = [
                'id_abono' => $abono->id_abono,
                'cliente' => $cliente,
                'lotes' => $lotes,
                'bloques' => $bloques,
                'lotes_texto' => $lotesBloquesTexto ?: "Lote {$lotes}",
                'monto' => (float) $abono->monto_abonado,
                'hora' => $abono->created_at ? $abono->created_at->format('h:i a') : '-',
                'fecha_pago' => $abono->fecha_pago ? \Carbon\Carbon::parse($abono->fecha_pago)->format('d/m/Y') : '-',
                'fecha_hora_registro' => $abono->created_at ? $abono->created_at->format('d/m/Y h:i a') : \Carbon\Carbon::parse($abono->fecha_pago)->format('d/m/Y'),
                'fecha_transferencia' => $abono->fecha_transferencia ? \Carbon\Carbon::parse($abono->fecha_transferencia)->format('d/m/Y') : \Carbon\Carbon::parse($abono->fecha_pago)->format('d/m/Y'),
                'referencia' => $abono->referencia ?? 'Pago en Efectivo',
                'numero_recibo' => $abono->numero_recibo_formateado ?? ($abono->numero_recibo ? 'REC-' . $abono->numero_recibo : 'N/A'),
                'metodo_pago' => $abono->metodo_pago,
                'cuenta_destino' => $abono->cuenta_destino ?? 'N/A',
                'grupo_recibo' => $abono->grupo_recibo ?? null,
                'created_at_ts' => $abono->created_at ? $abono->created_at->timestamp : 0,
            ];

            if ($abono->metodo_pago === 'Efectivo') {
                $rawEfectivo[] = $item;
                $totalEfectivo += $item['monto'];
            } else {
                $rawTransferencias[] = $item;
                $totalTransferencias += $item['monto'];
            }
        }

        // Agrupar abonos en efectivo que pertenezcan a la misma operación / recibo
        $abonosEfectivo = [];
        $gruposEfectivo = [];

        foreach ($rawEfectivo as $item) {
            if (!empty($item['grupo_recibo'])) {
                $key = 'GRUPO_' . $item['grupo_recibo'];
            } elseif (!empty($item['numero_recibo']) && $item['numero_recibo'] !== 'N/A') {
                $key = 'REC_' . md5(mb_strtolower($item['cliente']) . '_' . $item['numero_recibo']);
            } elseif ($item['created_at_ts'] > 0) {
                $minuteKey = floor($item['created_at_ts'] / 60);
                $key = 'TIME_' . md5(mb_strtolower($item['cliente']) . '_' . $item['fecha_pago'] . '_' . $minuteKey);
            } else {
                $key = 'SINGLE_' . $item['id_abono'];
            }

            if (!isset($gruposEfectivo[$key])) {
                $gruposEfectivo[$key] = $item;
                $gruposEfectivo[$key]['lotes_lista'] = [$item['lotes_texto']];
            } else {
                $gruposEfectivo[$key]['monto'] += $item['monto'];
                if (!in_array($item['lotes_texto'], $gruposEfectivo[$key]['lotes_lista'])) {
                    $gruposEfectivo[$key]['lotes_lista'][] = $item['lotes_texto'];
                }
            }
        }

        foreach ($gruposEfectivo as $g) {
            $g['lotes_texto'] = implode(', ', $g['lotes_lista']);
            $abonosEfectivo[] = $g;
        }

        // Agrupar transferencias de la misma transacción bancaria (mismo cliente y misma referencia o grupo_recibo)
        $abonosTransferencia = [];
        $gruposTransf = [];

        foreach ($rawTransferencias as $item) {
            $refKey = trim((string)$item['referencia']);
            $hasValidRef = !empty($refKey) && $refKey !== 'N/A' && $refKey !== 'null';

            if (!empty($item['grupo_recibo'])) {
                $key = 'GRUPO_' . $item['grupo_recibo'];
            } elseif ($hasValidRef) {
                $key = 'REF_' . md5(mb_strtolower($item['cliente']) . '_' . mb_strtolower($refKey) . '_' . mb_strtolower($item['cuenta_destino']));
            } else {
                $key = 'SINGLE_' . $item['id_abono'];
            }

            if (!isset($gruposTransf[$key])) {
                $gruposTransf[$key] = $item;
                $gruposTransf[$key]['lotes_lista'] = [$item['lotes_texto']];
            } else {
                $gruposTransf[$key]['monto'] += $item['monto'];
                if (!in_array($item['lotes_texto'], $gruposTransf[$key]['lotes_lista'])) {
                    $gruposTransf[$key]['lotes_lista'][] = $item['lotes_texto'];
                }
            }
        }

        foreach ($gruposTransf as $g) {
            $g['lotes_texto'] = implode(', ', $g['lotes_lista']);
            $abonosTransferencia[] = $g;
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
        $logoBase64 = null;
        $lot = null;

        if (!empty($cierre->lotificacion_id)) {
            $lot = \App\Models\Lotificacion::find($cierre->lotificacion_id);
        }
        if (!$lot) {
            try {
                $lot = app(\App\Services\LotificacionService::class)->getActiveLotificacion();
            } catch (\Exception $e) {}
        }
        if (!$lot && session('lotificacion_id')) {
            $lot = \App\Models\Lotificacion::find(session('lotificacion_id'));
        }

        if ($lot) {
            $lotificacionNombre = $lot->nombre;
            if (!empty($lot->logo)) {
                $path = public_path('storage/' . $lot->logo);
                if (!file_exists($path)) {
                    $path = storage_path('app/public/' . $lot->logo);
                }
                if (file_exists($path)) {
                    $type = pathinfo($path, PATHINFO_EXTENSION);
                    $dataImg = file_get_contents($path);
                    $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($dataImg);
                }
            }
        } else {
            $lotificacionNombre = 'Proyecto';
        }

        $data = [
            'fechaFormateada' => \Carbon\Carbon::parse($cierre->fecha)->format('d/m/Y'),
            'horaGeneracion' => now()->format('h:i a'),
            'cajero' => $cierre->user ? $cierre->user->name : 'Cajero',
            'lotificacionNombre' => $lotificacionNombre,
            'logoBase64' => $logoBase64,
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

    /**
     * Panel de Monitoreo de Cajas y Cierres de Usuarios en Tiempo Real (Vista de Administración).
     */
    public function monitorCajas(Request $request)
    {
        if (!auth()->user()->hasRole('Administrador') && !auth()->user()->can('ver-reportes')) {
            abort(403, 'No tiene permisos para acceder al monitoreo global de cajas.');
        }

        $fecha = $request->input('fecha', Carbon::today()->format('Y-m-d'));
        $filtroUsuarioId = $request->input('user_id');

        // Obtener usuarios del sistema
        $usuariosQuery = \App\Models\User::with('roles')->orderBy('name', 'asc');
        if ($filtroUsuarioId) {
            $usuariosQuery->where('id', $filtroUsuarioId);
        }
        $usuarios = $usuariosQuery->get();

        $usuariosData = [];
        $totalRecaudadoGlobal = 0.0;
        $totalEfectivoGlobal = 0.0;
        $totalBancosGlobal = 0.0;
        $totalEgresosGlobal = 0.0;
        $totalCajasAbiertas = 0;
        $totalCierresRealizados = 0;

        foreach ($usuarios as $user) {
            // Aperturas del día para este usuario
            $aperturas = \App\Models\AperturaCaja::whereDate('fecha', $fecha)
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'asc')
                ->get();

            // Cierres del día para este usuario
            $cierres = \App\Models\CierreCaja::whereDate('fecha', $fecha)
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'asc')
                ->get();

            // Abonos registrados para la fecha por este usuario (filtrado por fecha de pago real para no mezclar migración histórica)
            $abonosDia = Abono::with(['venta.cliente', 'venta.lotes.bloque'])
                ->whereDate('fecha_pago', $fecha)
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            // Salidas registradas en la fecha por este usuario
            $salidasDia = Salida::whereDate('fecha', $fecha)
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            $ultimaApertura = $aperturas->last();
            $ultimoCierre = $cierres->last();

            // Determinar estado actual
            $estado = 'SIN_APERTURA';
            $turnoActivo = false;
            $inicioTurno = null;
            $montoInicialTurno = 0.0;

            if ($ultimaApertura && (!$ultimoCierre || $ultimoCierre->created_at < $ultimaApertura->created_at)) {
                $estado = 'EN_VIVO'; // Turno abierto actualmente
                $turnoActivo = true;
                $inicioTurno = $ultimaApertura->created_at;
                $montoInicialTurno = (float) $ultimaApertura->monto_inicial;
                $totalCajasAbiertas++;
            } elseif ($ultimoCierre) {
                $estado = 'CERRADO'; // Turno cerrado
            }

            $totalCierresRealizados += $cierres->count();

            // Movimientos del turno en vivo (si está abierto) o acumulados del día
            if ($turnoActivo && $ultimaApertura) {
                $abonosTurno = Abono::with(['venta.cliente', 'venta.lotes.bloque'])
                    ->where('user_id', $user->id)
                    ->where('created_at', '>=', $ultimaApertura->created_at)
                    ->whereDate('fecha_pago', $fecha)
                    ->orderBy('created_at', 'desc')
                    ->get();

                $salidasTurno = Salida::where('user_id', $user->id)
                    ->where('created_at', '>=', $ultimaApertura->created_at)
                    ->orderBy('created_at', 'desc')
                    ->get();
            } else {
                $abonosTurno = $abonosDia;
                $salidasTurno = $salidasDia;
            }

            // Cálculos del turno / en vivo
            $ingresosEfectivoTurno = (float) $abonosTurno->where('metodo_pago', 'Efectivo')->sum('monto_abonado');
            $ingresosBancosTurno = (float) $abonosTurno->where('metodo_pago', '!=', 'Efectivo')->sum('monto_abonado');
            $totalIngresosTurno = (float) $abonosTurno->sum('monto_abonado');
            $totalSalidasTurno = (float) $salidasTurno->sum('monto');
            $salidasEfectivoTurno = (float) $salidasTurno->filter(fn($s) => empty($s->metodo_pago) || $s->metodo_pago === 'Efectivo')->sum('monto');
            
            // Efectivo estimado en gaveta en este momento
            $efectivoEnGaveta = $montoInicialTurno + $ingresosEfectivoTurno - $salidasEfectivoTurno;

            // Totales de todo el día para este usuario
            $diaEfectivo = (float) $abonosDia->where('metodo_pago', 'Efectivo')->sum('monto_abonado');
            $diaBancos = (float) $abonosDia->where('metodo_pago', '!=', 'Efectivo')->sum('monto_abonado');
            $diaTotalRecaudado = (float) $abonosDia->sum('monto_abonado');
            $diaTotalEgresos = (float) $salidasDia->sum('monto');

            // Acumular a KPIs globales
            $totalRecaudadoGlobal += $diaTotalRecaudado;
            $totalEfectivoGlobal += $diaEfectivo;
            $totalBancosGlobal += $diaBancos;
            $totalEgresosGlobal += $diaTotalEgresos;

            $tieneActividad = ($aperturas->count() > 0 || $cierres->count() > 0 || $abonosDia->count() > 0 || $salidasDia->count() > 0);

            $usuariosData[] = [
                'user' => $user,
                'estado' => $estado,
                'turnoActivo' => $turnoActivo,
                'tieneActividad' => $tieneActividad,
                'aperturas' => $aperturas,
                'cierres' => $cierres,
                'ultimaApertura' => $ultimaApertura,
                'ultimoCierre' => $ultimoCierre,
                'montoInicialTurno' => $montoInicialTurno,
                'ingresosEfectivoTurno' => $ingresosEfectivoTurno,
                'ingresosBancosTurno' => $ingresosBancosTurno,
                'totalIngresosTurno' => $totalIngresosTurno,
                'totalSalidasTurno' => $totalSalidasTurno,
                'salidasEfectivoTurno' => $salidasEfectivoTurno,
                'efectivoEnGaveta' => $efectivoEnGaveta,
                'abonosTurno' => $abonosTurno,
                'salidasTurno' => $salidasTurno,
                'abonosDia' => $abonosDia,
                'salidasDia' => $salidasDia,
                'diaEfectivo' => $diaEfectivo,
                'diaBancos' => $diaBancos,
                'diaTotalRecaudado' => $diaTotalRecaudado,
                'diaTotalEgresos' => $diaTotalEgresos,
                'cantAbonosDia' => $abonosDia->count(),
            ];
        }

        // Ordenar usuarios: primero los que tienen turno abierto en vivo, luego los que tienen actividad, luego el resto
        usort($usuariosData, function($a, $b) {
            if ($a['turnoActivo'] && !$b['turnoActivo']) return -1;
            if (!$a['turnoActivo'] && $b['turnoActivo']) return 1;
            if ($a['tieneActividad'] && !$b['tieneActividad']) return -1;
            if (!$a['tieneActividad'] && $b['tieneActividad']) return 1;
            return strcmp($a['user']->name, $b['user']->name);
        });

        $kpis = [
            'totalRecaudadoGlobal' => $totalRecaudadoGlobal,
            'totalEfectivoGlobal' => $totalEfectivoGlobal,
            'totalBancosGlobal' => $totalBancosGlobal,
            'totalEgresosGlobal' => $totalEgresosGlobal,
            'flujoNetoGlobal' => $totalRecaudadoGlobal - $totalEgresosGlobal,
            'totalCajasAbiertas' => $totalCajasAbiertas,
            'totalCierresRealizados' => $totalCierresRealizados,
            'totalUsuariosActivos' => count(array_filter($usuariosData, fn($u) => $u['tieneActividad'])),
        ];

        $todosLosUsuarios = \App\Models\User::orderBy('name', 'asc')->get();

        return view('reportes.monitor_cajas', compact('fecha', 'usuariosData', 'kpis', 'todosLosUsuarios', 'filtroUsuarioId'));
    }
}

