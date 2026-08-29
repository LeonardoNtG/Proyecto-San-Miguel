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
            $table->unsignedBigInteger('lotificacion_id')->nullable()->after('user_id');
            $table->foreign('lotificacion_id')->references('id')->on('lotificaciones')->onDelete('cascade');
        });

        Schema::table('cierre_cajas', function (Blueprint $table) {
            $table->unsignedBigInteger('lotificacion_id')->nullable()->after('user_id');
            $table->foreign('lotificacion_id')->references('id')->on('lotificaciones')->onDelete('cascade');
        });

        Schema::table('salidas', function (Blueprint $table) {
            $table->unsignedBigInteger('lotificacion_id')->nullable()->after('user_id');
            $table->foreign('lotificacion_id')->references('id')->on('lotificaciones')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('salidas', function (Blueprint $table) {
            $table->dropForeign(['lotificacion_id']);
            $table->dropColumn('lotificacion_id');
        });

        Schema::table('cierre_cajas', function (Blueprint $table) {
            $table->dropForeign(['lotificacion_id']);
            $table->dropColumn('lotificacion_id');
        });

        Schema::table('apertura_cajas', function (Blueprint $table) {
            $table->dropForeign(['lotificacion_id']);
            $table->dropColumn('lotificacion_id');
        });
    }
};
