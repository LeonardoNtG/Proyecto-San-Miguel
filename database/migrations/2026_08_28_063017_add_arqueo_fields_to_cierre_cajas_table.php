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
        Schema::table('cierre_cajas', function (Blueprint $table) {
            $table->decimal('efectivo_real', 10, 2)->nullable();
            $table->decimal('diferencia', 10, 2)->nullable();
            $table->text('comentario')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cierre_cajas', function (Blueprint $table) {
            $table->dropColumn(['efectivo_real', 'diferencia', 'comentario']);
        });
    }
};
