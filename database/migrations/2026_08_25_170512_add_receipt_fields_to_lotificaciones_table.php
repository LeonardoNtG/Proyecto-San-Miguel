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
        Schema::table('lotificaciones', function (Blueprint $table) {
            $table->string('logo')->nullable()->after('descripcion');
            $table->string('ruc')->nullable()->after('logo');
            $table->string('telefono')->nullable()->after('ruc');
            $table->string('ciudad')->nullable()->after('telefono');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lotificaciones', function (Blueprint $table) {
            $table->dropColumn(['logo', 'ruc', 'telefono', 'ciudad']);
        });
    }


};
