<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Confiar en proxies (Nginx) para detectar HTTPS correctamente en producción
        $middleware->trustProxies(at: '*');

        // Usuarios autenticados van al chat, admins al dashboard
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo(function ($request) {
            $user = auth()->user();
            if ($user && $user->isAdmin()) {
                return route('admin.dashboard');
            }
            return route('chat.index');
        });
        
        // Registrar middleware en el grupo web
        $middleware->web(append: [
            \App\Http\Middleware\DetectAjaxRequest::class,
            \App\Http\Middleware\UpdateUserPresence::class,
        ]);
        
        // ✅ Registro de alias para middleware personalizado
        $middleware->alias([
            'isAdmin' => \App\Http\Middleware\IsAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();