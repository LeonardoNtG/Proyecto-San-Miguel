<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Auditoría y corrección integral de contratos, lotes y abonos para:
     * 1. ERMICENDA DEL CARMEN ESCORCIA MAIRENA (EXP-3921) -> 1 Contrato (Lote Q-01, 4 abonos, total $400.00)
     * 2. REYNA MAIRENA SANCHEZ (EXP-3912) -> 3 Contratos (Lote Q-24, Lotes Q-25,26,27, Lote Q-28, total $1,000.00)
     */
    public function up(): void
    {
        DB::transaction(function () {
            // ─────────────────────────────────────────────────────────────
            // 1. LOCALIZAR CLIENTES
            // ─────────────────────────────────────────────────────────────
            $clienteErmicenda = DB::table('clientes')
                ->where('expediente_num', 'like', '%3921%')
                ->orWhere('nombres_apellidos', 'like', '%ESCORCIA%')
                ->first();

            $clienteReyna = DB::table('clientes')
                ->where('expediente_num', 'like', '%3912%')
                ->orWhere('nombres_apellidos', 'like', '%REYNA%MAIRENA%')
                ->first();

            if (!$clienteErmicenda || !$clienteReyna) {
                return;
            }

            // Actualizar datos personales y PV si aplica
            DB::table('clientes')->where('id_cliente', $clienteErmicenda->id_cliente)->update([
                'nombres_apellidos' => 'ERMICENDA DEL CARMEN ESCORCIA MAIRENA',
                'identificacion'    => '448-280566-0000B',
                'telefono'          => '50558208776',
                'pv_num'            => 'PENDERM',
                'updated_at'        => now(),
            ]);

            DB::table('clientes')->where('id_cliente', $clienteReyna->id_cliente)->update([
                'nombres_apellidos' => 'REYNA MAIRENA SANCHEZ',
                'identificacion'    => '448-050145-0000P',
                'telefono'          => '50558208776',
                'updated_at'        => now(),
            ]);

            // ─────────────────────────────────────────────────────────────
            // 2. REASIGNAR CONTRATO Q-28 A REYNA
            // ─────────────────────────────────────────────────────────────
            // Buscar la venta que contiene el lote Q-28
            $loteQ28 = DB::table('lotes')
                ->where('numero_lote', '28')
                ->orWhere('numero_lote', 'Q-28')
                ->orWhere('numero_lote', 'Q-028')
                ->first();

            $ventaQ28Id = null;

            if ($loteQ28) {
                $histQ28 = DB::table('historial_lotes')
                    ->where('id_lote', $loteQ28->id_lote)
                    ->where('estado', 'Activo')
                    ->first();

                if ($histQ28) {
                    $ventaQ28Id = $histQ28->id_venta;
                }
            }

            // Si no se halló por lote, buscar venta asociada a Ermicenda que no sea Q-01
            if (!$ventaQ28Id) {
                $ventasErmicenda = DB::table('ventas')->where('id_cliente', $clienteErmicenda->id_cliente)->get();
                if ($ventasErmicenda->count() > 1) {
                    foreach ($ventasErmicenda as $v) {
                        $tieneQ1 = DB::table('historial_lotes')
                            ->join('lotes', 'historial_lotes.id_lote', '=', 'lotes.id_lote')
                            ->where('historial_lotes.id_venta', $v->id_venta)
                            ->where(function($q) {
                                $q->where('lotes.numero_lote', '1')
                                  ->orWhere('lotes.numero_lote', '01')
                                  ->orWhere('lotes.numero_lote', 'Q-01')
                                  ->orWhere('lotes.numero_lote', 'Q-1');
                            })
                            ->exists();

                        if (!$tieneQ1) {
                            $ventaQ28Id = $v->id_venta;
                            break;
                        }
                    }
                }
            }

            // Trasladar la venta de Q-28 a Reyna
            if ($ventaQ28Id) {
                DB::table('ventas')->where('id_venta', $ventaQ28Id)->update([
                    'id_cliente'      => $clienteReyna->id_cliente,
                    'estado_contrato' => 'Vigente',
                    'updated_at'      => now(),
                ]);
            }

            // ─────────────────────────────────────────────────────────────
            // 3. AUDITORÍA Y AJUSTE DE ABONOS DE ERMICENDA (LOTE Q-01)
            // ─────────────────────────────────────────────────────────────
            // Ventas de Ermicenda (ahora solo debe tener Q-01)
            $ventaQ1 = DB::table('ventas')->where('id_cliente', $clienteErmicenda->id_cliente)->first();

            if ($ventaQ1) {
                // Lista exacta de abonos según estado de cuenta:
                // 1) 05/05/2026 | Ref: 92   | $100.00
                // 2) 10/06/2026 | Ref: 654  | $100.00
                // 3) 13/07/2026 | Ref: 1067 | $100.00
                // 4) 08/08/2026 | Ref: 1340 | $100.00
                $abonosEsperadosQ1 = [
                    ['fecha' => '2026-05-05', 'ref' => '92',   'monto' => 100.00],
                    ['fecha' => '2026-06-10', 'ref' => '654',  'monto' => 100.00],
                    ['fecha' => '2026-07-13', 'ref' => '1067', 'monto' => 100.00],
                    ['fecha' => '2026-08-08', 'ref' => '1340', 'monto' => 100.00],
                ];

                foreach ($abonosEsperadosQ1 as $abEsp) {
                    $existe = DB::table('abonos')
                        ->where('id_venta', $ventaQ1->id_venta)
                        ->where(function ($q) use ($abEsp) {
                            $q->where('referencia', $abEsp['ref'])
                              ->orWhere('numero_recibo', (int)$abEsp['ref'])
                              ->orWhere('codigo_recibo', $abEsp['ref']);
                        })
                        ->exists();

                    if (!$existe) {
                        DB::table('abonos')->insert([
                            'id_venta'       => $ventaQ1->id_venta,
                            'numero_recibo'  => (int)$abEsp['ref'],
                            'codigo_recibo'  => $abEsp['ref'],
                            'fecha_pago'     => $abEsp['fecha'],
                            'monto_abonado'  => $abEsp['monto'],
                            'tipo_pago'      => 'Cuota',
                            'metodo_pago'    => 'Efectivo',
                            'referencia'     => $abEsp['ref'],
                            'user_id'        => 1,
                            'es_migracion'   => 1,
                            'created_at'     => Carbon::parse($abEsp['fecha']),
                            'updated_at'     => now(),
                        ]);
                    }
                }
            }

            // ─────────────────────────────────────────────────────────────
            // 4. AUDITORÍA Y AJUSTE DE ABONOS DE REYNA
            // ─────────────────────────────────────────────────────────────
            // Contrato Q-28:
            // 1) 06/05/2026 | Ref: 108 | $100.00
            // 2) 10/06/2026 | Ref: 653 | $100.00
            if ($ventaQ28Id) {
                $abonosEsperadosQ28 = [
                    ['fecha' => '2026-05-06', 'ref' => '108', 'monto' => 100.00],
                    ['fecha' => '2026-06-10', 'ref' => '653', 'monto' => 100.00],
                ];

                foreach ($abonosEsperadosQ28 as $abEsp) {
                    $existe = DB::table('abonos')
                        ->where('id_venta', $ventaQ28Id)
                        ->where(function ($q) use ($abEsp) {
                            $q->where('referencia', $abEsp['ref'])
                              ->orWhere('numero_recibo', (int)$abEsp['ref'])
                              ->orWhere('codigo_recibo', $abEsp['ref']);
                        })
                        ->exists();

                    if (!$existe) {
                        DB::table('abonos')->insert([
                            'id_venta'       => $ventaQ28Id,
                            'numero_recibo'  => (int)$abEsp['ref'],
                            'codigo_recibo'  => $abEsp['ref'],
                            'fecha_pago'     => $abEsp['fecha'],
                            'monto_abonado'  => $abEsp['monto'],
                            'tipo_pago'      => 'Cuota',
                            'metodo_pago'    => 'Efectivo',
                            'referencia'     => $abEsp['ref'],
                            'user_id'        => 1,
                            'es_migracion'   => 1,
                            'created_at'     => Carbon::parse($abEsp['fecha']),
                            'updated_at'     => now(),
                        ]);
                    }
                }
            }

            // ─────────────────────────────────────────────────────────────
            // 5. RECALCULAR PLAN DE CUOTAS PARA TODAS LAS VENTAS AFECTADAS
            // ─────────────────────────────────────────────────────────────
            $ventasTotales = DB::table('ventas')
                ->whereIn('id_cliente', [$clienteErmicenda->id_cliente, $clienteReyna->id_cliente])
                ->get();

            foreach ($ventasTotales as $v) {
                \App\Http\Controllers\AbonoController::recalcularCuotas($v->id_venta);
            }

            // ─────────────────────────────────────────────────────────────
            // 6. REGISTRAR BITÁCORA DE AUDITORÍA
            // ─────────────────────────────────────────────────────────────
            if (DB::getSchemaBuilder()->hasTable('auditorias')) {
                DB::table('auditorias')->insert([
                    'accion'      => 'Auditoría y Corrección de Cartera',
                    'modulo'      => 'Clientes / Ventas',
                    'id_registro' => $clienteReyna->id_cliente,
                    'detalles'    => "<strong>Corrección Integral de Estados de Cuenta:</strong><br>" .
                                     "• <strong>Ermicenda Escorcia (EXP-3921):</strong> 1 Contrato (Q-01), 4 abonos (Total $400.00), Saldo pendiente: $8,100.00.<br>" .
                                     "• <strong>Reyna Mairena (EXP-3912):</strong> 3 Contratos (Q-24, Q-25/26/27, Q-28), 6 abonos (Total $1,000.00), Saldo pendiente: $41,500.00.",
                    'user_id'     => 1,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No destructivo
    }
};
