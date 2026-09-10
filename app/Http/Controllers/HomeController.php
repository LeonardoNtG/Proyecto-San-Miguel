<?php

namespace App\Http\Controllers;

use App\Models\Abono;
use App\Models\AperturaCaja;
use App\Models\CierreCaja;
use App\Models\Cliente;
use App\Models\Cuota;
use App\Models\Lote;
use App\Models\Lotificacion;
use App\Models\Salida;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $activeLotId = session('lotificacion_id');
        $lotificacionActiva = $activeLotId ? Lotificacion::find($activeLotId) : Lotificacion::first();

        // 1. Estado de Caja del usuario hoy
        $fechaHoy = Carbon::today()->format('Y-m-d');
        $apertura = AperturaCaja::where('fecha', $fechaHoy)
            ->where('user_id', auth()->id())
            ->latest()
            ->first();

        $cierre = $apertura ? CierreCaja::where('fecha', $fechaHoy)
            ->where('user_id', auth()->id())
            ->where('created_at', '>=', $apertura->created_at)
            ->first() : null;

        $cajaAbierta = ($apertura && !$cierre);

        $saldoCajaActual = 0;
        if ($cajaAbierta) {
            $ingresosCaja = Abono::where('user_id', auth()->id())
                ->where('metodo_pago', 'Efectivo')
                ->where('created_at', '>=', $apertura->created_at)
                ->sum('monto_abonado');

            $salidasCaja = Salida::where('user_id', auth()->id())
                ->where('created_at', '>=', $apertura->created_at)
                ->sum('monto');

            $saldoCajaActual = ($apertura->monto_inicial ?? 0) + $ingresosCaja - $salidasCaja;
        }

        // 2. KPIs del Mes Actual
        $inicioMes = now()->startOfMonth();
        $finMes = now()->endOfMonth();

        // Recaudación del Mes
        $abonosMesQuery = Abono::whereBetween('fecha_pago', [$inicioMes->format('Y-m-d'), $finMes->format('Y-m-d')])
            ->whereHas('venta', function ($q) use ($activeLotId) {
                if ($activeLotId) {
                    $q->withoutGlobalScope('lotificacion')->where('lotificacion_id', $activeLotId);
                }
            });
        $recaudacionMes = (float) $abonosMesQuery->sum('monto_abonado');

        // Lotes del Proyecto
        $lotesQuery = Lote::query();
        if ($activeLotId) {
            $lotesQuery->whereHas('bloque', fn($q) => $q->where('lotificacion_id', $activeLotId));
        }
        $totalLotes = $lotesQuery->count();
        $lotesDisponibles = (clone $lotesQuery)->where('estado', 'Disponible')->count();
        $lotesReservados = (clone $lotesQuery)->where('estado', 'Reservado')->count();
        $lotesVendidos = (clone $lotesQuery)->where('estado', 'Vendido')->count();
        $porcentajeOcupacion = $totalLotes > 0 ? round(($lotesVendidos / $totalLotes) * 100, 1) : 0;

        // Clientes con Contratos Vigentes
        $ventasVigentesQuery = Venta::where('estado_contrato', 'Vigente');
        if ($activeLotId) {
            $ventasVigentesQuery->where('lotificacion_id', $activeLotId);
        }
        $totalContratosVigentes = $ventasVigentesQuery->count();

        // Mora Exigible
        $cuotasMoraQuery = Cuota::whereIn('estado', ['Pendiente', 'Parcial', 'Mora'])
            ->whereDate('fecha_vencimiento', '<', now()->format('Y-m-d'))
            ->whereHas('venta', function ($q) use ($activeLotId) {
                $q->withoutGlobalScope('lotificacion')->where('estado_contrato', 'Vigente');
                if ($activeLotId) {
                    $q->where('lotificacion_id', $activeLotId);
                }
            });
        $montoMoraExigible = (float) $cuotasMoraQuery->get()->sum(fn($c) => $c->saldo_restante + $c->mora_pendiente);
        $clientesConMora = $cuotasMoraQuery->distinct('id_venta')->count('id_venta');

        // 3. Gráfico de Ingresos de los Últimos 6 Meses
        $labelsMeses = [];
        $dataIngresosMeses = [];

        for ($i = 5; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $startM = $m->copy()->startOfMonth()->format('Y-m-d');
            $endM = $m->copy()->endOfMonth()->format('Y-m-d');

            $labelsMeses[] = ucfirst($m->locale('es')->translatedFormat('M Y'));

            $montoM = (float) Abono::whereBetween('fecha_pago', [$startM, $endM])
                ->whereHas('venta', function ($q) use ($activeLotId) {
                    if ($activeLotId) {
                        $q->withoutGlobalScope('lotificacion')->where('lotificacion_id', $activeLotId);
                    }
                })->sum('monto_abonado');

            $dataIngresosMeses[] = round($montoM, 2);
        }

        // 4. Últimos 5 Abonos Registrados
        $ultimosAbonosQuery = Abono::with(['venta.cliente', 'venta.lotes.bloque'])
            ->whereHas('venta', function ($q) use ($activeLotId) {
                if ($activeLotId) {
                    $q->withoutGlobalScope('lotificacion')->where('lotificacion_id', $activeLotId);
                }
            })->latest('id_abono')->limit(5);

        $ultimosAbonos = $ultimosAbonosQuery->get();

        // 5. Próximos Vencimientos (Próximos 7 días)
        $hoyStr = now()->format('Y-m-d');
        $en7DiasStr = now()->addDays(7)->format('Y-m-d');

        $proximosVencimientos = Cuota::whereIn('estado', ['Pendiente', 'Parcial'])
            ->whereBetween('fecha_vencimiento', [$hoyStr, $en7DiasStr])
            ->whereHas('venta', function ($q) use ($activeLotId) {
                $q->withoutGlobalScope('lotificacion')->where('estado_contrato', 'Vigente');
                if ($activeLotId) {
                    $q->where('lotificacion_id', $activeLotId);
                }
            })
            ->with(['venta.cliente', 'venta.lotes.bloque'])
            ->orderBy('fecha_vencimiento', 'asc')
            ->limit(5)
            ->get();

        return view('inicio', [
            'lotificacionActiva'    => $lotificacionActiva,
            'cajaAbierta'           => $cajaAbierta,
            'saldoCajaActual'       => $saldoCajaActual,
            'recaudacionMes'        => $recaudacionMes,
            'totalLotes'            => $totalLotes,
            'lotesDisponibles'      => $lotesDisponibles,
            'lotesReservados'       => $lotesReservados,
            'lotesVendidos'         => $lotesVendidos,
            'porcentajeOcupacion'   => $porcentajeOcupacion,
            'totalContratosVigentes'=> $totalContratosVigentes,
            'montoMoraExigible'     => $montoMoraExigible,
            'clientesConMora'       => $clientesConMora,
            'labelsMeses'           => $labelsMeses,
            'dataIngresosMeses'     => $dataIngresosMeses,
            'ultimosAbonos'         => $ultimosAbonos,
            'proximosVencimientos'  => $proximosVencimientos,
            'mesActualNombre'       => ucfirst(now()->locale('es')->translatedFormat('F Y')),
        ]);
    }
}
