<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // 1. Crear tabla historial_lotes
        Schema::create('historial_lotes', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('id_lote');
            $table->unsignedInteger('id_venta');
            $table->enum('estado', ['Activo', 'Rescindido'])->default('Activo');
            $table->date('fecha_asignacion')->nullable();
            $table->date('fecha_liberacion')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('id_lote')->references('id_lote')->on('lotes')->onDelete('cascade');
            $table->foreign('id_venta')->references('id_venta')->on('ventas')->onDelete('cascade');
        });

        // 2. Migrar datos existentes (donde id_venta no es null)
        $lotesVendidos = DB::table('lotes')->whereNotNull('id_venta')->get();
        foreach ($lotesVendidos as $lote) {
            DB::table('historial_lotes')->insert([
                'id_lote' => $lote->id_lote,
                'id_venta' => $lote->id_venta,
                'estado' => 'Activo',
                'fecha_asignacion' => now(), // Como no sabemos la fecha exacta, usamos hoy o podríamos traerla de la venta
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Si es posible traer la fecha de venta para que sea más exacto:
        DB::table('historial_lotes')
            ->join('ventas', 'historial_lotes.id_venta', '=', 'ventas.id_venta')
            ->update(['historial_lotes.fecha_asignacion' => DB::raw('ventas.fecha_venta')]);

        // 3. Eliminar id_venta de la tabla lotes
        Schema::table('lotes', function (Blueprint $table) {
            // Primero tenemos que asegurarnos de quitar la foreign key si existe
            // La foreign key según 2026_08_20_000001_add_id_venta_to_lotes_table.php se llamó id_venta
            $table->dropForeign(['id_venta']);
            $table->dropColumn('id_venta');
        });
    }

    public function down()
    {
        // Restaurar id_venta en lotes
        Schema::table('lotes', function (Blueprint $table) {
            $table->unsignedInteger('id_venta')->nullable()->after('id_bloque');
            $table->foreign('id_venta')->references('id_venta')->on('ventas')->onDelete('set null');
        });

        // Devolver datos (solo los activos)
        $activos = DB::table('historial_lotes')->where('estado', 'Activo')->get();
        foreach ($activos as $historial) {
            DB::table('lotes')->where('id_lote', $historial->id_lote)->update(['id_venta' => $historial->id_venta]);
        }

        Schema::dropIfExists('historial_lotes');
    }
};
