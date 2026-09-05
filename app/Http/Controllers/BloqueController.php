<?php

namespace App\Http\Controllers;

use App\Models\Bloque;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BloqueController extends Controller
{
    /**
     * Listado de todos los Bloques registrados, con la cantidad de lotes de cada uno.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $lotificacion_id = $request->get('lotificacion_id');

        $query = Bloque::with('lotificacion')->withCount('lotes')->orderBy('nombre');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%");
            });
        }

        if ($lotificacion_id) {
            $query->where('lotificacion_id', $lotificacion_id);
        }

        $bloques = $query->paginate(15);
        $lotificaciones = \App\Models\Lotificacion::orderBy('nombre')->get();

        return view('bloques.index', compact('bloques', 'search', 'lotificacion_id', 'lotificaciones'));
    }

    // Devuelve los bloques que pertenecen a una lotificación específica
    public function getBloquesByLotificacion($lotificacionId)
    {
        $bloques = Bloque::where('lotificacion_id', $lotificacionId)
            ->orderBy('nombre')
            ->get(['id_bloque', 'nombre']);

        return response()->json($bloques);
    }

    /**
     * El formulario de creación vive como modal dentro de bloques.index.
     */
    public function create()
    {
        return redirect()->route('bloques.index');
    }

    /**
     * Guarda un nuevo Bloque.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:50',
            'lotificacion_id' => 'required|exists:lotificaciones,id',
            'descripcion' => 'nullable|string|max:255',
        ]);

        $nombre = trim($request->nombre);
        $lotificacionId = $request->lotificacion_id;

        // Comprobación exacta BINARIA para que MySQL diferencie N de Ñ
        $existe = Bloque::where('lotificacion_id', $lotificacionId)
            ->whereRaw('BINARY nombre = ?', [$nombre])
            ->exists();

        if ($existe) {
            return back()->withInput()->withErrors(['nombre' => 'El valor indicado en nombre ya se encuentra registrado en este proyecto.']);
        }

        try {
            Bloque::create($validated);
        } catch (\Illuminate\Database\QueryException $e) {
            if (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1062) {
                return back()->withInput()->withErrors(['nombre' => 'El bloque ' . $nombre . ' ya existe en este proyecto.']);
            }
            throw $e;
        }

        return redirect()->route('bloques.index')->with('success', 'Bloque creado exitosamente.');
    }

    /**
     * No se usa: el detalle de un bloque es su listado de lotes (lotes.index).
     */
    public function show($bloque)
    {
        $bloqueModel = $bloque instanceof Bloque 
            ? $bloque 
            : Bloque::withoutGlobalScope('lotificacion')->findOrFail($bloque);

        return redirect()->route('lotes.index', ['bloque' => $bloqueModel->id_bloque]);
    }

    /**
     * Formulario de edición del Bloque.
     */
    public function edit($bloque)
    {
        $bloqueModel = $bloque instanceof Bloque 
            ? $bloque 
            : Bloque::withoutGlobalScope('lotificacion')->findOrFail($bloque);

        return view('bloques.edit', ['bloque' => $bloqueModel]);
    }

    /**
     * Actualiza un Bloque existente.
     */
    public function update(Request $request, $bloque)
    {
        $bloqueModel = $bloque instanceof Bloque 
            ? $bloque 
            : Bloque::withoutGlobalScope('lotificacion')->findOrFail($bloque);

        $validated = $request->validate([
            'nombre' => 'required|string|max:50',
            'lotificacion_id' => 'required|exists:lotificaciones,id',
            'descripcion' => 'nullable|string|max:255',
        ]);

        $nombre = trim($request->nombre);
        $lotificacionId = $request->lotificacion_id;

        // Comprobación exacta BINARIA para que MySQL diferencie N de Ñ
        $existe = Bloque::where('lotificacion_id', $lotificacionId)
            ->where('id_bloque', '!=', $bloqueModel->id_bloque)
            ->whereRaw('BINARY nombre = ?', [$nombre])
            ->exists();

        if ($existe) {
            return back()->withInput()->withErrors(['nombre' => 'El valor indicado en nombre ya se encuentra registrado en este proyecto.']);
        }

        try {
            $bloqueModel->update($validated);
        } catch (\Illuminate\Database\QueryException $e) {
            if (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1062) {
                return back()->withInput()->withErrors(['nombre' => 'El bloque ' . $nombre . ' ya existe en este proyecto.']);
            }
            throw $e;
        }

        return redirect()->route('bloques.index')->with('success', 'Bloque actualizado exitosamente.');
    }

    /**
     * Eliminar un Bloque.
     */
    public function destroy($bloque)
    {
        return redirect()->route('bloques.index')
            ->with('error', 'La eliminación de bloques aún no está habilitada.');
    }
}
