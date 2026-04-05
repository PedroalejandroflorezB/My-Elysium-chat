<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateUserPresence
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();
            
            if (!$user->last_seen_at || $user->last_seen_at->lt(now()->subSeconds(60))) {
                // Actualizar de forma silenciosa para evitar eventos de "updated" si no son necesarios
                $user->timestamps = false;
                $user->last_seen_at = \Illuminate\Support\Carbon::now();
                $user->save();
                $user->timestamps = true;
            }
        }
        
        return $next($request);
    }
}
