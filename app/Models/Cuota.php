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
        $pendiente = round((float)$this->mora_calculada - (float)$this->mora_exonerada - (float)$this->mora_pagada, 2);
        return $pendiente > 0 ? $pendiente : 0;
    }

    /**
     * Calcula y actualiza la mora de la cuota según las reglas del proyecto (mora mensual proporcional a los días de retraso).
     */
    public function recalcularMoraSegunConfiguracion(): float
    {
        $lotificacionId = $this->venta ? $this->venta->lotificacion_id : null;

        // 1. ¿Está activo el cobro de mora en el proyecto?
        if (!setting('cobrar_mora', true, $lotificacionId)) {
            $this->mora_calculada = 0;
            if ($this->estado === 'Mora' && $this->saldo_restante > 0) {
                $this->estado = 'Pendiente';
            }
            $this->save();
            return 0.0;
        }

        // 2. Si la cuota ya está pagada y no debe mora
        if ($this->estado === 'Pagada') {
            return (float) $this->mora_calculada;
        }

        $fechaVencimiento = \Carbon\Carbon::parse($this->fecha_vencimiento);
        $hoy = \Carbon\Carbon::now()->startOfDay();

        // 3. Evaluar inicio de mora (tras vencimiento o tras 1 mes vencido)
        $inicioMoraRegla = setting('inicio_mora', 'vencimiento', $lotificacionId);
        $fechaBaseMora = ($inicioMoraRegla === 'mes_vencido') 
            ? $fechaVencimiento->copy()->addMonth() 
            : $fechaVencimiento->copy();

        if ($hoy->lessThanOrEqualTo($fechaBaseMora)) {
            $this->mora_calculada = 0;
            $this->save();
            return 0.0;
        }

        $diasRetraso = $fechaBaseMora->diffInDays($hoy);
        $diasGracia = (int) setting('dias_gracia_mora', 5, $lotificacionId);

        if ($diasRetraso <= $diasGracia) {
            $this->mora_calculada = 0;
            $this->save();
            return 0.0;
        }

        // 4. Parámetros de mora del proyecto
        $tipoMora = setting('tipo_mora', 'porcentaje', $lotificacionId);
        $valorMora = (float) setting('valor_mora', 5.00, $lotificacionId);

        // Mora Mensual
        if ($tipoMora === 'porcentaje') {
            $moraMensual = ((float) $this->monto_total) * ($valorMora / 100);
        } else {
            $moraMensual = $valorMora;
        }

        // Equivalente diario (mes comercial de 30 días)
        $moraDiaria = $moraMensual / 30;
        $moraTotal = round($diasRetraso * $moraDiaria, 2);

        $this->mora_calculada = $moraTotal;
        if ($moraTotal > 0 && $this->saldo_restante > 0) {
            $this->estado = 'Mora';
        }
        $this->save();

        return $moraTotal;
    }
}
