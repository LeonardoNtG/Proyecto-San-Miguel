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
        Schema::create('auditorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('accion'); // e.g., 'Rescindió Contrato', 'Eliminó Pago'
            $table->string('modelo')->nullable(); // e.g., 'Venta', 'Abono'
            $table->unsignedBigInteger('modelo_id')->nullable(); // e.g., 5
            $table->text('detalles')->nullable(); // JSON or Text
            $table->string('ip_address')->nullable();
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
        Schema::dropIfExists('auditorias');
    }
};
