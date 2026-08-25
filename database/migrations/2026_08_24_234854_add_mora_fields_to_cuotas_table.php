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
        Schema::table('cuotas', function (Blueprint $table) {
            $table->decimal('mora_calculada', 10, 2)->default(0)->after('saldo_restante');
            $table->decimal('mora_exonerada', 10, 2)->default(0)->after('mora_calculada');
            $table->decimal('mora_pagada', 10, 2)->default(0)->after('mora_exonerada');
        });
        
        // Add 'Mora' to the enum using raw SQL
        DB::statement("ALTER TABLE cuotas MODIFY COLUMN estado ENUM('Pendiente', 'Parcial', 'Pagada', 'Mora') DEFAULT 'Pendiente'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('cuotas', function (Blueprint $table) {
            $table->dropColumn(['mora_calculada', 'mora_exonerada', 'mora_pagada']);
        });
        
        DB::statement("ALTER TABLE cuotas MODIFY COLUMN estado ENUM('Pendiente', 'Parcial', 'Pagada') DEFAULT 'Pendiente'");
    }
};
