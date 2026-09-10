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
        Schema::create('rescisiones', function (Blueprint $table) {
            $table->id('id_rescision');
            $table->unsignedBigInteger('id_venta');
            $table->unsignedBigInteger('id_cliente');
            $table->unsignedBigInteger('lotificacion_id')->nullable();
            
            // Tipo de rescisión: 'Parcial' o 'Total'
            $table->string('tipo', 50);

            // Nombres de lotes afectados y conservados
            $table->text('lotes_afectados');
            $table->text('lotes_conservados')->nullable();

            // Destino de los abonos del lote desistido:
            // 'acreditar_otro_lote', 'devolucion_efectivo', 'sin_devolucion'
            $table->string('destino_abonos', 50);

            // Montos
            $table->decimal('monto_abonos_lote', 12, 2)->default(0);
            $table->decimal('monto_transferido', 12, 2)->default(0);
            $table->decimal('monto_devuelto', 12, 2)->default(0);

            // Si el monto fue transferido a otro contrato del mismo cliente
            $table->unsignedBigInteger('id_venta_destino')->nullable();

            // Motivo y comentario obligatorio
            $table->text('comentario');

            // Auditoría de usuario
            $table->unsignedBigInteger('user_id')->nullable();

            $table->timestamps();

            // Índices
            $table->index('id_venta');
            $table->index('id_cliente');
            $table->index('lotificacion_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rescisiones');
    }
};
