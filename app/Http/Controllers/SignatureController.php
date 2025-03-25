<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class SignatureController extends Controller
{
    /**
     * Affiche une signature depuis le stockage
     */
    public function showSignature($filename)
    {
        Log::info('Tentative d\'affichage de la signature: ' . $filename);
        
        // Vérifier s'il s'agit d'un chemin complet ou juste d'un nom de fichier
        if (strpos($filename, '/') !== false) {
            $filename = basename($filename);
            Log::info('Extraction du nom de fichier: ' . $filename);
        }
        
        // Essayer plusieurs chemins possibles
        $possiblePaths = [
            // Si le fichier est directement dans signatures/
            'public/signatures/' . $filename,
            // Si le fichier est dans signatures/ mais sans le préfixe public
            'signatures/' . $filename,
            // Si c'est le fichier de signature admin fixe
            'public/signatures/admin_signature.png',
            // Si le fichier est référencé par le chemin complet
            $filename,
        ];
        
        $path = null;
        
        // Log tous les chemins possibles pour le débogage
        Log::info('Chemins de recherche pour la signature:', $possiblePaths);
        
        // Vérifier également le chemin physique
        $publicPath = public_path('storage/signatures/' . $filename);
        Log::info('Vérification du chemin physique: ' . $publicPath);
        if (File::exists($publicPath)) {
            Log::info('Le fichier existe au chemin physique');
            return response()->file($publicPath);
        }
        
        foreach ($possiblePaths as $possiblePath) {
            if (Storage::exists($possiblePath)) {
                $path = $possiblePath;
                Log::info('Signature trouvée dans le chemin: ' . $path);
                break;
            }
        }
        
        if (!$path) {
            Log::error('Signature non trouvée dans aucun des chemins testés. Fichier recherché: ' . $filename);
            
            // Dernière tentative - vérifier si le fichier existe directement dans le système de fichiers
            $storagePath = Storage::path('public/signatures/' . $filename);
            Log::info('Tentative avec le chemin complet du système: ' . $storagePath);
            
            if (File::exists($storagePath)) {
                Log::info('Fichier trouvé via chemin système');
                return response()->file($storagePath);
            }
            
            abort(404, 'Signature non trouvée');
        }
        
        try {
            // Récupérer le contenu du fichier
            $file = Storage::get($path);
            $type = Storage::mimeType($path);
            
            // Retourner la réponse avec le bon type MIME
            return response($file, 200)->header('Content-Type', $type);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'affichage de la signature: ' . $e->getMessage());
            abort(500, 'Erreur lors de l\'affichage de la signature');
        }
    }
} 