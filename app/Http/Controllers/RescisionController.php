<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Rescision;
use App\Models\Lotificacion;

class RescisionController extends Controller
{
    /**
     * Muestra el historial completo de rescisiones y desistimientos.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $tipo = $request->get('tipo');
        $destino = $request->get('destino');

        $query = Rescision::with([
            'cliente',
            'venta.lotificacion',
            'ventaDestino',
            'user',
            'lotificacion',
        ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('lotes_afectados', 'like', "%{$search}%")
                  ->orWhere('comentario', 'like', "%{$search}%")
                  ->orWhereHas('cliente', function ($c) use ($search) {
                      $c->where('nombres_apellidos', 'like', "%{$search}%")
                        ->orWhere('identificacion', 'like', "%{$search}%")
                        ->orWhere('expediente_num', 'like', "%{$search}%");
                  });
            });
        }

        if ($tipo) {
            $query->where('tipo', $tipo);
        }

        if ($destino) {
            $query->where('destino_abonos', $destino);
        }

        $rescisiones = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('rescisiones.index', compact('rescisiones', 'search', 'tipo', 'destino'));
    }

    /**
     * Ver el detalle de una rescisión específica.
     */
    public function show($id)
    {
        $rescision = Rescision::with([
            'cliente.ventas',
            'venta',
            'ventaDestino',
            'user',
            'lotificacion',
        ])->findOrFail($id);

        return view('rescisiones.show', compact('rescision'));
    }
}
