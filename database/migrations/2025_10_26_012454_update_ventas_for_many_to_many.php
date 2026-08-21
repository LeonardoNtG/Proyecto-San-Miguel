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
        if (Schema::hasColumn('ventas', 'id_lote')) {
            Schema::table('ventas', function (Blueprint $table) {
                $table->dropForeign(['id_lote']);
                $table->dropUnique(['id_lote']);
                $table->dropColumn('id_lote');
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
        if (!Schema::hasColumn('ventas', 'id_lote')) {
            Schema::table('ventas', function (Blueprint $table) {
                $table->unsignedInteger('id_lote')->unique();
                $table->foreign('id_lote')->references('id_lote')->on('lotes');
            });
        }
    }
};
