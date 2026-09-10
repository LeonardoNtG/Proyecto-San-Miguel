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
    public function index(Request $request, Bloque $bloque)
    {
        $search = $request->get('search');

        $query = $bloque->lotes()->orderBy('numero_lote');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('numero_lote', 'like', "%{$search}%")
                  ->orWhere('estado', 'like', "%{$search}%");
            });
        }

        $lotes = $query->paginate(15);

        return view('lotes.index', compact('bloque', 'lotes', 'search'));
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
     * Generación masiva de lotes.
     */
    public function generarMasivo(Request $request, Bloque $bloque)
    {
        $request->validate([
            'prefijo' => 'nullable|string|max:5',
            'desde' => 'required|numeric|min:1',
            'hasta' => 'required|numeric|min:1|gte:desde',
            'area_metros' => 'required|numeric|min:0.01',
            'precio_base' => 'required|numeric|min:0.01',
            'estado' => ['required', Rule::in(['Disponible', 'Reservado', 'Vendido'])],
        ]);

        $prefijo = $request->input('prefijo', '');
        $desde = (int) $request->desde;
        $hasta = (int) $request->hasta;
        $creados = 0;
        $errores = 0;

        for ($i = $desde; $i <= $hasta; $i++) {
            $numeroLote = $prefijo . str_pad($i, 2, '0', STR_PAD_LEFT);

            // Evitar duplicados
            $existe = Lote::where('id_bloque', $bloque->id_bloque)
                ->where('numero_lote', $numeroLote)
                ->exists();

            if (!$existe) {
                $bloque->lotes()->create([
                    'numero_lote' => $numeroLote,
                    'area_metros' => $request->area_metros,
                    'precio_base' => $request->precio_base,
                    'estado' => $request->estado,
                ]);
                $creados++;
            } else {
                $errores++;
            }
        }

        $mensaje = "Se generaron {$creados} lotes exitosamente.";
        if ($errores > 0) {
            $mensaje .= " Hubo {$errores} lotes que no se crearon porque el número de lote ya existía.";
            return redirect()->route('lotes.index', $bloque)->with('error', $mensaje);
        }

        return redirect()->route('lotes.index', $bloque)->with('success', $mensaje);
    }

    /**
     * Formulario de edición de un Lote.
     */
    public function edit(Lote $lote)
    {
        $bloque = $lote->bloque;
        $ventaActiva = $lote->ventaActiva;
        $cliente = $ventaActiva ? $ventaActiva->cliente : null;

        return view('lotes.edit', compact('lote', 'bloque', 'ventaActiva', 'cliente'));
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
            'estado' => ['nullable', Rule::in(['Disponible', 'Reservado', 'Vendido'])],
        ]);

        $lote->update(array_filter($validated, fn($v) => !is_null($v)));

        $msg = 'Lote "' . $lote->numero_lote . '" actualizado exitosamente.';
        if ($lote->estado === 'Vendido' || $lote->estado === 'Reservado') {
            $msg .= ' (Nota: Los contratos financieros existentes mantienen sus cuotas y saldos pactados).';
        }

        return redirect()->route('lotes.index', $lote->id_bloque)->with('success', $msg);
    }

    /**
     * Elimina un Lote, siempre que no esté ya en uso (Reservado o Vendido).
     */
    public function destroy(Lote $lote)
    {
        if ($lote->estado !== 'Disponible' || $lote->ventaActiva) {
            return redirect()->route('lotes.index', $lote->id_bloque)
                ->with('error', 'No se puede eliminar el lote "' . $lote->numero_lote . '": ya está reservado o vendido.');
        }

        $idBloque = $lote->id_bloque;
        $lote->delete();

        return redirect()->route('lotes.index', $idBloque)->with('success', 'Lote eliminado exitosamente.');
    }
}
