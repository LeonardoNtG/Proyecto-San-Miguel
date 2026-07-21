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
       Schema::create('cierre_cajas', function (Blueprint $table) {
        $table->id();
        // La fecha es única para no tener dos cierres el mismo día
        $table->date('fecha')->unique(); 
        $table->decimal('saldo_inicial', 10, 2)->default(0);
        $table->decimal('ingresos', 10, 2)->default(0);
        $table->decimal('egresos', 10, 2)->default(0);
        $table->decimal('saldo_final', 10, 2)->default(0);
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
        Schema::dropIfExists('cierre_cajas');
    }
};
