<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Cada venta guarda el proyecto (heredado del bloque de los lotes
     * vendidos) para que el expediente del cliente refleje a qué proyecto
     * pertenece, sin depender de que los lotes originales sigan intactos.
     * Nullable para no romper ventas ya existentes.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('ventas', 'proyecto')) {
            Schema::table('ventas', function (Blueprint $table) {
                $table->string('proyecto', 100)->nullable()->after('id_cliente');
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
        if (Schema::hasColumn('ventas', 'proyecto')) {
            Schema::table('ventas', function (Blueprint $table) {
                $table->dropColumn('proyecto');
            });
        }
    }
};
