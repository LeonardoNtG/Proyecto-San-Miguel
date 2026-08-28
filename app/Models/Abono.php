<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Abono extends Model
{
    use HasFactory, \App\Traits\ScopedByLotificacion;
    
    // Nombre de la tabla
    protected $table = 'abonos';

    // Clave primaria personalizada
    protected $primaryKey = 'id_abono';

    // Campos que pueden ser asignados masivamente
    protected $fillable = [
        'id_venta',
        'monto_abonado',
        'fecha_pago',
        'tipo_pago',
        'metodo_pago',
        'referencia',
        'cuenta_destino',
        'ruta_recibo',
        'user_id'
    ];
    
    // Relación: Un Abono pertenece a una Venta (Many-to-One)
    public function venta()
    {
        // La clave foránea en la tabla 'abonos' es 'id_venta'
        return $this->belongsTo(Venta::class, 'id_venta', 'id_venta');
    }

    // Relación: Un Abono pertenece a un Usuario (Many-to-One)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
