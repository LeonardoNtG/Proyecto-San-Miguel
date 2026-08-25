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

    public function bloque()
    {
        return $this->belongsTo(Bloque::class, 'id_bloque', 'id_bloque');
    }
    
    public function historialLotes()
    {
        return $this->hasMany(HistorialLote::class, 'id_lote', 'id_lote');
    }

    public function ventas()
    {
        return $this->belongsToMany(Venta::class, 'historial_lotes', 'id_lote', 'id_venta')
                    ->withPivot('estado', 'fecha_asignacion', 'fecha_liberacion')
                    ->withTimestamps();
    }
    
    // Obtener la venta activa actual del lote (si tiene)
    public function getVentaActivaAttribute()
    {
        return $this->ventas()->wherePivot('estado', 'Activo')->first();
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
