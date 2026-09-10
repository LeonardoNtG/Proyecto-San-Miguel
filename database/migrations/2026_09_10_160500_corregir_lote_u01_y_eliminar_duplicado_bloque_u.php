<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            DB::beginTransaction();

            $campana = \App\Models\Lotificacion::where('nombre', 'like', '%Campana%')->orWhere('id', 1)->first();
            $lotifId = $campana ? $campana->id : 1;

            $bloqueU = \App\Models\Bloque::withoutGlobalScopes()
                ->where('lotificacion_id', $lotifId)
                ->where(function ($q) {
                    $q->where('nombre', 'U')->orWhere('nombre', 'Bloque U');
                })
                ->first();

            if ($bloqueU) {
                // 1. Buscar el lote real U-01 (que estaba erróneamente nombrado T-01)
                $loteRealU01 = \App\Models\Lote::withoutGlobalScopes()
                    ->where('id_bloque', $bloqueU->id_bloque)
                    ->where(function ($q) {
                        $q->where('numero_lote', 'T-01')
                          ->orWhere('numero_lote', 'U-01')
                          ->orWhere('area_metros', 208.54);
                    })
                    ->first();

                // 2. Buscar el lote placeholder/duplicado '1'
                $lotePlaceholder = \App\Models\Lote::withoutGlobalScopes()
                    ->where('id_bloque', $bloqueU->id_bloque)
                    ->where(function ($q) {
                        $q->where('numero_lote', '1')
                          ->orWhere('numero_lote', '01')
                          ->orWhere(function ($q2) {
                              $q2->where('area_metros', 150.00)->where('precio_base', 0);
                          });
                    })
                    ->where('id_lote', '!=', $loteRealU01 ? $loteRealU01->id_lote : 0)
                    ->first();

                if ($loteRealU01) {
                    $loteRealU01->numero_lote = 'U-01';
                    $loteRealU01->area_metros = 208.54;
                    $loteRealU01->precio_base = 10057.15;
                    $loteRealU01->estado = 'Vendido';
                    $loteRealU01->save();
                }

                if ($lotePlaceholder && $loteRealU01) {
                    // Reasignar historial_lotes del placeholder al lote real
                    $historiales = DB::table('historial_lotes')
                        ->where('id_lote', $lotePlaceholder->id_lote)
                        ->get();

                    foreach ($historiales as $h) {
                        // Verificar si ya existe relación activa para el lote real
                        $existeHist = DB::table('historial_lotes')
                            ->where('id_lote', $loteRealU01->id_lote)
                            ->where('id_venta', $h->id_venta)
                            ->exists();

                        if (!$existeHist) {
                            DB::table('historial_lotes')
                                ->where('id', $h->id)
                                ->update(['id_lote' => $loteRealU01->id_lote]);
                        } else {
                            DB::table('historial_lotes')->where('id', $h->id)->delete();
                        }

                        // Recalcular cuotas de la venta del cliente (Ángel Josué Castillo)
                        \App\Http\Controllers\AbonoController::recalcularCuotas($h->id_venta);
                    }

                    // Eliminar cualquier reserva vinculada al placeholder
                    DB::table('reservas')->where('id_lote', $lotePlaceholder->id_lote)->update(['id_lote' => $loteRealU01->id_lote]);

                    // Eliminar el lote placeholder de 150 m2
                    $lotePlaceholder->delete();
                }

                // Asegurar que si Ángel Josué Castillo Castro tiene venta, esté vinculada al lote U-01
                $clienteAngel = \App\Models\Cliente::withoutGlobalScopes()
                    ->where('nombres_apellidos', 'like', '%CASTILLO CASTRO%')
                    ->orWhere('nombres_apellidos', 'like', '%ANGEL JOSUE%')
                    ->first();

                if ($clienteAngel && $loteRealU01) {
                    $ventasAngel = \App\Models\Venta::withoutGlobalScopes()
                        ->where('id_cliente', $clienteAngel->id_cliente)
                        ->get();

                    foreach ($ventasAngel as $va) {
                        $tieneHist = DB::table('historial_lotes')
                            ->where('id_venta', $va->id_venta)
                            ->where('id_lote', $loteRealU01->id_lote)
                            ->exists();

                        if (!$tieneHist) {
                            DB::table('historial_lotes')->insert([
                                'id_venta' => $va->id_venta,
                                'id_lote' => $loteRealU01->id_lote,
                                'estado' => 'Activo',
                                'fecha_asignacion' => $va->fecha_venta ?? now(),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }

                        \App\Http\Controllers\AbonoController::recalcularCuotas($va->id_venta);
                    }
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            // Continuar sin romper migraciones
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No revertir
    }
};
