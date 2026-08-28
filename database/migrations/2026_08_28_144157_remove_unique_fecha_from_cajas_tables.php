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
        Schema::table('apertura_cajas', function (Blueprint $table) {
            $table->dropUnique(['fecha']);
        });

        Schema::table('cierre_cajas', function (Blueprint $table) {
            $table->dropUnique(['fecha']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('apertura_cajas', function (Blueprint $table) {
            $table->unique('fecha');
        });

        Schema::table('cierre_cajas', function (Blueprint $table) {
            $table->unique('fecha');
        });
    }
};
