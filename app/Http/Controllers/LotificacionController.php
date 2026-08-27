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

    public function index()
    {
        $lotificaciones = \App\Models\Lotificacion::all();
        return view('lotificaciones.index', compact('lotificaciones'));
    }

    public function create()
    {
        return view('lotificaciones.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'ruc' => 'nullable|string|max:50',
            'telefono' => 'nullable|string|max:50',
            'ciudad' => 'nullable|string|max:100',
            'logo' => 'nullable|image|max:2048'
        ]);

        $data = $request->except('logo');

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('logos', 'public');
        }

        \App\Models\Lotificacion::create($data);

        return redirect()->route('lotificaciones.index')->with('success', 'Lotificación creada exitosamente.');
    }

    public function edit($id)
    {
        $lotificacion = \App\Models\Lotificacion::findOrFail($id);
        return view('lotificaciones.edit', compact('lotificacion'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'ruc' => 'nullable|string|max:50',
            'telefono' => 'nullable|string|max:50',
            'ciudad' => 'nullable|string|max:100',
            'logo' => 'nullable|image|max:2048'
        ]);

        $lotificacion = \App\Models\Lotificacion::findOrFail($id);
        
        $data = $request->except('logo');

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $lotificacion->update($data);

        return redirect()->route('lotificaciones.index')->with('success', 'Lotificación actualizada exitosamente.');
    }
}
