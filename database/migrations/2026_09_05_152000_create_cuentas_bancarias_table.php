<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('cuentas_bancarias')) {
            Schema::create('cuentas_bancarias', function (Blueprint $table) {
                $table->id();
                $table->string('banco', 100);
                $table->string('moneda', 10)->default('$'); // $, C$
                $table->string('numero_cuenta', 100);
                $table->string('titular', 255);
                $table->enum('estado', ['Activa', 'Inactiva'])->default('Activa');
                $table->unsignedBigInteger('lotificacion_id')->nullable();
                $table->timestamps();
            });
        }

        // Sembrar las 5 cuentas iniciales solicitadas si no existen
        $cuentasIniciales = [
            [
                'banco' => 'Banpro',
                'moneda' => '$',
                'numero_cuenta' => '10021210290831',
                'titular' => 'Ángeles Nazareth Cruz',
                'estado' => 'Activa',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'banco' => 'BAC',
                'moneda' => '$',
                'numero_cuenta' => '369263405',
                'titular' => 'Ahmed Meneses Centeno',
                'estado' => 'Activa',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'banco' => 'BAC',
                'moneda' => 'C$',
                'numero_cuenta' => '366146926',
                'titular' => 'Ahmed Meneses Centeno',
                'estado' => 'Activa',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'banco' => 'BAC',
                'moneda' => '$',
                'numero_cuenta' => '369599956',
                'titular' => 'Ángeles Nazareth Cruz',
                'estado' => 'Activa',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'banco' => 'BAC',
                'moneda' => '$',
                'numero_cuenta' => '369599998',
                'titular' => 'Ángeles Nazareth Cruz',
                'estado' => 'Activa',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($cuentasIniciales as $cta) {
            $existe = DB::table('cuentas_bancarias')
                ->where('numero_cuenta', $cta['numero_cuenta'])
                ->where('banco', $cta['banco'])
                ->exists();

            if (!$existe) {
                DB::table('cuentas_bancarias')->insert($cta);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuentas_bancarias');
    }
};
