<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cuota extends Model
{
    use HasFactory;

    protected $table = 'cuotas';
    protected $primaryKey = 'id_cuota';

    protected $fillable = [
        'id_venta',
        'numero_cuota',
        'fecha_vencimiento',
        'monto_total',
        'capital',
        'interes',
        'saldo_restante',
        'estado'
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'id_venta', 'id_venta');
    }

    public function abonos()
    {
        return $this->hasMany(Abono::class, 'id_cuota', 'id_cuota');
    }

    // Accessor para obtener la mora que aún falta por pagar o exonerar
    public function getMoraPendienteAttribute()
    {
        $pendiente = $this->mora_calculada - $this->mora_exonerada - $this->mora_pagada;
        return $pendiente > 0 ? $pendiente : 0;
    }
}
