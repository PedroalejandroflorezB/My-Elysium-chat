<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Como la tabla está vacía (confirmado), lo más seguro es recrear la columna id
        // para asegurar que sea BIGINT UNSIGNED AUTO_INCREMENT
        
        Schema::table('messages', function (Blueprint $table) {
            // Intentar eliminar la PK y la columna si existen como char(36)
            try {
                $table->dropColumn('id');
            } catch (\Exception $e) {
                // Si falla, es que no existe o ya se borró
            }
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->id()->first();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No es necesario revertir a char(36) ya que era un estado erróneo
    }
};
