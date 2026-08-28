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
        Schema::create('reservas', function (Blueprint $table) {
            $table->increments('id_reserva');
            $table->unsignedInteger('id_cliente');
            $table->unsignedBigInteger('lotificacion_id');
            $table->decimal('monto_reserva', 10, 2);
            $table->date('fecha_reserva');
            $table->date('fecha_vencimiento');
            $table->enum('estado', ['Activa', 'Formalizada', 'Vencida', 'Anulada'])->default('Activa');
            $table->timestamps();

            $table->foreign('id_cliente')->references('id_cliente')->on('clientes')->onDelete('restrict');
            $table->foreign('lotificacion_id')->references('id')->on('lotificaciones')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reservas');
    }
};
