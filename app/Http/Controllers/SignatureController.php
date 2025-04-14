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
        // Logger l'accès pour faciliter le débogage
        \Log::info('Demande d\'accès à une signature', [
            'filename' => $filename,
            'url' => request()->fullUrl()
        ]);
        
        // Chemins possibles pour les signatures (par ordre de priorité)
        $paths = [
            'public/signatures/' . $filename,
            'private/signatures/' . $filename,
            'signatures/' . $filename
        ];
        
        // Si le nom du fichier suit le format 'XX_employee.png', on vérifie aussi les chemins avec l'ID
        if (preg_match('/^(\d+)_employee\.png$/', $filename, $matches)) {
            $userId = $matches[1];
            
            // Logger les tentatives d'accès pour déboguer
            \Log::info('Signature employé demandée', [
                'filename' => $filename,
                'userId' => $userId
            ]);
        }
        
        // Cas spécial pour la signature admin
        if ($filename === 'admin_signature.png') {
            \Log::info('Signature admin demandée');
            
            // Si la signature n'existe pas, la créer
            if (!Storage::exists('public/signatures/admin_signature.png')) {
                \Log::info('Création de la signature admin car non trouvée');
                $this->createAdminSignature();
            }
        }
        
        // Chercher la signature dans les différents chemins
        foreach ($paths as $path) {
            if (Storage::exists($path)) {
                \Log::info('Signature trouvée', ['path' => $path]);
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
        
        \Log::warning('Signature non trouvée dans les chemins standards', [
            'filename' => $filename,
            'paths_checked' => $paths
        ]);
        
        // Si aucune signature n'est trouvée, essayer de copier depuis un autre emplacement
        $success = $this->tryToCopySignature($filename);
        
        if ($success) {
            \Log::info('Signature copiée avec succès, nouvelle tentative d\'accès');
            
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
        }
        
        // Si c'est une signature admin, essayer de la créer
        if ($filename === 'admin_signature.png') {
            \Log::info('Création d\'une signature admin par défaut');
            $this->createAdminSignature();
            
            if (Storage::exists('public/signatures/admin_signature.png')) {
                $file = Storage::get('public/signatures/admin_signature.png');
                return response($file, 200)->header('Content-Type', 'image/png');
            }
        }
        
        // Si aucune signature n'est trouvée, renvoyer une image par défaut
        \Log::warning('Aucune signature trouvée, utilisation d\'une image par défaut');
        
        // Créer une image de signature simple
        $img = imagecreatetruecolor(300, 100);
        $background = imagecolorallocate($img, 255, 255, 255);
        $textcolor = imagecolorallocate($img, 0, 0, 0);
        
        // Fond blanc
        imagefilledrectangle($img, 0, 0, 300, 100, $background);
        
        // Message "Signature non disponible"
        imagestring($img, 5, 50, 40, "Signature non disponible", $textcolor);
        
        // Renvoyer l'image générée
        ob_start();
        imagepng($img);
        $imageContent = ob_get_clean();
        imagedestroy($img);
        
        return response($imageContent, 200)->header('Content-Type', 'image/png');
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
    
    /**
     * Crée une signature admin par défaut
     */
    private function createAdminSignature()
    {
        \Log::info('Début de la création de la signature administrateur');
        
        // Créer les répertoires nécessaires avec les bonnes permissions
        $directories = [
            storage_path('app/public/signatures'),
            storage_path('app/private/signatures')
        ];
        
        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                if (!mkdir($dir, 0777, true)) {
                    \Log::error('Impossible de créer le répertoire: ' . $dir);
                    continue;
                }
                chmod($dir, 0777);
                \Log::info('Répertoire créé: ' . $dir . ' avec permissions 0777');
            }
        }
        
        // Créer une image de signature plus grande et plus visible
        $img = imagecreatetruecolor(600, 200);
        if (!$img) {
            \Log::error('Impossible de créer l\'image de signature');
            return false;
        }
        
        $background = imagecolorallocate($img, 255, 255, 255);
        $textcolor = imagecolorallocate($img, 0, 0, 0);
        
        // Fond blanc
        imagefilledrectangle($img, 0, 0, 600, 200, $background);
        
        // Dessiner une signature stylisée plus visible
        $penWidth = 5; // Épaisseur du trait plus importante
        
        // Dessiner plusieurs lignes pour créer une signature épaisse visible
        for ($i = 0; $i < $penWidth; $i++) {
            // Première partie - arc signature
            imagearc($img, 100 + $i, 100, 150, 80, 180, 270, $textcolor);
            imageline($img, 100 + $i, 100, 200 + $i, 100, $textcolor);
            
            // Deuxième partie - boucle
            imagearc($img, 250 + $i, 90, 100, 60, 0, 350, $textcolor);
            
            // Troisième partie - ligne finale
            imageline($img, 300 + $i, 110, 450 + $i, 80, $textcolor);
        }
        
        // Ajouter des points d'accentuation
        imagefilledellipse($img, 80, 100, 10, 10, $textcolor);
        imagefilledellipse($img, 450, 80, 10, 10, $textcolor);
        
        // Ajouter une ligne horizontale sous la signature
        imagesetthickness($img, 3);
        imageline($img, 80, 150, 450, 150, $textcolor);
        
        // Sauvegarder l'image dans un buffer
        ob_start();
        imagepng($img);
        $signatureContent = ob_get_clean();
        imagedestroy($img);
        
        if (empty($signatureContent)) {
            \Log::error('Contenu de la signature vide après génération');
            return false;
        }
        
        \Log::info('Image de signature générée, taille: ' . strlen($signatureContent) . ' octets');
        
        // Sauvegarder le fichier directement dans le système de fichiers
        $publicPath = storage_path('app/public/signatures/admin_signature.png');
        $privatePath = storage_path('app/private/signatures/admin_signature.png');
        
        $publicSuccess = file_put_contents($publicPath, $signatureContent);
        $privateSuccess = file_put_contents($privatePath, $signatureContent);
        
        // S'assurer que les fichiers ont les bonnes permissions
        if ($publicSuccess) {
            chmod($publicPath, 0777);
            \Log::info('Signature admin enregistrée (méthode directe) dans: ' . $publicPath);
            \Log::info('Permissions mises à jour: ' . substr(sprintf('%o', fileperms($publicPath)), -4));
        } else {
            \Log::error('Échec de l\'enregistrement de la signature admin dans: ' . $publicPath);
        }
        
        if ($privateSuccess) {
            chmod($privatePath, 0777);
            \Log::info('Signature admin enregistrée (méthode directe) dans: ' . $privatePath);
        }
        
        // Essayer aussi avec l'API Storage de Laravel comme alternative
        $publicStorageSuccess = Storage::put('public/signatures/admin_signature.png', $signatureContent);
        $privateStorageSuccess = Storage::put('private/signatures/admin_signature.png', $signatureContent);
        
        \Log::info('Résultat de l\'enregistrement via Storage: public=' . ($publicStorageSuccess ? 'OK' : 'ÉCHEC') . 
                   ', private=' . ($privateStorageSuccess ? 'OK' : 'ÉCHEC'));
        
        $fileExists = file_exists($publicPath);
        $fileSize = $fileExists ? filesize($publicPath) : 0;
        \Log::info('Vérification finale: fichier existe=' . ($fileExists ? 'OUI' : 'NON') . ', taille=' . $fileSize . ' octets');
         
        return $publicSuccess || $publicStorageSuccess;
    }
} 