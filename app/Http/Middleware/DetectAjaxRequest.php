<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DetectAjaxRequest
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // ✅ Detect if this is an AJAX/fetch request
        $isAjax = $request->ajax() || 
                  $request->wantsJson() || 
                  $request->header('X-Requested-With') === 'XMLHttpRequest';

        $request->attributes->set('is_ajax', $isAjax);
        
        return $next($request);
    }
}
