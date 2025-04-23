<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UpdatePhotoUrls
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Vérifier si l'utilisateur est connecté
        if (Auth::check()) {
            $user = Auth::user();
            
            // Si l'utilisateur a une photo de profil
            if ($user->profile_photo_path) {
                // Corriger les problèmes courants dans les chemins de photos
                
                // 1. Enlever 'public/' si présent
                if (strpos($user->profile_photo_path, 'public/') === 0) {
                    $user->profile_photo_path = str_replace('public/', '', $user->profile_photo_path);
                    $user->save();
                    \Log::info('Middleware: Correction du chemin de photo utilisateur #' . $user->id . ' - suppression de "public/"');
                }
                
                // 2. Vérifier si le fichier existe dans storage
                $storagePath = storage_path('app/public/' . $user->profile_photo_path);
                if (!file_exists($storagePath) && strpos($user->profile_photo_path, 'profile-photos/') === 0) {
                    // Vérifier s'il existe dans le dossier public/photos
                    $filename = basename($user->profile_photo_path);
                    $publicPath = public_path('photos/' . $filename);
                    
                    if (file_exists($publicPath)) {
                        $user->profile_photo_path = 'photos/' . $filename;
                        $user->save();
                        \Log::info('Middleware: Correction du chemin de photo utilisateur #' . $user->id . ' - déplacement vers "photos/"');
                    }
                }
            }
        }
        
        return $next($request);
    }
} 