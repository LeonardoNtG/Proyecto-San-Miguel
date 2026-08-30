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
        // Si la configuración del proyecto desactiva la exigencia de caja abierta, permitir operar libremente
        if (!setting('exigir_caja_abierta', true)) {
            return $next($request);
        }

        $fechaCaja = Carbon::today()->format('Y-m-d');
        
        $apertura = AperturaCaja::where('fecha', $fechaCaja)
                        ->where('user_id', auth()->id())
                        ->latest()
                        ->first();

        if (!$apertura) {
            return redirect()->route('reportes.index')
                ->with('error', '⚠️ ALERTA: Debe abrir la caja (nuevo turno) antes de registrar transacciones (ventas, abonos o reservas).');
        }

        $cierre = \App\Models\CierreCaja::where('fecha', $fechaCaja)
                        ->where('user_id', auth()->id())
                        ->latest()
                        ->first();

        if ($cierre && $cierre->created_at >= $apertura->created_at) {
            return redirect()->route('reportes.index')
                ->with('error', '⚠️ ALERTA: Su turno actual ya ha sido cerrado. Debe abrir una nueva caja para registrar transacciones.');
        }

        return $next($request);
    }
}
