<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // 1. Agregar columnas
        Schema::table('bloques', function (Blueprint $table) {
            $table->unsignedBigInteger('lotificacion_id')->nullable()->after('id_bloque');
        });
        
        Schema::table('ventas', function (Blueprint $table) {
            $table->unsignedBigInteger('lotificacion_id')->nullable()->after('id_venta');
        });

        // 2. Extraer proyectos únicos y crear lotificaciones
        $proyectosBloques = DB::table('bloques')->whereNotNull('proyecto')->pluck('proyecto')->toArray();
        $proyectosVentas = DB::table('ventas')->whereNotNull('proyecto')->pluck('proyecto')->toArray();
        $proyectosUnicos = array_unique(array_merge($proyectosBloques, $proyectosVentas));

        $mapaProyectos = [];
        foreach ($proyectosUnicos as $nombre) {
            $id = DB::table('lotificaciones')->insertGetId([
                'nombre' => $nombre,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $mapaProyectos[$nombre] = $id;
        }

        // 3. Asignar lotificacion_id a los registros existentes
        foreach ($mapaProyectos as $nombre => $id) {
            DB::table('bloques')->where('proyecto', $nombre)->update(['lotificacion_id' => $id]);
            DB::table('ventas')->where('proyecto', $nombre)->update(['lotificacion_id' => $id]);
        }

        // Si quedó algún bloque/venta sin proyecto anterior, le creamos uno por defecto (o se deja nulo si es permitido).
        // En este caso el sistema viejo lo requería, así que asumimos que todos tenían proyecto. 
        // Si no, podríamos crear "Proyecto Base" y asignarlo.
        $defaultId = DB::table('lotificaciones')->first();
        if (!$defaultId && (DB::table('bloques')->count() > 0 || DB::table('ventas')->count() > 0)) {
            $defaultId = DB::table('lotificaciones')->insertGetId([
                'nombre' => 'Lotificación Principal',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('bloques')->whereNull('lotificacion_id')->update(['lotificacion_id' => $defaultId]);
            DB::table('ventas')->whereNull('lotificacion_id')->update(['lotificacion_id' => $defaultId]);
        } elseif ($defaultId) {
            DB::table('bloques')->whereNull('lotificacion_id')->update(['lotificacion_id' => $defaultId->id]);
            DB::table('ventas')->whereNull('lotificacion_id')->update(['lotificacion_id' => $defaultId->id]);
        }

        // 4. Hacer foreign keys y dropear la columna vieja
        Schema::table('bloques', function (Blueprint $table) {
            $table->dropColumn('proyecto');
            $table->foreign('lotificacion_id')->references('id')->on('lotificaciones')->onDelete('cascade');
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn('proyecto');
            $table->foreign('lotificacion_id')->references('id')->on('lotificaciones')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('bloques', function (Blueprint $table) {
            $table->dropForeign(['lotificacion_id']);
            $table->string('proyecto')->nullable();
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->dropForeign(['lotificacion_id']);
            $table->string('proyecto')->nullable();
        });

        // Revertir datos
        $lotificaciones = DB::table('lotificaciones')->get();
        foreach ($lotificaciones as $lot) {
            DB::table('bloques')->where('lotificacion_id', $lot->id)->update(['proyecto' => $lot->nombre]);
            DB::table('ventas')->where('lotificacion_id', $lot->id)->update(['proyecto' => $lot->nombre]);
        }

        Schema::table('bloques', function (Blueprint $table) {
            $table->dropColumn('lotificacion_id');
        });
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn('lotificacion_id');
        });
    }
};
