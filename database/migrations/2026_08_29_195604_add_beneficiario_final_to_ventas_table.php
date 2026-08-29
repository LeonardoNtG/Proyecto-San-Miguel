<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            // Futuro titular del lote (para casos de representación: hermanos, socios, familiares en el exterior)
            $table->string('beneficiario_final', 255)->nullable()->after('estado_contrato')
                  ->comment('Futuro propietario del lote. Escritura final a su nombre al terminar de pagar.');
            $table->text('nota_beneficiario')->nullable()->after('beneficiario_final')
                  ->comment('Nota adicional sobre el beneficiario final o la naturaleza de la representación.');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn(['beneficiario_final', 'nota_beneficiario']);
        });
    }
};
