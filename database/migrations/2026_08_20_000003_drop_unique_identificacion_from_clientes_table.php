<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Una misma persona (cédula) puede tener varios contratos/ventas,
     * por lo que 'identificacion' ya no debe ser única en 'clientes'.
     *
     * @return void
     */
    public function up()
    {
        if ($this->hasUniqueIndex('clientes', 'clientes_identificacion_unique')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->dropUnique(['identificacion']);
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
        if (!$this->hasUniqueIndex('clientes', 'clientes_identificacion_unique')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->unique('identificacion');
            });
        }
    }

    private function hasUniqueIndex(string $table, string $indexName): bool
    {
        $database = DB::getDatabaseName();

        $result = DB::selectOne(
            'SELECT COUNT(*) AS total FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $indexName]
        );

        return $result->total > 0;
    }
};
