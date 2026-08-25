<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistorialLote extends Model
{
    use HasFactory;

    protected $table = 'historial_lotes';

    protected $fillable = [
        'id_lote',
        'id_venta',
        'estado',
        'fecha_asignacion',
        'fecha_liberacion',
        'observaciones'
    ];

    public function lote()
    {
        return $this->belongsTo(Lote::class, 'id_lote', 'id_lote');
    }

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'id_venta', 'id_venta');
    }
}
