<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    
    use HasFactory;
    protected $table = 'ventas';

    //  Clave primaria personalizada
    protected $primaryKey = 'id_venta';

    // Campos que deben permitir asignación masiva
    protected $fillable = [
        'id_cliente',
        'proyecto',
        'fecha_venta',
        'precio_final', // Monto total del lote
        'plazo_meses',
        'estado_contrato',
        'extension_lote',
        'cuota_mensual',
    ];

    // Relación: Una Venta pertenece a un Cliente (Many-to-One)
    public function cliente()
    {
        // La clave foránea en la tabla 'ventas' es 'id_cliente'
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }

    // Relación: Una Venta puede tener muchos Lotes, pero un Lote pertenece a una sola Venta (One-to-Many)
    public function lotes()
    {
        return $this->hasMany(Lote::class, 'id_venta', 'id_venta');
    }

    // Relación: Una Venta tiene muchos Abonos (One-to-Many)
    public function abonos()
    {
        // La clave foránea en la tabla 'abonos' es 'id_venta'
        return $this->hasMany(Abono::class, 'id_venta', 'id_venta');
    }

    // Cantidad de lotes asociados a esta venta (usado en las vistas)
    public function getTotalLotesVendidosAttribute()
    {
        return $this->lotes()->count();
    }
}
