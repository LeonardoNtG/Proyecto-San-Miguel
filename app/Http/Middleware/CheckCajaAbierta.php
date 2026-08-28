<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\AperturaCaja;

class CheckCajaAbierta
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $fechaCaja = Carbon::today()->format('Y-m-d');
        $apertura = AperturaCaja::where('fecha', $fechaCaja)
                        ->where('user_id', auth()->id())
                        ->first();

        if (!$apertura) {
            return redirect()->route('reportes.index')
                ->with('error', '⚠️ ALERTA: Debe abrir la caja del día de hoy antes de registrar transacciones (ventas, abonos o reservas).');
        }

        $cierre = \App\Models\CierreCaja::where('fecha', $fechaCaja)
                        ->where('user_id', auth()->id())
                        ->first();

        if ($cierre) {
            return redirect()->route('reportes.index')
                ->with('error', '⚠️ ALERTA: La caja del día de hoy ya ha sido cerrada. No puede registrar más transacciones.');
        }

        return $next($request);
    }
}
