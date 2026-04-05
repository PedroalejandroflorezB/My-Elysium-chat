<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpiar usuarios existentes (opcional, comenta si no quieres borrar)
        // DB::table('users')->truncate();

        // Crear usuarios de prueba
        $users = [
            [
                'name' => 'Admin Elysium',
                'email' => 'admin@elysium.local',
                'password' => Hash::make('admin987'),
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Usuario Prueba 1',
                'email' => 'usuario1@test.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Usuario Prueba 2',
                'email' => 'usuario2@test.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Insertar usuarios (ignorar si ya existen por email único)
        foreach ($users as $user) {
            DB::table('users')->updateOrInsert(
                ['email' => $user['email']],
                $user
            );
        }

        $this->command->info('✅ Usuarios de prueba creados exitosamente');
        $this->command->table(
            ['Email', 'Contraseña', 'Rol'],
            [
                ['admin@elysium.local', 'admin987', 'Admin'],
                ['usuario1@test.com', 'password', 'Usuario'],
                ['usuario2@test.com', 'password', 'Usuario'],
            ]
        );
    }
}