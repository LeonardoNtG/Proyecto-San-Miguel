<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rescision extends Model
{
    use HasFactory;

    protected $table = 'rescisiones';
    protected $primaryKey = 'id_rescision';

    protected $fillable = [
        'id_venta',
        'id_cliente',
        'lotificacion_id',
        'tipo',
        'lotes_afectados',
        'lotes_conservados',
        'destino_abonos',
        'monto_abonos_lote',
        'monto_transferido',
        'monto_devuelto',
        'id_venta_destino',
        'comentario',
        'user_id',
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'id_venta', 'id_venta')
                    ->withoutGlobalScope('lotificacion');
    }

    public function ventaDestino()
    {
        return $this->belongsTo(Venta::class, 'id_venta_destino', 'id_venta')
                    ->withoutGlobalScope('lotificacion');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente')
                    ->withoutGlobalScope('lotificacion');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function lotificacion()
    {
        return $this->belongsTo(Lotificacion::class, 'lotificacion_id');
    }
}
