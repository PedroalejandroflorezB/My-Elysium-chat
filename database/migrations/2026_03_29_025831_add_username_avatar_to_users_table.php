<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
            $table->string('avatar')->nullable()->after('username');
        });

        // Poblar usernames para usuarios existentes
        $users = DB::table('users')->whereNull('username')->get();
        foreach ($users as $user) {
            $base = Str::slug($user->name, '_');
            $username = $base;
            $count = 1;
            while (DB::table('users')->where('username', $username)->exists()) {
                $username = $base . '_' . $count++;
            }
            DB::table('users')->where('id', $user->id)->update(['username' => $username]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'avatar']);
        });
    }
};
