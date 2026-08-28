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
            'nombre' => [
                'required', 'string', 'max:50',
                Rule::unique('bloques', 'nombre')->where('lotificacion_id', $request->lotificacion_id),
            ],
            'lotificacion_id' => 'required|exists:lotificaciones,id',
            'descripcion' => 'nullable|string|max:255',
        ]);

        Bloque::create($validated);

        return redirect()->route('bloques.index')->with('success', 'Bloque creado exitosamente.');
    }

    /**
     * No se usa: el detalle de un bloque es su listado de lotes (lotes.index).
     */
    public function show(Bloque $bloque)
    {
        return redirect()->route('lotes.index', $bloque);
    }

    /**
     * Formulario de edición del Bloque.
     */
    public function edit(Bloque $bloque)
    {
        return view('bloques.edit', compact('bloque'));
    }

    /**
     * Actualiza un Bloque existente.
     */
    public function update(Request $request, Bloque $bloque)
    {
        $validated = $request->validate([
            'nombre' => [
                'required', 'string', 'max:50',
                Rule::unique('bloques', 'nombre')
                    ->where('lotificacion_id', $request->lotificacion_id)
                    ->ignore($bloque->id_bloque, 'id_bloque'),
            ],
            'lotificacion_id' => 'required|exists:lotificaciones,id',
            'descripcion' => 'nullable|string|max:255',
        ]);

        $bloque->update($validated);

        return redirect()->route('bloques.index')->with('success', 'Bloque actualizado exitosamente.');
    }

    /**
     * Eliminar un Bloque (y en cascada sus lotes) es una operación sensible
     * que todavía no se ha definido/activado. La vista ya muestra la
     * advertencia correspondiente; este método se implementará más adelante.
     */
    public function destroy(Bloque $bloque)
    {
        return redirect()->route('bloques.index')
            ->with('error', 'La eliminación de bloques aún no está habilitada.');
    }
}
