<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * La tabla pivote 'lote_venta' pertenecía al diseño Many-to-Many descartado.
     * Ahora la relación es: un lote pertenece a una única venta (FK 'id_venta' en 'lotes').
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('lote_venta');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('lote_venta', function (Blueprint $table) {
            $table->unsignedInteger('id_venta');
            $table->unsignedInteger('id_lote');

            $table->foreign('id_venta')->references('id_venta')->on('ventas')->onDelete('cascade');
            $table->foreign('id_lote')->references('id_lote')->on('lotes')->onDelete('cascade');

            $table->primary(['id_venta', 'id_lote']);
        });
    }
};
