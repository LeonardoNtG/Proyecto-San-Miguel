<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait ScopedByLotificacion
{
    protected static function bootScopedByLotificacion()
    {
        static::addGlobalScope('lotificacion', function (Builder $builder) {
            // Check if user is logged in
            if (auth()->check()) {
                $user = auth()->user();
                
                // If user is Admin, they see everything
                if ($user->hasRole('Administrador')) {
                    return;
                }

                // Get authorized lotificacion IDs using DB query to prevent infinite loop
                $authorizedIds = \Illuminate\Support\Facades\DB::table('lotificacion_user')
                    ->where('user_id', $user->id)
                    ->pluck('lotificacion_id')
                    ->toArray();

                if (empty($authorizedIds)) {
                    // If they have no authorized lotificaciones, they see nothing
                    $builder->whereRaw('1 = 0');
                    return;
                }

                // Get the table name to avoid ambiguity in joins
                $table = (new static)->getTable();

                if ($table === 'lotificaciones' || $table === 'lotificacions') {
                    $builder->whereIn("$table.id", $authorizedIds);
                } elseif ($table === 'bloques') {
                    $builder->whereIn("$table.lotificacion_id", $authorizedIds);
                } elseif ($table === 'lotes') {
                    $builder->whereHas('bloque', function ($q) use ($authorizedIds) {
                        $q->whereIn('lotificacion_id', $authorizedIds);
                    });
                } elseif ($table === 'ventas') {
                    $builder->whereIn("$table.lotificacion_id", $authorizedIds);
                } elseif ($table === 'abonos') {
                    $builder->whereHas('venta', function ($q) use ($authorizedIds) {
                        $q->whereIn('lotificacion_id', $authorizedIds);
                    });
                } elseif ($table === 'clientes') {
                    $builder->whereHas('ventas', function ($q) use ($authorizedIds) {
                        $q->whereIn('lotificacion_id', $authorizedIds);
                    });
                }
            }
        });
    }
}
