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
        Schema::table('abonos', function (Blueprint $table) {
            if (!Schema::hasColumn('abonos', 'grupo_recibo')) {
                $table->string('grupo_recibo', 64)->nullable()->index()->after('codigo_recibo');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('abonos', function (Blueprint $table) {
            if (Schema::hasColumn('abonos', 'grupo_recibo')) {
                $table->dropColumn('grupo_recibo');
            }
        });
    }
};
