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
        // 1. Agregar last_seen_at a users
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_seen_at')->nullable()->after('avatar');
        });

        // 2. Crear p2p_signals para polling de WebRTC
        Schema::create('p2p_signals', function (Blueprint $table) {
            $table->id();
            $table->string('from_id'); // ID del peer o user emisor
            $table->string('to_id');   // ID del peer o user receptor
            $table->string('type');    // offer, answer, candidate
            $table->json('data');      // contenido de la señal
            $table->timestamp('read_at')->nullable(); // para saber si ya se procesó por polling
            $table->timestamps();
            
            // Índice para acelerar consultas de polling
            $table->index(['to_id', 'read_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('last_seen_at');
        });
        Schema::dropIfExists('p2p_signals');
    }
};
