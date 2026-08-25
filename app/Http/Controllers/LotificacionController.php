<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LotificacionController extends Controller
{
    public function setLotificacionActiva(Request $request, $id)
    {
        $user = Auth::user();

        // Validar si el usuario tiene acceso a esta lotificación
        if ($user->lotificaciones->contains('id', $id)) {
            session(['lotificacion_id' => $id]);
            return redirect()->back()->with('success', 'Contexto de lotificación actualizado.');
        }

        abort(403, 'Acceso denegado a esta lotificación.');
    }
}
