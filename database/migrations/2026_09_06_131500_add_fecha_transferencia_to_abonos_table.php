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
        if (Schema::hasTable('abonos') && !Schema::hasColumn('abonos', 'fecha_transferencia')) {
            Schema::table('abonos', function (Blueprint $table) {
                $table->date('fecha_transferencia')->nullable()->after('cuenta_destino');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('abonos') && Schema::hasColumn('abonos', 'fecha_transferencia')) {
            Schema::table('abonos', function (Blueprint $table) {
                $table->dropColumn('fecha_transferencia');
            });
        }
    }
};
