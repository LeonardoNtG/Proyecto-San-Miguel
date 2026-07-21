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
    public function up() : void
    {
        Schema::create('bloques', function (Blueprint $table) {
            // Clave primaria: id_bloque
            $table->increments('id_bloque'); 
            
            $table->string('nombre', 50)->unique();
            $table->string('descripcion')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() : void
    {
        Schema::dropIfExists('bloques');
    }
};
