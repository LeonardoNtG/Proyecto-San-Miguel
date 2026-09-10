<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AperturaCaja extends Model
{
    use HasFactory, \App\Traits\ScopedByLotificacion;
    
    protected $table = 'apertura_cajas';

    protected $fillable = [
        'user_id',
        'lotificacion_id',
        'monto_inicial',
        'fecha'
    ];

    protected $casts = [
        'monto_inicial' => 'decimal:2',
        'fecha' => 'date'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
