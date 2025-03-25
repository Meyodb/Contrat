<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Vérifier si l'utilisateur est connecté et est un administrateur
        if (Auth::check() && Auth::user()->is_admin) {
            return $next($request);
        }

        // Renvoyer une erreur 403 au lieu de rediriger
        abort(403, 'Accès non autorisé. Cette section est réservée aux administrateurs.');
    }
}
