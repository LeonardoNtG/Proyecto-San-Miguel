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
        Schema::table('abonos', function (Blueprint $table) {
            if (!Schema::hasColumn('abonos', 'recibo_firmado')) {
                $table->string('recibo_firmado')->nullable()->after('ruta_recibo');
            }
            if (!Schema::hasColumn('abonos', 'fecha_recibo_firmado')) {
                $table->dateTime('fecha_recibo_firmado')->nullable()->after('recibo_firmado');
            }
            if (!Schema::hasColumn('abonos', 'user_recibo_firmado_id')) {
                $table->unsignedBigInteger('user_recibo_firmado_id')->nullable()->after('fecha_recibo_firmado');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('abonos', function (Blueprint $table) {
            if (Schema::hasColumn('abonos', 'user_recibo_firmado_id')) {
                $table->dropColumn('user_recibo_firmado_id');
            }
            if (Schema::hasColumn('abonos', 'fecha_recibo_firmado')) {
                $table->dropColumn('fecha_recibo_firmado');
            }
            if (Schema::hasColumn('abonos', 'recibo_firmado')) {
                $table->dropColumn('recibo_firmado');
            }
        });
    }
};
