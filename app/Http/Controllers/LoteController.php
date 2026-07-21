<?php

namespace App\Http\Controllers;

use App\Models\Lote;
use Illuminate\Http\Request;
class LoteController extends Controller
{
    // Devuelve los lotes disponibles para un bloque específico
    public function getLotesByBloque($bloque_id)
    {
        // NOTA: Asegúrate de que tu modelo Lote tiene la columna 'id_bloque' y 'area_metros'
        $lotes = Lote::where('id_bloque', $bloque_id)
                      // También puedes filtrar por 'estado' si solo quieres disponibles
                      ->where('estado', 'Disponible') 
                      ->get(['id_lote', 'numero_lote', 'area_metros']); 
                 
                      
        return response()->json($lotes);
    }
}
