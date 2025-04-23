<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware('web')
            ->group(base_path('routes/web.php'));
            
        // Ajouter un middleware commun pour synchroniser les photos de profil
        Route::matched(function ($route, $request) {
            if (auth()->check() && !session()->has('photos_synced_login')) {
                $user = auth()->user();
                
                // Chercher une photo d'identité dans les contrats de l'utilisateur
                $contractData = $user->contracts()
                    ->with('data')
                    ->whereHas('data', function ($query) {
                        $query->whereNotNull('photo_path');
                    })
                    ->first();
                    
                if ($contractData && $contractData->data && $contractData->data->photo_path) {
                    // Toujours utiliser la photo d'identité, même si l'utilisateur a déjà une photo de profil
                    if ($user->profile_photo_path !== $contractData->data->photo_path) {
                        $user->update([
                            'profile_photo_path' => $contractData->data->photo_path
                        ]);
                        \Log::info('Photo de profil automatiquement synchronisée pour: ' . $user->name);
                    }
                }
                
                // Marquer comme synchronisé pour cette session d'utilisateur
                session(['photos_synced_login' => true]);
            }
        });
    }
} 