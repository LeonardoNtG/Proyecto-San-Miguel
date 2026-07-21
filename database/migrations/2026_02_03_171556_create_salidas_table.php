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
        Schema::create('salidas', function (Blueprint $table) {
        $table->id();
        // 10,2 para permitir montos hasta 99,999,999.99
        $table->decimal('monto', 10, 2); 
        $table->string('descripcion'); // El "Por qué"
        $table->date('fecha'); // Agrupar por día
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('salidas');
    }
};
