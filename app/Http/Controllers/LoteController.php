<?php

namespace App\Http\Controllers;

use App\Models\Bloque;
use App\Models\Lote;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LoteController extends Controller
{
    // Devuelve los lotes disponibles para un bloque específico (usado por el formulario de registro de clientes)
    public function getLotesByBloque($bloque_id)
    {
        // NOTA: Asegúrate de que tu modelo Lote tiene la columna 'id_bloque' y 'area_metros'
        $lotes = Lote::where('id_bloque', $bloque_id)
                      // También puedes filtrar por 'estado' si solo quieres disponibles
                      ->where('estado', 'Disponible')
                      ->get(['id_lote', 'numero_lote', 'area_metros', 'precio_base']);


        return response()->json($lotes);
    }

    /**
     * Listado de los Lotes de un Bloque específico (CRUD de Lotes).
     */
    public function index(Bloque $bloque)
    {
        $lotes = $bloque->lotes()->orderBy('numero_lote')->get();

        return view('lotes.index', compact('bloque', 'lotes'));
    }

    /**
     * El formulario de creación vive como modal dentro de lotes.index.
     */
    public function create(Bloque $bloque)
    {
        return redirect()->route('lotes.index', $bloque);
    }

    /**
     * Guarda un nuevo Lote para el Bloque dado.
     */
    public function store(Request $request, Bloque $bloque)
    {
        $validated = $request->validate([
            'numero_lote' => [
                'required', 'string', 'max:10',
                Rule::unique('lotes', 'numero_lote')->where('id_bloque', $bloque->id_bloque),
            ],
            'area_metros' => 'required|numeric|min:0.01',
            'precio_base' => 'required|numeric|min:0.01',
            'estado' => ['required', Rule::in(['Disponible', 'Reservado', 'Vendido'])],
        ]);

        $bloque->lotes()->create($validated);

        return redirect()->route('lotes.index', $bloque)->with('success', 'Lote agregado exitosamente.');
    }

    /**
     * Formulario de edición de un Lote.
     */
    public function edit(Lote $lote)
    {
        $bloque = $lote->bloque;

        return view('lotes.edit', compact('lote', 'bloque'));
    }

    /**
     * Actualiza un Lote existente.
     */
    public function update(Request $request, Lote $lote)
    {
        $validated = $request->validate([
            'numero_lote' => [
                'required', 'string', 'max:10',
                Rule::unique('lotes', 'numero_lote')
                    ->where('id_bloque', $lote->id_bloque)
                    ->ignore($lote->id_lote, 'id_lote'),
            ],
            'area_metros' => 'required|numeric|min:0.01',
            'precio_base' => 'required|numeric|min:0.01',
            'estado' => ['required', Rule::in(['Disponible', 'Reservado', 'Vendido'])],
        ]);

        $lote->update($validated);

        return redirect()->route('lotes.index', $lote->id_bloque)->with('success', 'Lote actualizado exitosamente.');
    }

    /**
     * Elimina un Lote, siempre que no esté ya en uso (Reservado o Vendido).
     */
    public function destroy(Lote $lote)
    {
        if ($lote->estado !== 'Disponible' || $lote->id_venta) {
            return redirect()->route('lotes.index', $lote->id_bloque)
                ->with('error', 'No se puede eliminar el lote "' . $lote->numero_lote . '": ya está reservado o vendido.');
        }

        $idBloque = $lote->id_bloque;
        $lote->delete();

        return redirect()->route('lotes.index', $idBloque)->with('success', 'Lote eliminado exitosamente.');
    }
}
