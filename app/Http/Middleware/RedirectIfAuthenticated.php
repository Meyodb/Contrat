<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Response;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                // Rediriger vers la bonne page en fonction du rôle de l'utilisateur
                if (Auth::guard($guard)->user()->is_admin) {
                    return redirect()->route('admin.dashboard');
                }
                
                return redirect()->route('home');
            }
        }

        return $next($request);
    }
} 