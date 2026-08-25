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
        Schema::table('historial_lotes', function (Blueprint $table) {
            $table->unsignedInteger('id_reserva')->nullable()->after('id_lote');
            $table->foreign('id_reserva')->references('id_reserva')->on('reservas')->onDelete('cascade');
        });
        
        // Use raw SQL to modify existing columns without doctrine/dbal
        DB::statement("ALTER TABLE historial_lotes MODIFY COLUMN id_venta INT UNSIGNED NULL");
        DB::statement("ALTER TABLE historial_lotes MODIFY COLUMN estado ENUM('Activo', 'Rescindido', 'Reservado') DEFAULT 'Activo'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('historial_lotes', function (Blueprint $table) {
            $table->dropForeign(['id_reserva']);
            $table->dropColumn('id_reserva');
            // Reverting id_venta to not null might fail if there are records with null, so we leave it nullable or just don't touch it.
        });
        
        DB::statement("ALTER TABLE historial_lotes MODIFY COLUMN estado ENUM('Activo', 'Rescindido') DEFAULT 'Activo'");
    }
};
