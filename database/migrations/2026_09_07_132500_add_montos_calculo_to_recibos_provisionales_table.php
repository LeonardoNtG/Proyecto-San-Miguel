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
        if (Schema::hasTable('recibos_provisionales')) {
            Schema::table('recibos_provisionales', function (Blueprint $table) {
                if (!Schema::hasColumn('recibos_provisionales', 'valor_total')) {
                    $table->decimal('valor_total', 12, 2)->nullable()->after('concepto');
                }
                if (!Schema::hasColumn('recibos_provisionales', 'total_abonado')) {
                    $table->decimal('total_abonado', 12, 2)->nullable()->after('valor_total');
                }
                if (!Schema::hasColumn('recibos_provisionales', 'saldo_pendiente')) {
                    $table->decimal('saldo_pendiente', 12, 2)->nullable()->after('total_abonado');
                }
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
        if (Schema::hasTable('recibos_provisionales')) {
            Schema::table('recibos_provisionales', function (Blueprint $table) {
                $table->dropColumn(['valor_total', 'total_abonado', 'saldo_pendiente']);
            });
        }
    }
};
