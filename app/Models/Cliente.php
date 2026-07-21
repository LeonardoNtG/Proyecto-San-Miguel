<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;
    // 1. Nombre de la tabla
    protected $table = 'clientes';

    // 2. Clave primaria personalizada
    protected $primaryKey = 'id_cliente';

    // 3. Campos que pueden ser asignados masivamente
    protected $fillable = [
        'expediente_num',
        'pv_num',
        'nombres_apellidos',
        'identificacion', 
        'telefono',
        'direccion',
        'estado_civil',
        'oficio',
    ];
    
    // 4. Relación: Un Cliente puede tener muchas Ventas (Promesas de Venta)
    public function ventas()
    {
        // La clave foránea en la tabla 'ventas' es 'id_cliente'
        return $this->hasMany(Venta::class, 'id_cliente', 'id_cliente');
    }
}
