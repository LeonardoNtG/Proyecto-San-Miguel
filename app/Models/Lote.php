<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lote extends Model
{
    use HasFactory, \App\Traits\ScopedByLotificacion;
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

    /**
     * Retorna el identificador legible y unificado del lote (ej: "Lote XYZ-03", "Lote A-01").
     */
    public function getNombreCompletoAttribute(): string
    {
        $bloqueNombre = trim((string) ($this->bloque?->nombre ?? ''));
        $numLote = trim((string) ($this->numero_lote ?? ''));

        // Limpiar prefijo "Bloque" si viene en el nombre del bloque
        $bloqueLimpio = trim(preg_replace('/^bloque\s*/i', '', $bloqueNombre));

        if (empty($bloqueLimpio)) {
            return $numLote ? "Lote {$numLote}" : 'Lote N/A';
        }

        // Si el número de lote ya contiene o empieza con el bloque (ej: "XYZ-03", "A-01")
        if (stripos($numLote, $bloqueLimpio) === 0 || stripos($numLote, '-') !== false) {
            return "Lote {$numLote}";
        }

        // Si es número simple (ej: "03" o "1") y el bloque es "XYZ" o "A" -> "Lote XYZ-03" o "Lote A-01"
        return "Lote {$bloqueLimpio}-{$numLote}";
    }

    /**
     * Retorna solo el código limpio del lote (ej: "XYZ-03", "A-01").
     */
    public function getCodigoLoteAttribute(): string
    {
        $bloqueNombre = trim((string) ($this->bloque?->nombre ?? ''));
        $numLote = trim((string) ($this->numero_lote ?? ''));

        $bloqueLimpio = trim(preg_replace('/^bloque\s*/i', '', $bloqueNombre));

        if (empty($bloqueLimpio)) {
            return $numLote ?: 'N/A';
        }

        if (stripos($numLote, $bloqueLimpio) === 0 || stripos($numLote, '-') !== false) {
            return $numLote;
        }

        return "{$bloqueLimpio}-{$numLote}";
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
