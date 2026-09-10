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
        if (!Schema::hasTable('recibos_provisionales')) {
            Schema::create('recibos_provisionales', function (Blueprint $table) {
                $table->id('id_recibo_provisional');
                $table->unsignedBigInteger('lotificacion_id')->nullable();
                $table->unsignedBigInteger('numero_recibo')->nullable();
                $table->string('codigo_recibo', 50)->nullable();
                $table->string('cliente_nombre')->nullable();
                $table->decimal('monto', 12, 2)->nullable();
                $table->string('monto_letras')->nullable();
                $table->text('concepto')->nullable();
                $table->date('fecha');
                $table->text('motivo')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamps();

                $table->foreign('lotificacion_id')->references('id')->on('lotificaciones')->onDelete('set null');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('recibos_provisionales');
    }
};
