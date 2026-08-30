<?php

namespace App\Http\Controllers;

use App\Models\Abono;
use App\Models\Bloque;
use App\Models\Cliente;
use App\Models\Cuota;
use App\Models\Lotificacion;
use App\Models\Lote;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportesAvanzadosController extends Controller
{
    /**
     * Helper para obtener el logo en base64 de la lotificación activa o especificada.
     */
    private function getLogoBase64(?int $lotificacionId): ?string
    {
        $lot = $lotificacionId ? Lotificacion::find($lotificacionId) : null;
        if (!$lot && session('lotificacion_id')) {
            $lot = Lotificacion::find(session('lotificacion_id'));
        }

        if ($lot && $lot->logo_path) {
            $path = storage_path('app/public/' . $lot->logo_path);
            if (file_exists($path)) {
                $type = pathinfo($path, PATHINFO_EXTENSION);
                $data = file_get_contents($path);
                return 'data:image/' . $type . ';base64,' . base64_encode($data);
            }
        }

        $defaultPath = public_path('img/logo-default.png');
        if (file_exists($defaultPath)) {
            $type = pathinfo($defaultPath, PATHINFO_EXTENSION);
            $data = file_get_contents($defaultPath);
            return 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        return null;
    }

    /**
     * Resuelve el filtro de proyecto (activo vs específico vs global para admin).
     */
    private function resolverFiltroProyecto(Request $request): array
    {
        $esAdmin = auth()->check() && auth()->user()->hasRole('Administrador');
        $activeLotId = session('lotificacion_id');
        $filtro = $request->get('proyecto_id', 'actual');

        $esGlobal = false;
        $targetLotId = null;
        $nombreProyecto = 'Proyecto Actual';

        if ($esAdmin && ($filtro === 'global' || $filtro === 'todos')) {
            $esGlobal = true;
            $nombreProyecto = 'CONSOLIDADO GLOBAL (TODAS LAS LOTIFICACIONES)';
        } elseif ($esAdmin && is_numeric($filtro)) {
            $targetLotId = (int) $filtro;
            $lot = Lotificacion::find($targetLotId);
            $nombreProyecto = $lot ? $lot->nombre : 'Proyecto Seleccionado';
        } else {
            $targetLotId = $activeLotId;
            $lot = Lotificacion::find($activeLotId);
            $nombreProyecto = $lot ? $lot->nombre : 'Proyecto Actual';
        }

        return [
            'esAdmin' => $esAdmin,
            'esGlobal' => $esGlobal,
            'targetLotId' => $targetLotId,
            'nombreProyecto' => $nombreProyecto,
            'filtroSeleccionado' => $filtro,
            'proyectosDisponibles' => Lotificacion::orderBy('nombre')->get(),
        ];
    }

    // =========================================================================
    // 1. REPORTE DE INVENTARIO Y DISPONIBILIDAD DE LOTES
    // =========================================================================

    public function inventarioLotes(Request $request)
    {
        $data = $this->datosInventarioLotes($request);
        return view('reportes.inventario_lotes', $data);
    }

    public function inventarioLotesPdf(Request $request)
    {
        $data = $this->datosInventarioLotes($request);
        $data['logoBase64'] = $this->getLogoBase64($data['targetLotId']);

        $pdf = Pdf::loadView('reportes.inventario_lotes_pdf', $data)
            ->setPaper('letter', 'landscape');

        return $pdf->download('reporte-inventario-lotes-' . now()->format('Ymd') . '.pdf');
    }

    public function inventarioLotesExcel(Request $request)
    {
        $data = $this->datosInventarioLotes($request);
        $html = view('reportes.inventario_lotes_excel', $data)->render();

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="inventario-lotes-' . now()->format('Ymd') . '.xls"',
        ]);
    }

    private function datosInventarioLotes(Request $request): array
    {
        $filtroProj = $this->resolverFiltroProyecto($request);
        $estadoFiltro = $request->get('estado', 'todos');
        $bloqueFiltro = $request->get('bloque_id', 'todos');
        $fechaDesde = $request->get('fecha_desde');
        $fechaHasta = $request->get('fecha_hasta');

        $query = Lote::withoutGlobalScope('lotificacion')->with(['bloque.lotificacion', 'ventas.cliente']);

        if (!$filtroProj['esGlobal'] && $filtroProj['targetLotId']) {
            $query->whereHas('bloque', fn($q) => $q->where('lotificacion_id', $filtroProj['targetLotId']));
        }

        if ($bloqueFiltro !== 'todos' && is_numeric($bloqueFiltro)) {
            $query->where('id_bloque', (int) $bloqueFiltro);
        }

        if ($estadoFiltro !== 'todos' && in_array($estadoFiltro, ['Disponible', 'Reservado', 'Vendido'])) {
            $query->where('estado', $estadoFiltro);
        }

        if (!empty($fechaDesde)) {
            $query->whereDate('created_at', '>=', $fechaDesde);
        }
        if (!empty($fechaHasta)) {
            $query->whereDate('created_at', '<=', $fechaHasta);
        }

        $lotes = $query->orderBy('id_bloque')->orderBy('numero_lote')->get();

        // Totales y Estadísticas
        $totalLotes = $lotes->count();
        $disponibles = $lotes->where('estado', 'Disponible');
        $reservados = $lotes->where('estado', 'Reservado');
        $vendidos = $lotes->where('estado', 'Vendido');

        $totalDisponibles = $disponibles->count();
        $totalReservados = $reservados->count();
        $totalVendidos = $vendidos->count();

        $valorTotal = $lotes->sum('precio_base');
        $valorDisponible = $disponibles->sum('precio_base');
        $valorReservado = $reservados->sum('precio_base');
        $valorVendido = $vendidos->sum('precio_base');

        $areaM2Total = $lotes->sum('area_metros');
        $areaM2Disponible = $disponibles->sum('area_metros');
        $areaM2Vendido = $vendidos->sum('area_metros');

        $factorVrs = 1 / 0.705;
        $areaVrsTotal = $areaM2Total * $factorVrs;
        $areaVrsDisponible = $areaM2Disponible * $factorVrs;
        $areaVrsVendido = $areaM2Vendido * $factorVrs;

        $porcentajeOcupacion = $totalLotes > 0 ? round(($totalVendidos / $totalLotes) * 100, 1) : 0;

        // Bloques disponibles para filtro
        $bloquesQuery = Bloque::withoutGlobalScope('lotificacion');
        if (!$filtroProj['esGlobal'] && $filtroProj['targetLotId']) {
            $bloquesQuery->where('lotificacion_id', $filtroProj['targetLotId']);
        }
        $bloquesDisponibles = $bloquesQuery->orderBy('nombre')->get();

        return array_merge($filtroProj, [
            'lotes' => $lotes,
            'totalLotes' => $totalLotes,
            'totalDisponibles' => $totalDisponibles,
            'totalReservados' => $totalReservados,
            'totalVendidos' => $totalVendidos,
            'valorTotal' => $valorTotal,
            'valorDisponible' => $valorDisponible,
            'valorReservado' => $valorReservado,
            'valorVendido' => $valorVendido,
            'areaM2Total' => $areaM2Total,
            'areaM2Disponible' => $areaM2Disponible,
            'areaM2Vendido' => $areaM2Vendido,
            'areaVrsTotal' => $areaVrsTotal,
            'areaVrsDisponible' => $areaVrsDisponible,
            'areaVrsVendido' => $areaVrsVendido,
            'porcentajeOcupacion' => $porcentajeOcupacion,
            'estadoFiltro' => $estadoFiltro,
            'bloqueFiltro' => $bloqueFiltro,
            'bloquesDisponibles' => $bloquesDisponibles,
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
            'generadoEl' => now()->locale('es')->translatedFormat('d/m/Y h:i A'),
        ]);
    }

    // =========================================================================
    // 2. REPORTE DE CARTERA DE CLIENTES Y ABONOS
    // =========================================================================

    public function carteraClientes(Request $request)
    {
        $data = $this->datosCarteraClientes($request);
        return view('reportes.cartera_clientes', $data);
    }

    public function carteraClientesPdf(Request $request)
    {
        $data = $this->datosCarteraClientes($request);
        $data['logoBase64'] = $this->getLogoBase64($data['targetLotId']);

        $pdf = Pdf::loadView('reportes.cartera_clientes_pdf', $data)
            ->setPaper('letter', 'landscape');

        return $pdf->download('reporte-cartera-clientes-' . now()->format('Ymd') . '.pdf');
    }

    public function carteraClientesExcel(Request $request)
    {
        $data = $this->datosCarteraClientes($request);
        $html = view('reportes.cartera_clientes_excel', $data)->render();

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="cartera-clientes-' . now()->format('Ymd') . '.xls"',
        ]);
    }

    private function datosCarteraClientes(Request $request): array
    {
        $filtroProj = $this->resolverFiltroProyecto($request);
        $estadoContrato = $request->get('estado_contrato', 'Vigente');
        $busqueda = trim($request->get('buscar', ''));
        $fechaDesde = $request->get('fecha_desde');
        $fechaHasta = $request->get('fecha_hasta');

        $query = Venta::withoutGlobalScope('lotificacion')
            ->with(['cliente', 'lotes.bloque.lotificacion', 'cuotas', 'abonos']);

        if (!$filtroProj['esGlobal'] && $filtroProj['targetLotId']) {
            $query->where('lotificacion_id', $filtroProj['targetLotId']);
        }

        if ($estadoContrato !== 'todos') {
            $query->where('estado_contrato', $estadoContrato);
        }

        if (!empty($fechaDesde)) {
            $query->whereDate('fecha_venta', '>=', $fechaDesde);
        }
        if (!empty($fechaHasta)) {
            $query->whereDate('fecha_venta', '<=', $fechaHasta);
        }

        if ($busqueda !== '') {
            $query->whereHas('cliente', function($q) use ($busqueda) {
                $q->where('nombres_apellidos', 'like', "%{$busqueda}%")
                  ->orWhere('identificacion', 'like', "%{$busqueda}%")
                  ->orWhere('expediente_num', 'like', "%{$busqueda}%")
                  ->orWhere('telefono', 'like', "%{$busqueda}%");
            });
        }

        $ventas = $query->latest('fecha_venta')->get();

        $filas = $ventas->map(function($venta) {
            $cliente = $venta->cliente;
            $lote = $venta->lotes->first();
            $cuotas = $venta->cuotas;

            $totalCuotas = $cuotas->count();
            $cuotasPagadas = $cuotas->where('estado', 'Pagada')->count();
            $cuotasPendientes = $totalCuotas - $cuotasPagadas;
            $cuotasEnMora = $cuotas->where('estado', 'Mora')->count();

            $totalAbonado = $venta->abonos->sum('monto_abonado');
            $saldoCapitalRestante = $cuotas->sum('saldo_restante');
            $moraAcumulada = $cuotas->sum(fn($c) => $c->mora_pendiente);

            // Próximo vencimiento
            $proximaCuota = $cuotas->whereIn('estado', ['Pendiente', 'Parcial', 'Mora'])->sortBy('fecha_vencimiento')->first();

            $estadoCliente = 'Al Día';
            if ($cuotasEnMora > 0) {
                $estadoCliente = 'En Mora (' . $cuotasEnMora . ' cuotas)';
            } elseif ($venta->estado_contrato === 'Finalizado') {
                $estadoCliente = 'Finalizado';
            } elseif ($venta->estado_contrato === 'Rescindido') {
                $estadoCliente = 'Rescindido';
            }

            $precioVenta = (float) ($venta->precio_final ?: ($venta->precio_venta ?: 0));

            return [
                'venta_id' => $venta->id_venta,
                'cliente_id' => $cliente ? $cliente->id_cliente : null,
                'cliente_nombre' => $cliente ? $cliente->nombres_apellidos : 'Sin Asignar',
                'identificacion' => $cliente ? ($cliente->identificacion ?: 'S/C') : '-',
                'expediente' => $cliente ? ($cliente->expediente_num ?: '-') : '-',
                'telefono' => $cliente ? ($cliente->telefono ?: '-') : '-',
                'lote_codigo' => $lote ? ($lote->bloque ? 'Bloque ' . $lote->bloque->nombre . ' - ' . $lote->numero_lote : $lote->numero_lote) : '-',
                'proyecto' => $lote && $lote->bloque && $lote->bloque->lotificacion ? $lote->bloque->lotificacion->nombre : 'N/A',
                'fecha_venta' => $venta->fecha_venta ? Carbon::parse($venta->fecha_venta)->format('d/m/Y') : '-',
                'precio_venta' => $precioVenta,
                'prima' => (float) ($venta->prima ?? 0),
                'total_abonado' => (float) $totalAbonado,
                'saldo_restante' => (float) $saldoCapitalRestante,
                'mora_pendiente' => (float) $moraAcumulada,
                'cuotas_pagadas' => $cuotasPagadas,
                'cuotas_totales' => $totalCuotas,
                'cuotas_pendientes' => $cuotasPendientes,
                'cuotas_mora' => $cuotasEnMora,
                'estado_cliente' => $estadoCliente,
                'estado_contrato' => $venta->estado_contrato,
                'proximo_vencimiento' => $proximaCuota ? Carbon::parse($proximaCuota->fecha_vencimiento)->format('d/m/Y') : 'N/A',
            ];
        });

        // Totales Generales
        $totalPrecioVentas = $filas->sum('precio_venta');
        $totalAbonadoGeneral = $filas->sum('total_abonado');
        $totalSaldoGeneral = $filas->sum('saldo_restante');
        $totalMoraGeneral = $filas->sum('mora_pendiente');
        $totalClientesConMora = $filas->where('cuotas_mora', '>', 0)->count();

        return array_merge($filtroProj, [
            'filas' => $filas,
            'totalContratos' => $filas->count(),
            'totalPrecioVentas' => $totalPrecioVentas,
            'totalAbonadoGeneral' => $totalAbonadoGeneral,
            'totalSaldoGeneral' => $totalSaldoGeneral,
            'totalMoraGeneral' => $totalMoraGeneral,
            'totalClientesConMora' => $totalClientesConMora,
            'estadoContrato' => $estadoContrato,
            'busqueda' => $busqueda,
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
            'generadoEl' => now()->locale('es')->translatedFormat('d/m/Y h:i A'),
        ]);
    }

    // =========================================================================
    // 3. REPORTE DE MOROSIDAD Y ANTIGÜEDAD DE SALDOS
    // =========================================================================

    public function morosidad(Request $request)
    {
        $data = $this->datosMorosidad($request);
        return view('reportes.morosidad', $data);
    }

    public function morosidadPdf(Request $request)
    {
        $data = $this->datosMorosidad($request);
        $data['logoBase64'] = $this->getLogoBase64($data['targetLotId']);

        $pdf = Pdf::loadView('reportes.morosidad_pdf', $data)
            ->setPaper('letter', 'landscape');

        return $pdf->download('reporte-morosidad-' . now()->format('Ymd') . '.pdf');
    }

    public function morosidadExcel(Request $request)
    {
        $data = $this->datosMorosidad($request);
        $html = view('reportes.morosidad_excel', $data)->render();

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="reporte-morosidad-' . now()->format('Ymd') . '.xls"',
        ]);
    }

    private function datosMorosidad(Request $request): array
    {
        $filtroProj = $this->resolverFiltroProyecto($request);
        $rangoFiltro = $request->get('rango', 'todos');
        $fechaDesde = $request->get('fecha_desde');
        $fechaHasta = $request->get('fecha_hasta');

        // Buscar todas las cuotas vencidas no pagadas
        $cuotasQuery = Cuota::whereIn('estado', ['Pendiente', 'Parcial', 'Mora'])
            ->whereDate('fecha_vencimiento', '<', now()->format('Y-m-d'))
            ->with(['venta.cliente', 'venta.lotes.bloque.lotificacion']);

        if (!$filtroProj['esGlobal'] && $filtroProj['targetLotId']) {
            $cuotasQuery->whereHas('venta', fn($q) => $q->withoutGlobalScope('lotificacion')->where('lotificacion_id', $filtroProj['targetLotId']));
        }

        if (!empty($fechaDesde)) {
            $cuotasQuery->whereDate('fecha_vencimiento', '>=', $fechaDesde);
        }
        if (!empty($fechaHasta)) {
            $cuotasQuery->whereDate('fecha_vencimiento', '<=', $fechaHasta);
        }

        $cuotasVencidas = $cuotasQuery->get();

        // Agrupar por Venta / Cliente
        $ventasAfectadas = [];
        $hoy = now()->startOfDay();

        foreach ($cuotasVencidas as $cuota) {
            $venta = $cuota->venta;
            if (!$venta || $venta->estado_contrato !== 'Vigente') continue;

            $vId = $venta->id_venta;
            $diasRetraso = Carbon::parse($cuota->fecha_vencimiento)->diffInDays($hoy);

            if (!isset($ventasAfectadas[$vId])) {
                $cliente = $venta->cliente;
                $lote = $venta->lotes->first();
                $ventasAfectadas[$vId] = [
                    'venta_id' => $vId,
                    'cliente_id' => $cliente ? $cliente->id_cliente : null,
                    'cliente_nombre' => $cliente ? $cliente->nombres_apellidos : 'Desconocido',
                    'identificacion' => $cliente ? ($cliente->identificacion ?: 'S/C') : '-',
                    'expediente' => $cliente ? ($cliente->expediente_num ?: '-') : '-',
                    'telefono' => $cliente ? ($cliente->telefono ?: '-') : '-',
                    'lote_codigo' => $lote ? ($lote->bloque ? 'Bloque ' . $lote->bloque->nombre . ' - ' . $lote->numero_lote : $lote->numero_lote) : '-',
                    'proyecto' => $lote && $lote->bloque && $lote->bloque->lotificacion ? $lote->bloque->lotificacion->nombre : 'N/A',
                    'cuotas_vencidas_count' => 0,
                    'monto_cuotas_vencidas' => 0.0,
                    'mora_acumulada' => 0.0,
                    'max_dias_retraso' => 0,
                    'fecha_mas_antigua' => $cuota->fecha_vencimiento,
                ];
            }

            $ventasAfectadas[$vId]['cuotas_vencidas_count']++;
            $ventasAfectadas[$vId]['monto_cuotas_vencidas'] += (float) $cuota->saldo_restante;
            $ventasAfectadas[$vId]['mora_acumulada'] += (float) $cuota->mora_pendiente;

            if ($diasRetraso > $ventasAfectadas[$vId]['max_dias_retraso']) {
                $ventasAfectadas[$vId]['max_dias_retraso'] = $diasRetraso;
            }
            if ($cuota->fecha_vencimiento < $ventasAfectadas[$vId]['fecha_mas_antigua']) {
                $ventasAfectadas[$vId]['fecha_mas_antigua'] = $cuota->fecha_vencimiento;
            }
        }

        // Asignar bucket de morosidad
        $filas = collect($ventasAfectadas)->map(function($v) {
            $dias = $v['max_dias_retraso'];
            if ($dias <= 30) {
                $v['bucket'] = '1_30';
                $v['bucket_label'] = '1 a 30 días';
                $v['badge_class'] = 'bg-info text-dark';
            } elseif ($dias <= 60) {
                $v['bucket'] = '31_60';
                $v['bucket_label'] = '31 a 60 días';
                $v['badge_class'] = 'bg-warning text-dark';
            } elseif ($dias <= 90) {
                $v['bucket'] = '61_90';
                $v['bucket_label'] = '61 a 90 días';
                $v['badge_class'] = 'bg-orange text-white';
            } else {
                $v['bucket'] = 'mas_90';
                $v['bucket_label'] = '+90 días (Crítico)';
                $v['badge_class'] = 'bg-danger text-white';
            }
            $v['total_deuda_vencida'] = $v['monto_cuotas_vencidas'] + $v['mora_acumulada'];
            return $v;
        })->sortByDesc('max_dias_retraso')->values();

        // Totales por bucket
        $b1_30 = $filas->where('bucket', '1_30');
        $b31_60 = $filas->where('bucket', '31_60');
        $b61_90 = $filas->where('bucket', '61_90');
        $bMas90 = $filas->where('bucket', 'mas_90');

        $resumenBuckets = [
            '1_30' => ['count' => $b1_30->count(), 'total' => $b1_30->sum('total_deuda_vencida')],
            '31_60' => ['count' => $b31_60->count(), 'total' => $b31_60->sum('total_deuda_vencida')],
            '61_90' => ['count' => $b61_90->count(), 'total' => $b61_90->sum('total_deuda_vencida')],
            'mas_90' => ['count' => $bMas90->count(), 'total' => $bMas90->sum('total_deuda_vencida')],
        ];

        if ($rangoFiltro !== 'todos') {
            $filas = $filas->where('bucket', $rangoFiltro)->values();
        }

        return array_merge($filtroProj, [
            'filas' => $filas,
            'totalClientesMora' => $filas->count(),
            'totalCapitalVencido' => $filas->sum('monto_cuotas_vencidas'),
            'totalMoraAcumulada' => $filas->sum('mora_acumulada'),
            'totalDeudaExigible' => $filas->sum('total_deuda_vencida'),
            'resumenBuckets' => $resumenBuckets,
            'rangoFiltro' => $rangoFiltro,
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
            'generadoEl' => now()->locale('es')->translatedFormat('d/m/Y h:i A'),
        ]);
    }

    // =========================================================================
    // 4. REPORTE DE PROYECCIÓN DE FLUJO Y RECAUDACIÓN FUTURA
    // =========================================================================

    public function proyeccionFlujo(Request $request)
    {
        $data = $this->datosProyeccionFlujo($request);
        return view('reportes.proyeccion_flujo', $data);
    }

    public function proyeccionFlujoExcel(Request $request)
    {
        $data = $this->datosProyeccionFlujo($request);
        $html = view('reportes.proyeccion_flujo_excel', $data)->render();

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="proyeccion-flujo-' . now()->format('Ymd') . '.xls"',
        ]);
    }

    private function datosProyeccionFlujo(Request $request): array
    {
        $filtroProj = $this->resolverFiltroProyecto($request);
        $mesesProyeccion = (int) $request->get('meses', 6);
        $fechaDesdeInput = $request->get('fecha_desde');
        $fechaHastaInput = $request->get('fecha_hasta');

        if (!empty($fechaDesdeInput) && !empty($fechaHastaInput)) {
            $hoy = Carbon::parse($fechaDesdeInput)->startOfMonth();
            $fin = Carbon::parse($fechaHastaInput)->endOfMonth();
            $mesesProyeccion = max(1, $hoy->diffInMonths($fin) + 1);
        } else {
            $hoy = now()->startOfMonth();
            $fin = $hoy->copy()->addMonths($mesesProyeccion - 1)->endOfMonth();
        }

        // Cuotas programadas de contratos vigentes
        $cuotasQuery = Cuota::whereHas('venta', function($q) use ($filtroProj) {
            $q->withoutGlobalScope('lotificacion')->where('estado_contrato', 'Vigente');
            if (!$filtroProj['esGlobal'] && $filtroProj['targetLotId']) {
                $q->where('lotificacion_id', $filtroProj['targetLotId']);
            }
        })->whereBetween('fecha_vencimiento', [$hoy->format('Y-m-d'), $fin->format('Y-m-d')]);

        $cuotas = $cuotasQuery->get();

        // Construir columnas mes a mes
        $mesesData = [];
        $labelsGrafico = [];
        $dataEsperada = [];

        for ($i = 0; $i < $mesesProyeccion; $i++) {
            $mesActual = $hoy->copy()->addMonths($i);
            if ($mesActual->greaterThan($fin)) break;

            $clave = $mesActual->format('Y-m');
            $label = ucfirst($mesActual->locale('es')->translatedFormat('M Y'));

            $cuotasDelMes = $cuotas->filter(fn($c) => substr($c->fecha_vencimiento, 0, 7) === $clave);

            $capitalEsperado = $cuotasDelMes->sum('capital');
            $interesEsperado = $cuotasDelMes->sum('interes');
            $totalEsperado = $cuotasDelMes->sum('monto_total');
            $cuotasCantidad = $cuotasDelMes->count();

            $mesesData[] = [
                'clave' => $clave,
                'label' => $label,
                'anio' => $mesActual->year,
                'mes_nombre' => ucfirst($mesActual->locale('es')->translatedFormat('F')),
                'cuotas_cantidad' => $cuotasCantidad,
                'capital_esperado' => (float) $capitalEsperado,
                'interes_esperado' => (float) $interesEsperado,
                'total_esperado' => (float) $totalEsperado,
            ];

            $labelsGrafico[] = $label;
            $dataEsperada[] = round($totalEsperado, 2);
        }

        $totalProyeccion = array_sum($dataEsperada);
        $promedioMensual = count($mesesData) > 0 ? $totalProyeccion / count($mesesData) : 0;
        $totalCuotasProyectadas = $cuotas->count();

        return array_merge($filtroProj, [
            'mesesProyeccion' => $mesesProyeccion,
            'fechaDesde' => $hoy->format('Y-m'),
            'fechaHasta' => $fin->format('Y-m'),
            'fechaDesdeCompleta' => $hoy->format('Y-m-d'),
            'fechaHastaCompleta' => $fin->format('Y-m-d'),
            'mesesData' => $mesesData,
            'labelsGrafico' => $labelsGrafico,
            'dataEsperada' => $dataEsperada,
            'totalProyeccion' => $totalProyeccion,
            'promedioMensual' => $promedioMensual,
            'totalCuotasProyectadas' => $totalCuotasProyectadas,
            'generadoEl' => now()->locale('es')->translatedFormat('d/m/Y h:i A'),
        ]);
    }
}
