<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class CheckLotificacionAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // Administrador tiene acceso total (bypass a validaciones estrictas por ahora, pero igual necesita contexto)
            // Si el usuario no tiene lotificaciones asignadas, denegar o redireccionar
            if ($user->lotificaciones()->count() === 0) {
                if ($user->hasRole('admin')) {
                    // Si es admin y no tiene lotificaciones, podemos asignarle la primera por defecto como contingencia,
                    // ya lo hicimos en el sync_admin.
                } else {
                    abort(403, 'No tienes acceso a ninguna lotificación.');
                }
            }

            $activeLotificacionId = session('lotificacion_id');

            // Si no hay lotificación activa, o si la que está activa no pertenece a las permitidas
            if (!$activeLotificacionId || !$user->lotificaciones->contains('id', $activeLotificacionId)) {
                $firstLotificacion = $user->lotificaciones()->first();
                if ($firstLotificacion) {
                    session(['lotificacion_id' => $firstLotificacion->id]);
                    $activeLotificacionId = $firstLotificacion->id;
                } else {
                    abort(403, 'No tienes acceso a ninguna lotificación.');
                }
            }

            // Compartir la lotificación activa y la lista de lotificaciones con todas las vistas
            View::share('activeLotificacionId', $activeLotificacionId);
            View::share('userLotificaciones', $user->lotificaciones);
        }

        return $next($request);
    }
}
