<?php

namespace App\Temp_Fixes;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Contract;
use App\Models\User;

class SignatureHelper
{
    /**
     * Chemins standards pour les signatures
     */
    protected $paths = [
        'admin' => [
            'storage' => 'public/signatures/admin/admin_signature.png',
            'legacy' => [
                'public/signatures/admin_signature.png',
                'public/signatures/admin.png'
            ]
        ],
        'employee' => [
            'storage' => 'public/signatures/employees/{id}.png',
            'legacy' => [
                'public/signatures/{id}_employee.png',
                'public/signatures/employee-{id}.png'
            ]
        ],
        'public' => 'signatures/{id}.png'
    ];
    
    /**
     * Prépare une signature pour l'inclusion dans un PDF
     * 
     * @param string $type Type de signature (admin ou employee)
     * @param int|null $userId L'ID de l'utilisateur (pour les signatures d'employés)
     * @param int|null $contractId L'ID du contrat (pour les anciennes signatures spécifiques à un contrat)
     * @return string|null Base64 du contenu de l'image
     */
    public function prepareSignatureForPdf($type = 'admin', $userId = null, $contractId = null)
    {
        Log::info('Recherche de signature', [
            'type' => $type,
            'user_id' => $userId,
            'contract_id' => $contractId
        ]);
        
        try {
            // Pour l'admin, utiliser directement le fichier fourni manuellement
            if ($type === 'admin') {
                $manualSignaturePath = storage_path('app/public/signatures/admin/admin_signature.png');
                
                if (file_exists($manualSignaturePath)) {
                    try {
                        $mime = mime_content_type($manualSignaturePath);
                        $data = file_get_contents($manualSignaturePath);
                        $base64Signature = 'data:' . $mime . ';base64,' . base64_encode($data);
                        
                        Log::info('Signature admin manuelle utilisée', [
                            'size' => strlen($data),
                            'path' => $manualSignaturePath
                        ]);
                        
                        return $base64Signature;
                    } catch (\Exception $e) {
                        Log::error('Erreur lors de la conversion de la signature admin manuelle', [
                            'error' => $e->getMessage(),
                            'path' => $manualSignaturePath
                        ]);
                    }
                }
            }
            
            // Pour les autres cas, continuer avec la logique existante
            // Chercher la signature dans les emplacements possibles
            $signaturePath = $this->findSignaturePath($type, $userId, $contractId);
            
            // Si aucune signature n'est trouvée, créer une signature par défaut
            if (!$signaturePath) {
                Log::warning('Aucune signature trouvée, création d\'une signature par défaut', [
                    'type' => $type,
                    'user_id' => $userId
                ]);
                
                // Créer une signature par défaut
                if ($type === 'admin') {
                    $signaturePath = $this->createAdminSignature();
                } else {
                    $signaturePath = $this->createDefaultSignature($userId);
                }
            }
            
            // Convertir l'image en base64
            if ($signaturePath && file_exists($signaturePath)) {
                try {
                    $mime = mime_content_type($signaturePath);
                    $data = file_get_contents($signaturePath);
                    $base64Signature = 'data:' . $mime . ';base64,' . base64_encode($data);
                    
                    Log::info('Signature convertie en base64', [
                        'size' => strlen($data),
                        'path' => $signaturePath
                    ]);
                    
                    return $base64Signature;
                } catch (\Exception $e) {
                    Log::error('Erreur lors de la conversion en base64', [
                        'error' => $e->getMessage(),
                        'path' => $signaturePath
                    ]);
                }
            }
            
            // Si toutes les tentatives échouent, générer une signature de secours
            return $this->generateFallbackSignature($type);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la préparation de la signature', [
                'error' => $e->getMessage()
            ]);
            
