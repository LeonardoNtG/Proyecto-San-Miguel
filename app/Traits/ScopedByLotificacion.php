<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait ScopedByLotificacion
{
    protected static function bootScopedByLotificacion()
    {
        static::addGlobalScope('lotificacion', function (Builder $builder) {
            if (auth()->check()) {
                $table = (new static)->getTable();
                
                // No aplicar el scope a la tabla lotificaciones (los administradores deben poder ver todas,
                // y los usuarios las ven a través de la relación pivot)
                if ($table === 'lotificaciones' || $table === 'lotificacions') {
                    return;
                }

                $activeLotificacionId = session('lotificacion_id');

                if ($activeLotificacionId) {
                    if ($table === 'bloques') {
                        $builder->where("$table.lotificacion_id", $activeLotificacionId);
                    } elseif ($table === 'lotes') {
                        $builder->whereHas('bloque', function ($q) use ($activeLotificacionId) {
                            $q->where('lotificacion_id', $activeLotificacionId);
                        });
                    } elseif ($table === 'ventas') {
                        $builder->where("$table.lotificacion_id", $activeLotificacionId);
                    } elseif ($table === 'abonos') {
                        $builder->whereHas('venta', function ($q) use ($activeLotificacionId) {
                            $q->where('lotificacion_id', $activeLotificacionId);
                        });
                    } elseif ($table === 'clientes') {
                        $builder->where(function($b) use ($activeLotificacionId) {
                            $b->whereHas('ventas', function ($q) use ($activeLotificacionId) {
                                $q->where('lotificacion_id', $activeLotificacionId);
                            })->orWhereHas('reservas', function ($q) use ($activeLotificacionId) {
                                $q->where('lotificacion_id', $activeLotificacionId);
                            });
                        });
                    } elseif ($table === 'apertura_cajas' || $table === 'cierre_cajas' || $table === 'salidas' || $table === 'reservas') {
                        $builder->where("$table.lotificacion_id", $activeLotificacionId);
                    }
                }
            }
        });

        // Automatically assign lotificacion_id on creation if the model has that column
        static::creating(function ($model) {
            if (auth()->check() && session('lotificacion_id')) {
                // Check if the model has a lotificacion_id column in its schema
                if (\Illuminate\Support\Facades\Schema::hasColumn($model->getTable(), 'lotificacion_id')) {
                    if (empty($model->lotificacion_id)) {
                        $model->lotificacion_id = session('lotificacion_id');
                    }
                }
            }
        });
    }
}
