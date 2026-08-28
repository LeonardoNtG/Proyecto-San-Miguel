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
        Schema::table('bloques', function (Blueprint $table) {
            $table->dropUnique('bloques_nombre_unique');
            $table->unique(['nombre', 'lotificacion_id'], 'bloques_nombre_lotificacion_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bloques', function (Blueprint $table) {
            $table->dropUnique('bloques_nombre_lotificacion_unique');
            $table->unique('nombre', 'bloques_nombre_unique');
        });
    }
};
