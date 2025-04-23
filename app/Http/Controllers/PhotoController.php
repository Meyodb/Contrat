<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class PhotoController extends Controller
{
    /**
     * Affiche une photo de profil
     */
    public function showProfilePhoto($filename)
    {
        $path = 'public/profile-photos/' . $filename;
        
        // Vérifier si le fichier existe
        if (!Storage::exists($path)) {
            Log::error('Photo non trouvée: ' . $path);
            abort(404, 'Photo non trouvée');
        }
        
        // Récupérer le contenu du fichier
        $file = Storage::get($path);
        $type = Storage::mimeType($path);
        
        // Retourner la réponse avec le bon type MIME
        return response($file, 200)->header('Content-Type', $type);
    }
}
