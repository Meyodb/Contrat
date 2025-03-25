<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EmployeeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Vérifier si l'utilisateur est connecté et n'est pas un administrateur
        if (Auth::check() && !Auth::user()->is_admin) {
            return $next($request);
        }
        
        // Rediriger vers la page d'accueil si l'utilisateur est un administrateur
        if (Auth::check() && Auth::user()->is_admin) {
            return redirect()->route('admin.dashboard');
        }
        
        // Rediriger vers la page de connexion si l'utilisateur n'est pas connecté
        return redirect()->route('login');
    }
} 