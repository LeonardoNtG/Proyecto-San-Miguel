<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Cada bloque pertenece a un proyecto (ej: "Lotificación La Campana").
     * Nullable para no romper bloques ya existentes que aún no tengan
     * proyecto asignado; el formulario de Bloques lo exige como requerido
     * para los nuevos registros.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('bloques', 'proyecto')) {
            Schema::table('bloques', function (Blueprint $table) {
                $table->string('proyecto', 100)->nullable()->after('nombre');
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
        if (Schema::hasColumn('bloques', 'proyecto')) {
            Schema::table('bloques', function (Blueprint $table) {
                $table->dropColumn('proyecto');
            });
        }
    }
};
