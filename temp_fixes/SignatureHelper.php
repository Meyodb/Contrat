<?php

namespace App\Temp_Fixes;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class SignatureHelper
{
    /**
     * Prépare une signature pour inclusion dans un PDF
     * 
     * @param string|null $signatureType Type de signature (admin ou employee)
     * @param string|null $signaturePath Chemin de signature depuis la base de données
     * @param int|null $userId ID de l'utilisateur (pour les signatures d'employés)
     * @param int|null $contractId ID du contrat (pour les signatures spécifiques)
     * @return string Base64 encoded image content
     */
    public static function prepareSignatureForPdf($signatureType = 'admin', $signaturePath = null, $userId = null, $contractId = null)
    {
        $base64Content = '';
        $tempDir = public_path('temp_signatures');
        
        // Assurer que le répertoire temporaire existe
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }
        
        // Noms de fichiers standardisés
        $defaultFileName = ($signatureType === 'admin') ? 'admin_signature.png' : 'employee_signature.png';
        $userFileName = ($userId) ? $userId . '_employee.png' : null;
        $contractFileName = ($contractId) ? $contractId . '_' . $signatureType . '.png' : null;
        
        // Liste des emplacements potentiels à vérifier
        $potentialPaths = [];
        
        // 1. Vérifier le chemin fourni
        if ($signaturePath) {
            // Si le chemin n'inclut pas le dossier signatures/, l'ajouter
            if (strpos($signaturePath, 'signatures/') === false) {
                $signaturePath = 'signatures/' . $signaturePath;
            }
            
            $potentialPaths[] = 'public/' . $signaturePath;
            $potentialPaths[] = 'private/' . $signaturePath;
            $potentialPaths[] = $signaturePath;
        }
        
        // 2. Vérifier les emplacements standardisés par type
        $potentialPaths[] = 'public/signatures/' . $defaultFileName;
        $potentialPaths[] = 'private/signatures/' . $defaultFileName;
        
        // 3. Vérifier les emplacements par ID d'utilisateur
        if ($userFileName) {
            $potentialPaths[] = 'public/signatures/' . $userFileName;
            $potentialPaths[] = 'private/signatures/' . $userFileName;
        }
        
        // 4. Vérifier les emplacements par ID de contrat
        if ($contractFileName) {
            $potentialPaths[] = 'public/signatures/' . $contractFileName;
            $potentialPaths[] = 'private/signatures/' . $contractFileName;
        }
        
        // Logger les chemins à vérifier
        Log::info("Recherche de signature ($signatureType)", [
            'paths_to_check' => $potentialPaths
        ]);
        
        // Parcourir tous les chemins potentiels
        foreach ($potentialPaths as $path) {
            if (Storage::exists($path)) {
                try {
                    // Tenter de lire le contenu
                    $content = Storage::get($path);
                    if ($content) {
                        // Créer une copie temporaire pour le PDF
                        $tempFileName = $signatureType . '_' . time() . '.png';
                        $tempFilePath = $tempDir . '/' . $tempFileName;
                        
                        file_put_contents($tempFilePath, $content);
                        chmod($tempFilePath, 0777);
                        
                        // Encoder le contenu en base64
                        $base64Content = base64_encode($content);
                        
                        Log::info("Signature trouvée et encodée ($signatureType)", [
                            'path' => $path,
                            'temp_file' => $tempFilePath,
                            'base64_length' => strlen($base64Content)
                        ]);
                        
                        // Si on trouve une signature, on arrête la recherche
                        return $base64Content;
                    }
                } catch (\Exception $e) {
                    Log::error("Erreur lors de la lecture de la signature ($signatureType)", [
                        'path' => $path,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
        
        // Si on arrive ici, on n'a pas trouvé de signature valide
        // Créer une signature par défaut
        Log::warning("Aucune signature trouvée, création d'une signature par défaut ($signatureType)");
        $base64Content = self::generateDefaultSignature($signatureType);
        
        return $base64Content;
    }
    
    /**
     * Génère une signature par défaut
     * 
     * @param string $signatureType Type de signature (admin ou employee)
     * @return string Base64 encoded image content
     */
    private static function generateDefaultSignature($signatureType)
    {
        // Créer une image pour la signature
        $img = imagecreatetruecolor(300, 100);
        $background = imagecolorallocate($img, 255, 255, 255);
        $textcolor = imagecolorallocate($img, 0, 0, 0);
        
        // Fond blanc
        imagefilledrectangle($img, 0, 0, 300, 100, $background);
        
        // Dessiner une ligne simple pour simuler une signature
        $text = ($signatureType === 'admin') ? "Signature administrateur" : "Signature employé";
        imagestring($img, 3, 50, 40, $text, $textcolor);
        
        // Dessiner une ligne horizontale
        imageline($img, 50, 60, 250, 60, $textcolor);
        
        // Convertir l'image en base64
        ob_start();
        imagepng($img);
        $content = ob_get_clean();
        imagedestroy($img);
        
        // Créer un fichier temporaire avec cette signature
        $tempDir = public_path('temp_signatures');
        $tempFileName = $signatureType . '_default_' . time() . '.png';
        $tempFilePath = $tempDir . '/' . $tempFileName;
        
        file_put_contents($tempFilePath, $content);
        chmod($tempFilePath, 0777);
        
        // Également sauvegarder dans storage pour utilisation future
        Storage::put('public/signatures/' . ($signatureType === 'admin' ? 'admin' : 'employee') . '_signature.png', $content);
        
        return base64_encode($content);
    }
} 