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
    public function index()
    {
        $bloques = Bloque::withCount('lotes')->orderBy('nombre')->get();

        return view('bloques.index', compact('bloques'));
    }

    // Devuelve los bloques que pertenecen a un proyecto específico (usado por el formulario de registro de clientes)
    public function getBloquesByProyecto(string $proyecto)
    {
        $bloques = Bloque::where('proyecto', $proyecto)
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
            'nombre' => 'required|string|max:50|unique:bloques,nombre',
            'proyecto' => 'required|string|max:100',
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
            'nombre' => ['required', 'string', 'max:50', Rule::unique('bloques', 'nombre')->ignore($bloque->id_bloque, 'id_bloque')],
            'proyecto' => 'required|string|max:100',
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
