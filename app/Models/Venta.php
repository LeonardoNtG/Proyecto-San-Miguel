<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    
    use HasFactory;

    protected static function booted()
    {
        static::addGlobalScope(new \App\Models\Scopes\LotificacionScope);
    }

    protected $table = 'ventas';

    //  Clave primaria personalizada
    protected $primaryKey = 'id_venta';

    // Campos que deben permitir asignación masiva
    protected $fillable = [
        'id_cliente',
        'lotificacion_id',
        'fecha_venta',
        'precio_final', // Monto total del lote
        'plazo_meses',
        'estado_contrato',
        'extension_lote',
        'cuota_mensual',
    ];

    public function lotificacion()
    {
        return $this->belongsTo(Lotificacion::class, 'lotificacion_id');
    }

    // Relación: Una Venta pertenece a un Cliente (Many-to-One)
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }

    public function abonos()
    {
        return $this->hasMany(Abono::class, 'id_venta', 'id_venta');
    }

    public function cuotas()
    {
        return $this->hasMany(Cuota::class, 'id_venta', 'id_venta');
    }

    public function historialLotes()
    {
        return $this->hasMany(HistorialLote::class, 'id_venta', 'id_venta');
    }

    public function lotes()
    {
        return $this->belongsToMany(Lote::class, 'historial_lotes', 'id_venta', 'id_lote')
                    ->withPivot('estado', 'fecha_asignacion', 'fecha_liberacion')
                    ->withTimestamps();
    }



    // Cantidad de lotes asociados a esta venta (usado en las vistas)
    public function getTotalLotesVendidosAttribute()
    {
        return $this->lotes()->count();
    }
}
