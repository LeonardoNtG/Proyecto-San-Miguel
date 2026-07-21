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
        Schema::create('ventas', function (Blueprint $table) {
            // Clave primaria: id_venta
            $table->increments('id_venta'); 
            
            // Claves foráneas
            $table->unsignedInteger('id_cliente');
            $table->unsignedInteger('id_lote')->unique(); // Restricción: un lote solo tiene UNA venta
            
            $table->date('fecha_venta');
            $table->decimal('precio_final', 10, 2);
            $table->integer('plazo_meses');
            $table->decimal('cuota_mensual', 10, 2);
            $table->string('extension_lote', 50); // Guardamos la extensión como texto para referencia
            $table->enum('estado_contrato', ['Vigente', 'Rescindido', 'Finalizado'])->default('Vigente');

            // Definición de las claves foráneas
            $table->foreign('id_cliente')->references('id_cliente')->on('clientes');
            $table->foreign('id_lote')->references('id_lote')->on('lotes');
            
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
        Schema::dropIfExists('ventas');
    }
};
