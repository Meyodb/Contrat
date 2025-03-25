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
        
        // Si le nom du fichier suit le format 'XX_employee.png', on vérifie aussi les chemins avec l'ID
        if (preg_match('/^(\d+)_employee\.png$/', $filename, $matches)) {
            $userId = $matches[1];
            $paths = array_merge($paths, [
                'private/private/private/signatures/' . $filename,
                'private/public/signatures/' . $filename
            ]);
            
            // Logger les tentatives d'accès pour déboguer
            \Log::info('Tentative d\'accès à la signature', [
                'filename' => $filename,
                'userId' => $userId,
                'paths' => $paths
            ]);
        }
        
        // Chercher la signature dans les différents chemins
        foreach ($paths as $path) {
            if (Storage::exists($path)) {
                try {
                    $file = Storage::get($path);
                    $type = File::mimeType(storage_path('app/' . $path));
                    
                    return response($file, 200)->header('Content-Type', $type);
                } catch (\Exception $e) {
                    \Log::error('Erreur lors de l\'accès à la signature: ' . $e->getMessage(), [
                        'path' => $path,
                        'filename' => $filename
                    ]);
                    continue;
                }
            }
        }
        
        // Si aucune signature n'est trouvée, essayer de copier depuis un autre emplacement
        $this->tryToCopySignature($filename);
        
        // Vérifier à nouveau après la tentative de copie
        foreach ($paths as $path) {
            if (Storage::exists($path)) {
                try {
                    $file = Storage::get($path);
                    $type = File::mimeType(storage_path('app/' . $path));
                    
                    return response($file, 200)->header('Content-Type', $type);
                } catch (\Exception $e) {
                    \Log::error('Erreur après copie: ' . $e->getMessage(), [
                        'path' => $path,
                        'filename' => $filename
                    ]);
                    continue;
                }
            }
        }
        
        // Si aucune signature n'est trouvée, renvoyer une image par défaut ou une erreur
        if (file_exists(public_path('img/no-signature.png'))) {
            return response()->file(public_path('img/no-signature.png'));
        }
        
        return response()->file(public_path('img/default_admin_signature.png'));
    }
    
    /**
     * Tente de copier une signature depuis un autre emplacement
     */
    private function tryToCopySignature($filename)
    {
        // Vérifier s'il s'agit d'une signature d'employé
        if (preg_match('/^(\d+)_employee\.png$/', $filename, $matches)) {
            $userId = $matches[1];
            
            // Rechercher tous les fichiers possibles
            $possibleFiles = glob(storage_path('app/*/*/*/' . $filename));
            $possibleFiles = array_merge($possibleFiles, glob(storage_path('app/*/*/' . $filename)));
            $possibleFiles = array_merge($possibleFiles, glob(storage_path('app/*/' . $filename)));
            
            if (!empty($possibleFiles)) {
                // Créer les répertoires si nécessaire
                $directories = [
                    storage_path('app/public/signatures'),
                    storage_path('app/private/signatures'),
                    storage_path('app/private/private/signatures')
                ];
                
                foreach ($directories as $dir) {
                    if (!file_exists($dir)) {
                        mkdir($dir, 0755, true);
                    }
                }
                
                // Copier le fichier vers tous les emplacements possibles
                foreach ($directories as $dir) {
                    if (!file_exists($dir . '/' . $filename)) {
                        copy($possibleFiles[0], $dir . '/' . $filename);
                    }
                }
                
                \Log::info('Signature copiée avec succès', [
                    'filename' => $filename,
                    'source' => $possibleFiles[0],
                    'destinations' => $directories
                ]);
                
                return true;
            }
        }
        
        return false;
    }
} 