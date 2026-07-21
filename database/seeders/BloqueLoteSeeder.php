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
     * @return void
     */
    public function run()
    {
        $bloquesData = [
            ['nombre' => 'A', 'descripcion' => 'Bloque principal, zona de alta demanda'],
            ['nombre' => 'B', 'descripcion' => 'Bloque intermedio, fácil acceso'],
            ['nombre' => 'C', 'descripcion' => 'Bloque trasero, vistas panorámicas'],
        ];

        $lotesPorBloque = 3;

        foreach ($bloquesData as $data) {
            // 1. Crear el Bloque
            $bloque = Bloque::create($data);

            // 2. Crear Lotes para el Bloque
            for ($i = 1; $i <= $lotesPorBloque; $i++) {
                // Generar datos variados para cada lote
                $area = 150 + ($i * 10) + rand(1, 5); // Área base 150 m² + variación
                $precio = round(10000 + ($area * 60) + ($i * 500), 2); // Precio base + calculado

                Lote::create([
                    'id_bloque' => $bloque->id_bloque,
                    'numero_lote' => $bloque->nombre . '-' . str_pad($i, 2, '0', STR_PAD_LEFT), // Ej: A-01, B-02
                    'area_metros' => $area,
                    'precio_base' => $precio,
                    'estado' => 'Disponible', // Todos inician disponibles
                ]);
            }
        }

        // Dejar un lote no disponible (Vendidos) para probar la exclusión en el formulario
        $loteVendido = Lote::where('numero_lote', 'A-03')->first();
        if ($loteVendido) {
            $loteVendido->estado = 'Vendido';
            $loteVendido->save();
        }
    }
 }

