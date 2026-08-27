<?php

namespace Database\Seeders;


use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Bloque;
use App\Models\Lote;

class BloqueLoteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Idempotente: correr el seeder varias veces (o sobre una BD que ya
     * tiene estos bloques/lotes de una corrida anterior) no debe fallar por
     * los índices únicos de 'nombre' y ['id_bloque', 'numero_lote']. Los
     * bloques usan updateOrCreate (para rellenar 'proyecto' en bloques ya
     * existentes sin ese dato); los lotes usan firstOrCreate para NO pisar
     * el estado real (Disponible/Reservado/Vendido) de lotes ya en uso.
     *
     * @return void
     */
    public function run()
    {
        $lotificacion = \App\Models\Lotificacion::firstOrCreate(
            ['nombre' => 'Lotificación La Campana'],
            ['ubicacion' => 'Dirección de prueba']
        );

        // Vincular al admin (User ID 1) para que tenga acceso
        $admin = \App\Models\User::find(1);
        if ($admin) {
            $admin->lotificaciones()->syncWithoutDetaching([$lotificacion->id]);
        }

        $bloquesData = [
            ['nombre' => 'A', 'lotificacion_id' => $lotificacion->id, 'descripcion' => 'Bloque principal, zona de alta demanda'],
            ['nombre' => 'B', 'lotificacion_id' => $lotificacion->id, 'descripcion' => 'Bloque intermedio, fácil acceso'],
            ['nombre' => 'C', 'lotificacion_id' => $lotificacion->id, 'descripcion' => 'Bloque trasero, vistas panorámicas'],
        ];

        $lotesPorBloque = 3;

        foreach ($bloquesData as $data) {
            // 1. Crear el Bloque
            $bloque = Bloque::updateOrCreate(
                ['nombre' => $data['nombre']],
                ['lotificacion_id' => $data['lotificacion_id'], 'descripcion' => $data['descripcion']]
            );

            // 2. Crear Lotes para el Bloque (solo los que no existan aún)
            for ($i = 1; $i <= $lotesPorBloque; $i++) {
                // Generar datos variados para cada lote
                $area = 150 + ($i * 10) + rand(1, 5); // Área base 150 m² + variación
                $precio = round(10000 + ($area * 60) + ($i * 500), 2); // Precio base + calculado

                $lote = Lote::firstOrCreate(
                    [
                        'id_bloque' => $bloque->id_bloque,
                        'numero_lote' => $bloque->nombre . '-' . str_pad($i, 2, '0', STR_PAD_LEFT), // Ej: A-01, B-02
                    ],
                    [
                        'area_metros' => $area,
                        'precio_base' => $precio,
                        'estado' => 'Disponible', // Todos inician disponibles
                    ]
                );

                // Dejar el lote A-03 no disponible (Vendido), solo si se creó en esta corrida
                if ($lote->wasRecentlyCreated && $lote->numero_lote === 'A-03') {
                    $lote->estado = 'Vendido';
                    $lote->save();
                }
            }
        }
    }
 }
