<?php

namespace App\Http\Controllers;

use App\Models\Abono;
use App\Models\Salida;
use App\Models\Cliente;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GraficoController extends Controller
{
    /**
     * Muestra el dashboard interactivo de Gráficos (Ingresos, Gastos, Ingresos vs Gastos
     * y Contratos por Estado), agrupables por año, mes o día.
     */
    public function dashboard(Request $request)
    {
        return view('reportes.dashboard', $this->datosDashboard($request));
    }

    private function datosDashboard(Request $request): array
    {
        $agrupacion = $request->get('agrupacion', 'mes');
        $anio = (int) $request->get('anio', now()->year);
        $mes = (int) $request->get('mes', now()->month);

        if (!in_array($agrupacion, ['anio', 'mes', 'dia'], true)) {
            $agrupacion = 'mes';
        }
        if ($mes < 1 || $mes > 12) {
            $mes = now()->month;
        }

        [$labels, $claves, $etiquetaGrupo] = $this->construirBuckets($agrupacion, $anio, $mes);

        $ingresosPorBucket = $this->agruparPorBucket(Abono::query(), 'fecha_pago', 'monto_abonado', $agrupacion, $anio, $mes, $claves);
        $gastosPorBucket = $this->agruparPorBucket(Salida::query(), 'fecha', 'monto', $agrupacion, $anio, $mes, $claves);

        $estados = ['Vigente', 'Finalizado', 'Rescindido'];
        $contratosPorEstado = [];
        foreach ($estados as $estado) {
            $contratosPorEstado[$estado] = $this->agruparPorBucket(
                Venta::where('estado_contrato', $estado),
                'fecha_venta',
                null,
                $agrupacion,
                $anio,
                $mes,
                $claves,
                true
            );
        }

        $dataIngresos = array_values($ingresosPorBucket);
        $dataGastos = array_values($gastosPorBucket);
        $dataBalance = [];
        foreach ($dataIngresos as $i => $ingreso) {
            $dataBalance[] = round($ingreso - $dataGastos[$i], 2);
        }

        $totalIngresos = array_sum($dataIngresos);
        $totalGastos = array_sum($dataGastos);
        $balanceNeto = $totalIngresos - $totalGastos;

        $distribucionEstados = Venta::selectRaw('estado_contrato, COUNT(*) as total')
            ->groupBy('estado_contrato')
            ->pluck('total', 'estado_contrato');

        return [
            'agrupacion' => $agrupacion,
            'anio' => $anio,
            'mes' => $mes,
            'etiquetaGrupo' => $etiquetaGrupo,
            'labels' => $labels,
            'dataIngresos' => $dataIngresos,
            'dataGastos' => $dataGastos,
            'dataBalance' => $dataBalance,
            'dataVigente' => array_values($contratosPorEstado['Vigente']),
            'dataFinalizado' => array_values($contratosPorEstado['Finalizado']),
            'dataRescindido' => array_values($contratosPorEstado['Rescindido']),
            'totalIngresos' => $totalIngresos,
            'totalGastos' => $totalGastos,
            'balanceNeto' => $balanceNeto,
            'totalClientes' => Cliente::count(),
            'totalContratos' => Venta::count(),
            'totalVigentes' => (int) ($distribucionEstados['Vigente'] ?? 0),
            'totalFinalizados' => (int) ($distribucionEstados['Finalizado'] ?? 0),
            'totalRescindidos' => (int) ($distribucionEstados['Rescindido'] ?? 0),
            'aniosDisponibles' => $this->aniosDisponibles(),
            'nombresMeses' => [
                1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
            ],
            'generadoEl' => now()->locale('es')->translatedFormat('d/m/Y h:i A'),
        ];
    }

    /**
     * Construye las etiquetas (labels) y claves de agrupación (buckets) según
     * el modo elegido: por año (histórico), por mes (los 12 del año) o por
     * día (los días del mes seleccionado).
     *
     * @return array{0: array, 1: array, 2: string}
     */
    private function construirBuckets(string $agrupacion, int $anio, int $mes): array
    {
        if ($agrupacion === 'anio') {
            $anios = $this->aniosDisponibles();
            sort($anios);
            return [array_map('strval', $anios), $anios, 'Histórico por Año'];
        }

        if ($agrupacion === 'dia') {
            $totalDias = Carbon::create($anio, $mes, 1)->daysInMonth;
            $claves = range(1, $totalDias);
            $nombreMes = ucfirst(Carbon::create($anio, $mes, 1)->locale('es')->translatedFormat('F'));
            return [$claves, $claves, $nombreMes . ' ' . $anio];
        }

        $nombresMeses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        return [$nombresMeses, range(1, 12), 'Año ' . $anio];
    }

    /**
     * Suma (o cuenta) los registros de $query agrupados por año/mes/día,
     * devolviendo un valor por cada clave en $claves (0 si no hay datos).
     */
    private function agruparPorBucket($query, string $columnaFecha, ?string $columnaSuma, string $agrupacion, int $anio, int $mes, array $claves, bool $contar = false): array
    {
        switch ($agrupacion) {
            case 'anio':
                $expresion = "YEAR($columnaFecha)";
                break;

            case 'dia':
                $expresion = "DAY($columnaFecha)";
                $query->whereYear($columnaFecha, $anio)->whereMonth($columnaFecha, $mes);
                break;

            case 'mes':
            default:
                $expresion = "MONTH($columnaFecha)";
                $query->whereYear($columnaFecha, $anio);
                break;
        }

        if ($contar) {
            $filas = $query->selectRaw("$expresion as bucket, COUNT(*) as total")
                ->groupBy('bucket')
                ->pluck('total', 'bucket');
        } else {
            $filas = $query->selectRaw("$expresion as bucket, SUM($columnaSuma) as total")
                ->groupBy('bucket')
                ->pluck('total', 'bucket');
        }

        $resultado = [];
        foreach ($claves as $clave) {
            $resultado[$clave] = round((float) ($filas[$clave] ?? 0), 2);
        }

        return $resultado;
    }

    /**
     * Años disponibles para el filtro: desde el registro más antiguo
     * (abono o venta) hasta el año actual.
     */
    private function aniosDisponibles(): array
    {
        $anioActual = (int) now()->year;

        $fechas = collect([Abono::min('fecha_pago'), Venta::min('fecha_venta')])->filter();

        $primerAnio = $fechas->isNotEmpty()
            ? $fechas->map(fn ($fecha) => Carbon::parse($fecha)->year)->min()
            : $anioActual;

        $desde = min($primerAnio, $anioActual);

        return range($anioActual, $desde);
    }
}
