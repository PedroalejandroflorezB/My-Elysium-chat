<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Str;

class FixUsernames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:fix-usernames';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Asigna usernames a usuarios que no los tienen';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Buscando usuarios sin username...');
        
        $usersWithoutUsername = User::whereNull('username')->get();
        
        if ($usersWithoutUsername->isEmpty()) {
            $this->info('✅ Todos los usuarios ya tienen username asignado.');
            return 0;
        }
        
        $this->info("Encontrados {$usersWithoutUsername->count()} usuarios sin username.");
        
        $bar = $this->output->createProgressBar($usersWithoutUsername->count());
        $bar->start();
        
        foreach ($usersWithoutUsername as $user) {
            // Generar username único basado en el nombre
            $baseUsername = Str::slug($user->name, '_');
            $username = $baseUsername;
            $count = 1;
            
            while (User::where('username', $username)->exists()) {
                $username = $baseUsername . '_' . $count++;
            }
            
            $user->update(['username' => $username]);
            
            $this->line("  Usuario '{$user->name}' → username: '{$username}'");
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info("✅ {$usersWithoutUsername->count()} usernames asignados correctamente.");
        
        return 0;
    }
}
