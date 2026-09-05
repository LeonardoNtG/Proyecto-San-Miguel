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
        Schema::table('abonos', function (Blueprint $table) {
            $table->unsignedInteger('numero_recibo')->nullable()->after('id_abono');
            $table->string('codigo_recibo', 50)->nullable()->after('numero_recibo');
        });

        // Numerar retroactivamente los abonos existentes por proyecto
        $ventas = \App\Models\Venta::withoutGlobalScope('lotificacion')->with(['abonos' => fn($q) => $q->withoutGlobalScope('lotificacion')->orderBy('id_abono', 'asc')])->get();
        $correlativosPorProyecto = [];

        foreach ($ventas as $venta) {
            $lotId = $venta->lotificacion_id ?? 1;
            if (!isset($correlativosPorProyecto[$lotId])) {
                $correlativosPorProyecto[$lotId] = 1;
            }

            foreach ($venta->abonos as $abono) {
                if (is_null($abono->numero_recibo)) {
                    $num = $correlativosPorProyecto[$lotId]++;
                    \Illuminate\Support\Facades\DB::table('abonos')
                        ->where('id_abono', $abono->id_abono)
                        ->update([
                            'numero_recibo' => $num,
                            'codigo_recibo' => (string) $num
                        ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('abonos', function (Blueprint $table) {
            $table->dropColumn(['numero_recibo', 'codigo_recibo']);
        });
    }
};
