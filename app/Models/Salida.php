<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Salida extends Model
{
    use HasFactory;
    protected $fillable = [
        'monto',
        'descripcion',
        'fecha'
    ];

    // Opcional: Si quieres que siempre se trate como número
    protected $casts = [
        'monto' => 'decimal:2',
        'fecha' => 'date'
    ];
}
