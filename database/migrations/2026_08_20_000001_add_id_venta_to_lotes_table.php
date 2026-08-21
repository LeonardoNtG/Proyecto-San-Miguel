<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('lotes', 'id_venta')) {
            Schema::table('lotes', function (Blueprint $table) {
                // Un lote pertenece a una sola venta (Many-to-One): la FK vive en 'lotes'
                $table->unsignedInteger('id_venta')->nullable()->after('id_bloque');
                $table->foreign('id_venta')->references('id_venta')->on('ventas')->onDelete('set null');
            });
        }

        // Migra las asociaciones existentes desde la tabla pivote 'lote_venta'
        // (diseño Many-to-Many descartado) antes de que sea eliminada, para no
        // perder la venta asociada a cada lote ya vendido.
        if (Schema::hasTable('lote_venta')) {
            DB::table('lote_venta')->orderBy('id_venta')->each(function ($pivote) {
                DB::table('lotes')
                    ->where('id_lote', $pivote->id_lote)
                    ->update(['id_venta' => $pivote->id_venta]);
            });
        }

        // En bases de datos donde la migración a many-to-many nunca llegó a
        // completarse, 'ventas.id_lote' (diseño original uno-a-uno) sigue
        // siendo la única fuente de la asociación lote-venta. La migramos
        // también, sin pisar lo que ya se haya resuelto desde 'lote_venta'.
        if (Schema::hasColumn('ventas', 'id_lote')) {
            DB::table('ventas')->whereNotNull('id_lote')->orderBy('id_venta')->each(function ($venta) {
                DB::table('lotes')
                    ->where('id_lote', $venta->id_lote)
                    ->whereNull('id_venta')
                    ->update(['id_venta' => $venta->id_venta]);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('lotes', 'id_venta')) {
            Schema::table('lotes', function (Blueprint $table) {
                $table->dropForeign(['id_venta']);
                $table->dropColumn('id_venta');
            });
        }
    }
};
