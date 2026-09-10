<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Auditoria;
use App\Models\User;

class AuditoriaController extends Controller
{
    public function index(Request $request)
    {
        $query = Auditoria::with('user')->orderBy('created_at', 'desc');

        // Filtro por búsqueda de texto
        if ($request->filled('q')) {
            $q = trim($request->input('q'));
            $query->where(function($sub) use ($q) {
                $sub->where('detalles', 'like', "%{$q}%")
                    ->orWhere('accion', 'like', "%{$q}%")
                    ->orWhere('modelo', 'like', "%{$q}%")
                    ->orWhere('modelo_id', 'like', "%{$q}%")
                    ->orWhereHas('user', function($u) use ($q) {
                        $u->where('name', 'like', "%{$q}%");
                    });
            });
        }

        // Filtro por acción
        if ($request->filled('accion')) {
            $query->where('accion', $request->input('accion'));
        }

        // Filtro por usuario
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        // Filtro por fecha
        if ($request->filled('fecha')) {
            $query->whereDate('created_at', $request->input('fecha'));
        }

        // Ocultar recálculos internos por defecto para no saturar la vista con ruido técnico
        if (!$request->filled('accion') && !$request->filled('q') && !$request->boolean('ver_todo', false)) {
            $query->where('accion', '!=', 'Recalculó Cuotas');
        }

        $auditorias = $query->paginate(25)->appends($request->all());
        $usuarios = User::orderBy('name', 'asc')->get();
        $accionesDisponibles = Auditoria::select('accion')->distinct()->pluck('accion');

        return view('auditoria.index', compact('auditorias', 'usuarios', 'accionesDisponibles'));
    }
}
