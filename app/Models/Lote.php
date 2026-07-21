<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lote extends Model
{
    use HasFactory;
    protected $table = 'lotes';

    // 2. Clave primaria personalizada
    protected $primaryKey = 'id_lote';

    // 3. Campos que pueden ser asignados masivamente
    protected $fillable = [
        'id_bloque',
        'numero_lote',
        'area_metros',
        'precio_base',
        'estado',
    ];

    // 4. Relación: Un Lote pertenece a un Bloque (Many-to-One)
    // El 'foreign key' es el 'id_bloque' en la tabla 'lotes'
    public function bloque()
    {
        return $this->belongsTo(Bloque::class, 'id_bloque', 'id_bloque');
    }
    
    // 5. Relación: Un Lote puede tener varias Ventas (aunque lo normal es solo una venta 'Vigente' o 'Finalizada')
    
     public function ventas()
    {
         return $this->belongsToMany(Venta::class, 'lote_venta', 'id_lote', 'id_venta');
    }
    public function getLotesByBloque($bloque_id)
{
    $lotes = Lote::where('id_bloque', $bloque_id)
                  // FILTRO CLAVE: Solo devuelve lotes disponibles
                  ->where('estado', 'Disponible') 
                  ->get(['id_lote', 'numero_lote', 'area_metros']); 
                  
    return response()->json($lotes);
}
}
