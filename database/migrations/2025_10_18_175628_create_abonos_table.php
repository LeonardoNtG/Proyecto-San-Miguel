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
        Schema::create('abonos', function (Blueprint $table) {
            // Clave primaria: id_abono
            $table->increments('id_abono'); 
            
            // Clave foránea a la venta
            $table->unsignedInteger('id_venta');
            
            $table->date('fecha_pago');
            $table->decimal('monto_abonado', 10, 2);
            $table->string('tipo_pago', 50);
            $table->string('referencia', 100)->nullable();
            
            // Definición de la clave foránea
            $table->foreign('id_venta')->references('id_venta')->on('ventas');
            
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
        Schema::dropIfExists('abonos');
    }
};
