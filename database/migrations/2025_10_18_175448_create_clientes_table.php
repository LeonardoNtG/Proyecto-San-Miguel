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
        Schema::create('clientes', function (Blueprint $table) {
            // Clave primaria: id_cliente
            $table->increments('id_cliente'); 
            
            $table->string('expediente_num', 20)->unique();
            $table->string('nombres_apellidos', 200); // Nombre completo / Representante
            $table->string('identificacion', 30)->unique(); // Cédula
            $table->string('telefono', 20)->nullable();
            $table->string('direccion', 255)->nullable();
            $table->string('estado_civil', 50)->nullable();
            $table->string('oficio', 100)->nullable();
            
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
        Schema::dropIfExists('clientes');
    }
};
