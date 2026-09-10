<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Salida extends Model
{
    use HasFactory, \App\Traits\ScopedByLotificacion;
    protected $fillable = [
        'monto',
        'descripcion',
        'metodo_pago',
        'fecha',
        'user_id',
        'lotificacion_id'
    ];

    // Opcional: Si quieres que siempre se trate como número
    protected $casts = [
        'monto' => 'decimal:2',
        'fecha' => 'date'
    ];
}
