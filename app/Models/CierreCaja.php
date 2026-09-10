<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CierreCaja extends Model
{
    use HasFactory, \App\Traits\ScopedByLotificacion;
    
    protected $fillable = [
        'fecha',
        'saldo_inicial',
        'ingresos',
        'egresos',
        'saldo_final',
        'user_id',
        'lotificacion_id',
        'efectivo_real',
        'diferencia',
        'comentario'
    ];

    protected $casts = [
        'saldo_inicial' => 'decimal:2',
        'ingresos' => 'decimal:2',
        'egresos' => 'decimal:2',
        'saldo_final' => 'decimal:2',
        'fecha' => 'date'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
