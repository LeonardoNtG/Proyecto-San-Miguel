<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('configuraciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lotificacion_id')->index();
            $table->string('clave', 100);
            $table->text('valor')->nullable();
            $table->string('tipo', 30)->default('string'); // boolean, integer, decimal, string, text
            $table->string('grupo', 50)->default('general'); // cobranza, caja, ventas, recibos
            $table->string('descripcion', 255)->nullable();
            $table->timestamps();

            $table->unique(['lotificacion_id', 'clave']);
            $table->foreign('lotificacion_id')->references('id')->on('lotificaciones')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuraciones');
    }
};
