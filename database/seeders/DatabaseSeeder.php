<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Solo crea el admin inicial.
     * Los usuarios y contactos se crean dinámicamente desde la app.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@elysium.com')],
            [
                'name'       => env('ADMIN_NAME', 'Admin'),
                'username'   => env('ADMIN_USERNAME', 'admin'),
                'password'   => \Illuminate\Support\Facades\Hash::make(env('ADMIN_PASSWORD', 'changeme')),
                'is_admin'   => true,
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('✅ Admin creado. Credenciales en .env (ADMIN_EMAIL, ADMIN_PASSWORD)');
    }
}
