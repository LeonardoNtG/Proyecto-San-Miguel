<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('cuotas', function (Blueprint $table) {
            $table->id('id_cuota');
            $table->unsignedInteger('id_venta');
            
            $table->integer('numero_cuota');
            $table->date('fecha_vencimiento');
            $table->decimal('monto_total', 10, 2);
            $table->decimal('capital', 10, 2)->default(0);
            $table->decimal('interes', 10, 2)->default(0);
            $table->decimal('saldo_restante', 10, 2)->default(0); // Lo que falta por pagar de esta cuota
            
            $table->enum('estado', ['Pendiente', 'Pagada', 'Parcial', 'Mora'])->default('Pendiente');
            
            $table->timestamps();

            $table->foreign('id_venta')->references('id_venta')->on('ventas')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cuotas');
    }
};