            return $this->generateFallbackSignature($type);
        }
    }
    
    /**
     * Trouve le chemin d'une signature
     * 
     * @param string $type Type de signature (admin ou employee)
     * @param int|null $userId L'ID de l'utilisateur (pour les signatures d'employés)
     * @param int|null $contractId L'ID du contrat (pour les anciennes signatures)
     * @return string|null Chemin vers le fichier de signature
     */
    public function findSignaturePath($type = 'admin', $userId = null, $contractId = null)
    {
        // Liste des chemins possibles pour la signature
        $potentialPaths = [];
        
        // Ajouter les chemins standards
        if ($type === 'admin') {
            // Chemins standards pour l'admin
            $potentialPaths[] = storage_path('app/' . $this->paths['admin']['storage']);
            foreach ($this->paths['admin']['legacy'] as $path) {
                $potentialPaths[] = storage_path('app/' . $path);
                $potentialPaths[] = public_path(str_replace('public/', '', $path));
            }
            
            // Ajout des chemins absolus
            $potentialPaths[] = public_path('signatures/admin_signature.png');
            $potentialPaths[] = public_path('signatures/admin/admin_signature.png');
        } else {
            // Chemins standards pour l'employé
            if ($userId) {
                $userPath = str_replace('{id}', $userId, $this->paths['employee']['storage']);
                $potentialPaths[] = storage_path('app/' . $userPath);
                
                foreach ($this->paths['employee']['legacy'] as $path) {
                    $userLegacyPath = str_replace('{id}', $userId, $path);
                    $potentialPaths[] = storage_path('app/' . $userLegacyPath);
                    $potentialPaths[] = public_path(str_replace('public/', '', $userLegacyPath));
                }
                
                // Ajout des chemins publics
                $potentialPaths[] = public_path('signatures/' . $userId . '.png');
                $potentialPaths[] = public_path('signatures/employees/' . $userId . '.png');
                $potentialPaths[] = public_path('signatures/' . $userId . '_employee.png');
            }
            
            // Ajouter les chemins de signature spécifiques au contrat
            if ($contractId) {
                $potentialPaths[] = storage_path('app/public/signatures/' . $contractId . '_' . $type . '.png');
                $potentialPaths[] = public_path('signatures/' . $contractId . '_' . $type . '.png');
            }
        }
        
        // Rechercher dans les chemins possibles
        foreach ($potentialPaths as $path) {
            if (file_exists($path)) {
                Log::info('Signature trouvée', [
                    'type' => $type,
                    'path' => $path
                ]);
                return $path;
            }
        }
        
        Log::warning('Aucune signature trouvée', [
            'type' => $type,
            'user_id' => $userId,
            'contract_id' => $contractId,
            'paths_checked' => count($potentialPaths)
        ]);
        
        return null;
    }

    /**
     * Génère une signature de secours en cas d'erreur
     * 
     * @param string $type Type de signature (admin ou employee)
     * @return string Base64 de l'image générée
     */
    protected function generateFallbackSignature($type = 'admin')
    {
        try {
            // Générer une signature simple
            $img = imagecreatetruecolor(300, 100);
            $white = imagecolorallocate($img, 255, 255, 255);
            $black = imagecolorallocate($img, 0, 0, 0);
            $gray = imagecolorallocate($img, 100, 100, 100);
            
            // Fond blanc
            imagefill($img, 0, 0, $white);
            
            // Texte
            $text = ($type === 'admin') ? "Signature Admin" : "Signature Employé";
            imagestring($img, 3, 50, 30, $text, $gray);
            
            // Ajouter une ligne manuscrite simple
            imagesetthickness($img, 2);
            imageline($img, 50, 60, 250, 60, $black);
            imageline($img, 50, 60, 100, 75, $black);
            imageline($img, 100, 75, 150, 45, $black);
            imageline($img, 150, 45, 200, 65, $black);
            imageline($img, 200, 65, 250, 60, $black);
            
            // Convertir en base64
            ob_start();
            imagepng($img);
            $imageData = ob_get_clean();
            imagedestroy($img);
            
            $base64Signature = 'data:image/png;base64,' . base64_encode($imageData);
            
            Log::info('Signature de secours générée', ['type' => $type]);
            
            return $base64Signature;
        } catch (\Exception $e) {
            // En cas d'erreur, retourner une image en base64 minimale
            return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';
        }
    }

    /**
     * Crée une signature administrative par défaut
     * 
     * @return string|null Chemin vers le fichier de signature créé
     */
    public function createAdminSignature()
    {
        try {
            // S'assurer que les répertoires existent
            $this->ensureDirectoriesExist();
            
            // Créer une image
            $width = 500;
            $height = 200;
            $img = imagecreatetruecolor($width, $height);
            
            // Couleurs
            $white = imagecolorallocate($img, 255, 255, 255);
            $blue = imagecolorallocate($img, 41, 128, 185);
            $darkBlue = imagecolorallocate($img, 52, 73, 94);
            
            // Fond blanc
            imagefill($img, 0, 0, $white);
            
            // Dessiner une signature stylisée
            $points = [];
            $pointCount = 100;
            
            // Générer des points pour une signature stylisée
            $centerY = $height / 2;
            $amplitude = $height / 4;
            $frequency = 0.05;
            
            for ($i = 0; $i < $pointCount; $i++) {
                $x = ($width / $pointCount) * $i;
                $y = $centerY + sin($x * $frequency) * $amplitude;
                $points[] = $x;
                $points[] = $y;
            }
            
            // Dessiner la courbe
            imagesetthickness($img, 3);
            imagesetstyle($img, [$blue, $blue, $blue, $darkBlue, $darkBlue, $darkBlue]);
            imageopenpolygon($img, $points, $pointCount, IMG_COLOR_STYLED);
            
            // Ajouter quelques accents à la signature
            imagesetthickness($img, 2);
            
            $accents = [
                [$width * 0.7, $centerY - 30, $width * 0.8, $centerY - 10],
                [$width * 0.2, $centerY + 20, $width * 0.3, $centerY + 40],
                [$width * 0.85, $centerY, $width * 0.95, $centerY - 20]
            ];
            
            foreach ($accents as $accent) {
                imageline($img, $accent[0], $accent[1], $accent[2], $accent[3], $darkBlue);
            }
            
            // Chemins où enregistrer la signature
            $storagePath = storage_path('app/' . $this->paths['admin']['storage']);
            $publicPath = public_path('signatures/admin_signature.png');
            
            // S'assurer que les répertoires existent
            $this->ensureDirectoryExists(dirname($storagePath));
            $this->ensureDirectoryExists(dirname($publicPath));
            
            // Enregistrer l'image
            imagepng($img, $storagePath);
            imagepng($img, $publicPath);
            imagedestroy($img);
            
            // Définir les permissions
            @chmod($storagePath, 0777);
            @chmod($publicPath, 0777);
            
            Log::info('Signature admin créée avec succès', [
                'storage_path' => $storagePath,
                'public_path' => $publicPath
            ]);
            
            return $storagePath;
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de la signature admin', [
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Répare les permissions des fichiers de signature
     */
    public function fixSignaturePermissions()
    {
        $stats = [
            'directories_fixed' => 0,
            'files_fixed' => 0,
        ];
        
        try {
            // Liste des répertoires à vérifier
            $directories = [
                storage_path('app/public/signatures'),
                storage_path('app/public/signatures/admin'),
                storage_path('app/public/signatures/employees'),
                public_path('signatures'),
            ];
            
            // Créer/réparer les répertoires
            foreach ($directories as $dir) {
                if (!file_exists($dir)) {
                    if (mkdir($dir, 0777, true)) {
                        $stats['directories_fixed']++;
                        Log::info('Permissions fixées pour le répertoire', ['dir' => $dir, 'permissions' => '777']);
                    }
                } else {
                    if (@chmod($dir, 0777)) {
                        $stats['directories_fixed']++;
                    }
                }
            }
            
            // Créer une signature admin par défaut si elle n'existe pas
            $adminSignaturePath = storage_path('app/public/signatures/admin/admin_signature.png');
            
            if (!file_exists($adminSignaturePath)) {
                $this->createAdminSignature();
                
                // Appliquer les permissions
                if (file_exists($adminSignaturePath)) {
                    @chmod($adminSignaturePath, 0777);
                    $stats['files_fixed']++;
                }
                
                Log::info('Signature admin créée et permissions fixées', ['file' => $adminSignaturePath]);
            }
            
            // Appliquer les permissions sur les fichiers de signature existants
            $signatureFiles = array_merge(
                glob(storage_path('app/public/signatures/admin/*.png')),
                glob(storage_path('app/public/signatures/employees/*.png')),
                glob(public_path('signatures/*.png'))
            );
            
            // Fixer les permissions pour chaque fichier
            foreach ($signatureFiles as $file) {
                if (file_exists($file) && @chmod($file, 0777)) {
                    $stats['files_fixed']++;
                    Log::info('Permissions fixées pour le fichier', ['file' => $file, 'permissions' => '777']);
                }
            }
            
            return $stats;
        } catch (\Exception $e) {
            Log::error('Erreur lors de la correction des permissions', [
                'error' => $e->getMessage()
            ]);
            
            return $stats;
        }
    }

    /**
     * S'assure qu'un répertoire existe
     * 
     * @param string $path Chemin du répertoire
     * @return bool
     */
    protected function ensureDirectoryExists($path)
    {
        if (!file_exists($path)) {
            return mkdir($path, 0777, true);
        }
        
        return true;
    }

    /**
     * S'assure que tous les répertoires nécessaires existent
     */
    protected function ensureDirectoriesExist()
    {
        $directories = [
            storage_path('app/public'),
            storage_path('app/public/signatures'),
            storage_path('app/public/signatures/admin'),
            storage_path('app/public/signatures/employees'),
            public_path('signatures')
        ];
        
        foreach ($directories as $dir) {
            $this->ensureDirectoryExists($dir);
        }
    }
    
    /**
     * Crée une signature par défaut pour un employé
     * 
     * @param int|null $userId ID de l'utilisateur
     * @return string|null Chemin vers le fichier de signature créé
     */
    public function createDefaultSignature($userId = null)
    {
        try {
            // S'assurer que les répertoires existent
            $this->ensureDirectoriesExist();
            
            // Créer une image 300x100 pixels (plus adaptée à une signature)
            $img = imagecreatetruecolor(300, 100);
            
            // Définir les couleurs
            $background = imagecolorallocate($img, 255, 255, 255); // Blanc
            $textcolor = imagecolorallocate($img, 50, 50, 50);     // Gris foncé
            
            // Remplir le fond
            imagefilledrectangle($img, 0, 0, 300, 100, $background);
            
            // Récupérer le nom de l'utilisateur si possible
            $userName = "Signature";
            if ($userId) {
                $user = \App\Models\User::find($userId);
                if ($user) {
                    $userName = $user->name;
                }
            }
            
            // Ajouter un texte de signature
            imagestring($img, 3, 50, 30, $userName, $textcolor);
            
            // Ajouter une ligne représentant une signature manuscrite
            imagesetthickness($img, 2);
            
            // Dessiner une ligne de signature plus élaborée
            imageline($img, 50, 60, 250, 60, $textcolor);
            imageline($img, 50, 60, 100, 80, $textcolor);
            imageline($img, 100, 80, 150, 40, $textcolor);
            imageline($img, 150, 40, 200, 70, $textcolor);
            imageline($img, 200, 70, 250, 60, $textcolor);
            
            // Définir les chemins pour sauvegarder l'image
            $fileBasename = $userId ? $userId : 'default';
            $storagePath = storage_path('app/public/signatures/employees/' . $fileBasename . '.png');
            $publicPath = public_path('signatures/' . $fileBasename . '.png');
            
            // S'assurer que les répertoires existent
            $this->ensureDirectoryExists(dirname($storagePath));
            $this->ensureDirectoryExists(dirname($publicPath));
            
            // Sauvegarder l'image
            imagepng($img, $storagePath);
            imagepng($img, $publicPath);
            imagedestroy($img);
            
            // Définir les permissions
            @chmod($storagePath, 0777);
            @chmod($publicPath, 0777);
            
            Log::info('Signature par défaut pour employé créée avec succès', [
                'user_id' => $userId,
                'storage_path' => $storagePath,
                'public_path' => $publicPath
            ]);
            
            return $storagePath;
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de la signature par défaut', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
            
            return null;
        }
    }

    /**
     * Sauvegarde une signature depuis des données base64
     * 
     * @param string $signatureData Données de signature en base64
     * @param string $type Type de signature (admin ou employee)
     * @param int|null $userId ID de l'utilisateur
     * @return string|null Chemin vers le fichier de signature sauvegardé
     */
    public function saveSignature($signatureData, $type = 'employee', $userId = null)
    {
        try {
            // S'assurer que les répertoires existent
            $this->ensureDirectoriesExist();
            
            // Extraire les données base64
            $matches = [];
            if (preg_match('/^data:image\/\w+;base64,(.+)$/', $signatureData, $matches)) {
                $imageData = base64_decode($matches[1]);
                
                // Définir les chemins de sauvegarde selon le type
                if ($type === 'admin') {
                    $storagePath = storage_path('app/' . $this->paths['admin']['storage']);
                    $publicPath = public_path('signatures/admin_signature.png');
                } else {
                    // Pour les employés, utiliser l'ID dans le nom de fichier
                    $fileId = $userId ? $userId : 'default';
                    $storagePath = storage_path('app/' . str_replace('{id}', $fileId, $this->paths['employee']['storage']));
                    
                    // Également sauvegarder dans les chemins legacy pour compatibilité
                    $legacyPaths = [];
                    foreach ($this->paths['employee']['legacy'] as $legacyPath) {
                        $legacyPaths[] = storage_path('app/' . str_replace('{id}', $fileId, $legacyPath));
                    }
                    
                    // Chemin public pour les signatures d'employés
                    $publicPath = public_path('signatures/' . $fileId . '.png');
                }
                
                // S'assurer que les répertoires existent
                $this->ensureDirectoryExists(dirname($storagePath));
                $this->ensureDirectoryExists(dirname($publicPath));
                
                // Sauvegarder l'image
                file_put_contents($storagePath, $imageData);
                file_put_contents($publicPath, $imageData);
                
                // Pour les employés, aussi sauvegarder dans les chemins legacy
                if ($type === 'employee' && isset($legacyPaths) && is_array($legacyPaths)) {
                    foreach ($legacyPaths as $path) {
                        $this->ensureDirectoryExists(dirname($path));
                        file_put_contents($path, $imageData);
                        @chmod($path, 0777);
                    }
                }
                
                // Définir les permissions
                @chmod($storagePath, 0777);
                @chmod($publicPath, 0777);
                
                Log::info('Signature sauvegardée avec succès', [
                    'type' => $type,
                    'user_id' => $userId,
                    'storage_path' => $storagePath,
                    'size' => strlen($imageData)
                ]);
                
                return $storagePath;
            } else {
                Log::error('Format de signature invalide', [
                    'data_prefix' => substr($signatureData, 0, 30)
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de la sauvegarde de la signature', [
                'error' => $e->getMessage(),
                'type' => $type,
                'user_id' => $userId
            ]);
            
            return null;
        }
    }
} 