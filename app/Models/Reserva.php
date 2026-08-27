<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reserva extends Model
{
    use HasFactory, \App\Traits\ScopedByLotificacion;

    protected $primaryKey = 'id_reserva';

    protected $fillable = [
        'id_cliente',
        'lotificacion_id',
        'monto_reserva',
        'fecha_reserva',
        'fecha_vencimiento',
        'estado',
    ];

    protected $casts = [
        'fecha_reserva' => 'date',
        'fecha_vencimiento' => 'date',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }

    public function lotificacion()
    {
        return $this->belongsTo(Lotificacion::class, 'lotificacion_id');
    }

    public function lotes()
    {
        return $this->belongsToMany(Lote::class, 'historial_lotes', 'id_reserva', 'id_lote')
                    ->withPivot('estado', 'fecha_asignacion', 'fecha_liberacion', 'observaciones')
                    ->withTimestamps();
    }
}
