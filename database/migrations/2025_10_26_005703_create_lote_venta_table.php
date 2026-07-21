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
        Schema::create('lote_venta', function (Blueprint $table) {
            $table->unsignedInteger('id_venta');
            $table->unsignedInteger('id_lote');
            
            // Definición de las claves foráneas
            $table->foreign('id_venta')->references('id_venta')->on('ventas')->onDelete('cascade');
            $table->foreign('id_lote')->references('id_lote')->on('lotes')->onDelete('cascade');
            
            // Clave primaria compuesta para asegurar la unicidad del par
            $table->primary(['id_venta', 'id_lote']);
            
            // Puedes agregar timestamps si lo necesitas, pero en tablas pivote no es común
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lote_venta');
    }
};
