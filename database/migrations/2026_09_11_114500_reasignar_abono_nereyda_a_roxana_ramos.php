<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Abono;
use App\Models\ReciboProvisional;
use App\Models\Cliente;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Reasigna el abono de $150.00 del cliente NEREYDA BRAVO RUIZ (EXP-0332)
     * registrado el 11/09/2026 por Leonardo Cruz Zavala a Roxana Ramos
     * para consolidar el corte/cierre de caja único del proyecto Colinas Santa Clara.
     */
    public function up(): void
    {
        DB::transaction(function () {
            // 1. Buscar Usuario Destino (Roxana Ramos)
            $usuarioRoxana = User::where('name', 'like', '%Roxana%')
                ->orWhere('name', 'like', '%Ramos%')
                ->orWhere('email', 'like', '%roxana%')
                ->first();

            // 2. Buscar Usuario Origen (Leonardo Cruz Zavala)
            $usuarioLeonardo = User::where('name', 'like', '%Leonardo%')
                ->orWhere('name', 'like', '%Leo%')
                ->first();

            if (!$usuarioRoxana) {
                // Si por alguna razón no se localiza por nombre parcial, buscar por rol o fallback
                $usuarioRoxana = User::where('email', 'like', '%caja%')->first();
            }

            if ($usuarioRoxana) {
                $targetUserId = $usuarioRoxana->id;

                // 3. Buscar Cliente NEREYDA BRAVO RUIZ / EXP-0332
                $cliente = Cliente::withoutGlobalScopes()
                    ->where(function ($q) {
                        $q->where('nombres_apellidos', 'like', '%NEREYDA%')
                          ->orWhere('expediente_num', 'like', '%0332%')
                          ->orWhere('expediente_num', 'like', '%332%');
                    })
                    ->first();

                // 4. Reasignar Abonos del día 11/09/2026
                $queryAbonos = Abono::withoutGlobalScopes()
                    ->whereDate('fecha_pago', '2026-09-11')
                    ->where('monto_abonado', 150.00);

                if ($cliente) {
                    $ventasIds = DB::table('ventas')->where('id_cliente', $cliente->id_cliente)->pluck('id_venta');
                    $queryAbonos->where(function ($q) use ($ventasIds, $usuarioLeonardo) {
                        $q->whereIn('id_venta', $ventasIds);
                        if ($usuarioLeonardo) {
                            $q->orWhere('user_id', $usuarioLeonardo->id);
                        }
                    });
                } elseif ($usuarioLeonardo) {
                    $queryAbonos->where('user_id', $usuarioLeonardo->id);
                }

                $abonos = $queryAbonos->get();
                foreach ($abonos as $abono) {
                    $abono->user_id = $targetUserId;
                    $abono->save();
                }

                // 5. Reasignar Recibos Provisionales si aplica
                $recibos = ReciboProvisional::withoutGlobalScopes()
                    ->where(function ($q) {
                        $q->whereDate('fecha', '2026-09-11')
                          ->orWhereDate('created_at', '2026-09-11');
                    })
                    ->where(function ($q) use ($usuarioLeonardo) {
                        $q->where('cliente_nombre', 'like', '%NEREYDA%');
                        if ($usuarioLeonardo) {
                            $q->orWhere('user_id', $usuarioLeonardo->id);
                        }
                    })
                    ->get();

                foreach ($recibos as $recibo) {
                    $recibo->user_id = $targetUserId;
                    $recibo->save();
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversible
    }
};
