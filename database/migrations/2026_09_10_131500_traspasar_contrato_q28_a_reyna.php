<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Traspasa el contrato del Lote Q-28 de ERMICENDADEL CARMEN ESCORCIA MAIRENA (EXP-3921)
     * a REYNA MAIRENA SANCHEZ (EXP-3912) con sus abonos correspondientes.
     */
    public function up(): void
    {
        DB::transaction(function () {
            // 1. Localizar Cliente Destino (Reyna Mairena Sanchez / EXP-3912)
            $clienteDestino = DB::table('clientes')
                ->where('expediente_num', 'like', '%3912%')
                ->orWhere('nombres_apellidos', 'like', '%REYNA%MAIRENA%')
                ->first();

            // 2. Localizar Cliente Origen (Ermicendadel Carmen Escorcia Mairena / EXP-3921)
            $clienteOrigen = DB::table('clientes')
                ->where('expediente_num', 'like', '%3921%')
                ->orWhere('nombres_apellidos', 'like', '%ESCORCIA%')
                ->first();

            if (!$clienteDestino || !$clienteOrigen) {
                // Si no se encuentran por coincidencia exacta en esta base de datos, salir de forma segura
                return;
            }

            // 3. Buscar la venta del Lote Q-28 asociada a Ermicendadel
            $ventasOrigen = DB::table('ventas')->where('id_cliente', $clienteOrigen->id_cliente)->get();
            $ventaQ28Id = null;

            foreach ($ventasOrigen as $venta) {
                // Verificar si esta venta tiene asignado el lote Q-28
                $tieneQ28 = DB::table('historial_lotes')
                    ->join('lotes', 'historial_lotes.id_lote', '=', 'lotes.id_lote')
                    ->where('historial_lotes.id_venta', $venta->id_venta)
                    ->where(function ($q) {
                        $q->where('lotes.numero_lote', 'like', '%28%')
                          ->orWhere('lotes.numero_lote', 'like', '%Q-28%')
                          ->orWhere('lotes.numero_lote', 'like', '%Q-028%');
                    })
                    ->exists();

                if ($tieneQ28) {
                    $ventaQ28Id = $venta->id_venta;
                    break;
                }
            }

            // Si por alguna razón no se encontró por historial_lotes, buscar venta por precio/saldo o abonos de Q-28
            if (!$ventaQ28Id && $ventasOrigen->count() === 2) {
                // Si tiene 2 ventas, la que NO es Q-01 es Q-28
                foreach ($ventasOrigen as $venta) {
                    $esQ1 = DB::table('historial_lotes')
                        ->join('lotes', 'historial_lotes.id_lote', '=', 'lotes.id_lote')
                        ->where('historial_lotes.id_venta', $venta->id_venta)
                        ->where(function ($q) {
                            $q->where('lotes.numero_lote', 'like', '%01%')
                              ->orWhere('lotes.numero_lote', 'like', '%Q-01%')
                              ->orWhere('lotes.numero_lote', 'like', '%Q-1%');
                        })
                        ->exists();

                    if (!$esQ1) {
                        $ventaQ28Id = $venta->id_venta;
                        break;
                    }
                }
            }

            // 4. Ejecutar el traspaso si encontramos la venta
            if ($ventaQ28Id) {
                DB::table('ventas')
                    ->where('id_venta', $ventaQ28Id)
                    ->update([
                        'id_cliente' => $clienteDestino->id_cliente,
                        'updated_at' => now(),
                    ]);

                // Registrar en Auditoría si la tabla existe
                if (DB::getSchemaBuilder()->hasTable('auditorias')) {
                    DB::table('auditorias')->insert([
                        'accion'         => 'Traspaso de Contrato',
                        'modulo'         => 'Venta',
                        'id_registro'    => $ventaQ28Id,
                        'detalles'       => "<strong>Traspaso de Contrato / Corrección de Importación:</strong><br>" .
                                            "• Lote: Bloque Q - Lote Q-28<br>" .
                                            "• Titular Anterior: {$clienteOrigen->nombres_apellidos} (Exp: {$clienteOrigen->expediente_num})<br>" .
                                            "• Nuevo Titular: {$clienteDestino->nombres_apellidos} (Exp: {$clienteDestino->expediente_num})<br>" .
                                            "• Motivo: Corrección de importación de datos histórica",
                        'user_id'        => 1,
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ]);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reversible si fuera necesario
    }
};
