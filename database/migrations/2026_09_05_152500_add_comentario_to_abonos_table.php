<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('abonos', function (Blueprint $table) {
            if (!Schema::hasColumn('abonos', 'comentario')) {
                $table->text('comentario')->nullable()->after('cuenta_destino');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('abonos', function (Blueprint $table) {
            if (Schema::hasColumn('abonos', 'comentario')) {
                $table->dropColumn('comentario');
            }
        });
    }
};
