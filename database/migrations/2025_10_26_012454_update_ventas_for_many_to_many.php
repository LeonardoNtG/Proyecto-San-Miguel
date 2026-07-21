<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        Schema::table('ventas', function (Blueprint $table) {
            // 1. ELIMINAR la clave foránea y la columna id_lote
            // Nota: Debes eliminar el índice UNIQUE si existe antes de eliminar la columna.
            // Laravel manejará esto si la restricción se creó mediante 'foreign'.
            $table->dropForeign(['id_lote']); // Elimina la restricción FOREIGN KEY
            $table->dropColumn('id_lote');    // Elimina la columna

            // 2. AGREGAR la nueva columna
            $table->unsignedSmallInteger('total_lotes_vendidos')
                  ->default(1)
                  ->after('plazo_meses');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ventas', function (Blueprint $table) {
            // Revertir: Volver a agregar la columna id_lote y su clave foránea
            $table->unsignedInteger('id_lote')->nullable();
            $table->foreign('id_lote')->references('id_lote')->on('lotes');
            
            // Revertir: Eliminar la nueva columna
            $table->dropColumn('total_lotes_vendidos');
        });
    }
};
