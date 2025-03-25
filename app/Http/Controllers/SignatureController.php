<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class SignatureController extends Controller
{
    /**
     * Affiche une signature à partir de son nom de fichier
     * 
     * @param string $filename Nom du fichier de signature
     * @return \Illuminate\Http\Response
     */
    public function showSignature($filename)
    {
        // Chemins possibles pour les signatures (par ordre de priorité)
        $paths = [
            'private/private/signatures/' . $filename,
            'private/signatures/' . $filename,
            'public/signatures/' . $filename,
            'signatures/' . $filename
        ];
        
        // Chercher la signature dans les différents chemins
        foreach ($paths as $path) {
            if (Storage::exists($path)) {
                $file = Storage::get($path);
                $type = File::mimeType(storage_path('app/' . $path));
                
                return response($file, 200)->header('Content-Type', $type);
            }
        }
        
        // Si aucune signature n'est trouvée, renvoyer une image par défaut ou une erreur
        return response()->file(public_path('img/no-signature.png'));
    }
} 