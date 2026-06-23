<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo($request)
    {
        // Jangan redirect untuk API requests
        if ($request->is('api/*')) {
            return null;
        }

        // Redirect ke login untuk web requests jika route ada
        if (!$request->expectsJson()) {
            return route('login');
        }
    }
}
