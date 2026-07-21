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
    public function up(): void
    {
        Schema::create('lotes', function (Blueprint $table) {
            // Clave primaria: id_lote
            $table->increments('id_lote'); 
            
            // Clave foránea: id_bloque (debe ser del mismo tipo que la PK de 'bloques')
            $table->unsignedInteger('id_bloque');
            
            $table->string('numero_lote', 10);
            $table->decimal('area_metros', 8, 2);
            $table->decimal('precio_base', 10, 2);
            $table->enum('estado', ['Disponible', 'Reservado', 'Vendido'])->default('Disponible');
            
            // Restricción para que el número de lote sea único dentro de un bloque
            $table->unique(['id_bloque', 'numero_lote']);

            // Definición de la clave foránea
            $table->foreign('id_bloque')->references('id_bloque')->on('bloques');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    
   public function down(): void
    {
        Schema::dropIfExists('lotes');
    }
};
