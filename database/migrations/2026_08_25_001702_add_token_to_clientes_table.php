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
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('token_seguimiento', 64)->unique()->nullable()->after('oficio');
        });

        // Generar tokens para los clientes existentes
        $clientes = \App\Models\Cliente::all();
        foreach ($clientes as $cliente) {
            $cliente->token_seguimiento = \Illuminate\Support\Str::uuid()->toString();
            $cliente->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('token_seguimiento');
        });
    }
};
