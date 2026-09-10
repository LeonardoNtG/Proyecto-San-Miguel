<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega columna es_migracion para identificar abonos históricos
     * ingresados durante el proceso de migración de datos.
     * Esto permite separar los pagos operativos del día de los pagos
     * históricos que se están cargando retroactivamente.
     */
    public function up(): void
    {
        Schema::table('abonos', function (Blueprint $table) {
            if (!Schema::hasColumn('abonos', 'es_migracion')) {
                $table->boolean('es_migracion')
                      ->default(false)
                      ->after('comentario')
                      ->comment('Indica si el abono fue ingresado durante la migración histórica de datos');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('abonos', function (Blueprint $table) {
            $table->dropColumn('es_migracion');
        });
    }
};
