<?php

namespace App\Temp_Fixes;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class SignatureHelper
{
    /**
     * Prépare une signature pour l'inclusion dans un PDF
     * 
     * @param string $filename Le nom du fichier à chercher
     * @param int|null $userId L'ID de l'utilisateur (pour les signatures d'employés)
     * @param int|null $contractId L'ID du contrat (pour les anciennes signatures spécifiques à un contrat)
     * @return string|null Base64 du contenu de l'image
     */
    public function prepareSignatureForPdf($filename, $userId = null, $contractId = null)
    {
        // Initialiser les variables
        $signatureContent = null;
        $paths = [];
        
        // Si c'est une signature d'employé avec user_id
        if ($userId && $filename !== 'admin_signature.png') {
            // Nouvelles signatures (user_id)
            $paths[] = storage_path('app/public/signatures/' . $userId . '_employee.png');
            $paths[] = storage_path('app/private/signatures/' . $userId . '_employee.png');
            $paths[] = storage_path('app/private/private/signatures/' . $userId . '_employee.png');
            
            // Anciennes signatures (contract_id)
            if ($contractId) {
                $paths[] = storage_path('app/public/signatures/' . $contractId . '_employee.png');
                $paths[] = storage_path('app/private/signatures/' . $contractId . '_employee.png');
            }
        } else {
            // Admin signature ou autre type
            $paths[] = storage_path('app/public/signatures/' . $filename);
            $paths[] = storage_path('app/private/signatures/' . $filename);
            $paths[] = 'signatures/' . $filename;
            $paths[] = public_path('signatures/' . $filename);
            $paths[] = public_path('storage/signatures/' . $filename);
        }
        
        // Logger la recherche
        $this->logSearchProcess("Recherche de la signature dans différents emplacements", $paths, $filename, $userId);
        
        // Essayer de charger le fichier depuis un des chemins possibles
        foreach ($paths as $path) {
            if (file_exists($path)) {
                try {
                    $this->logSearchProcess("Signature trouvée: {$path}", [$path], $filename, $userId);
                    $signatureContent = file_get_contents($path);
                    break;
                } catch (\Exception $e) {
                    $this->logSearchProcess("Erreur lors de la lecture du fichier: {$e->getMessage()}", [$path], $filename, $userId);
                }
            }
        }
        
        // Si aucune signature n'a été trouvée, générer une signature par défaut
        if (!$signatureContent) {
            $this->logSearchProcess("Aucune signature trouvée, génération d'une signature par défaut", [], $filename, $userId);
            
            if ($filename === 'admin_signature.png') {
                $signatureContent = $this->createAdminSignature();
            } else {
                $signatureContent = $this->createDefaultSignature($userId ? 'Employé #' . $userId : 'Employé');
            }
        }
        
        // Encoder en base64 pour inclusion dans le PDF
        if ($signatureContent) {
            return base64_encode($signatureContent);
        }
        
        return null;
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
        
        // Retourner le contenu au format attendu par les balises img src
        return 'data:image/png;base64,' . base64_encode($content);
    }
    
    /**
     * Crée une signature par défaut pour l'administrateur
     * 
     * @return string Le contenu de l'image générée
     */
    private function createAdminSignature()
    {
        // Générer une image de signature basique
        $img = imagecreatetruecolor(300, 100);
        $background = imagecolorallocate($img, 255, 255, 255);
        $textcolor = imagecolorallocate($img, 0, 0, 0);
        
        // Fond blanc
        imagefilledrectangle($img, 0, 0, 300, 100, $background);
        
        // Dessiner une signature stylisée (lignes courbes)
        imageline($img, 50, 50, 100, 30, $textcolor);
        imageline($img, 100, 30, 150, 70, $textcolor);
        imageline($img, 150, 70, 200, 40, $textcolor);
        imageline($img, 200, 40, 250, 60, $textcolor);
        
        // Sauvegarder l'image
        ob_start();
        imagepng($img);
        $content = ob_get_clean();
        imagedestroy($img);
        
        // Créer les dossiers pour les signatures si nécessaires
        $publicDir = storage_path('app/public/signatures');
        if (!file_exists($publicDir)) {
            mkdir($publicDir, 0755, true);
        }
        
        // Sauvegarder dans le dossier public
        file_put_contents($publicDir . '/admin_signature.png', $content);
        
        return $content;
    }
    
    /**
     * Crée une signature par défaut pour un employé
     * 
     * @param string $name Nom à afficher
     * @return string Le contenu de l'image générée
     */
    private function createDefaultSignature($name = 'Employé')
    {
        // Générer une image de signature basique
        $img = imagecreatetruecolor(300, 100);
        $background = imagecolorallocate($img, 255, 255, 255);
        $textcolor = imagecolorallocate($img, 0, 0, 0);
        
        // Fond blanc
        imagefilledrectangle($img, 0, 0, 300, 100, $background);
        
        // Dessiner une signature stylisée différente de celle de l'admin
        imageline($img, 70, 50, 130, 70, $textcolor);
        imageline($img, 130, 70, 180, 30, $textcolor);
        imageline($img, 180, 30, 230, 50, $textcolor);
        
        // Ajouter le texte du nom si l'extension freetype est disponible
        if (function_exists('imagettftext')) {
            $font = 5; // une police système par défaut
            imagefttext($img, 10, 0, 50, 80, $textcolor, $font, $name);
        }
        
        // Sauvegarder l'image
        ob_start();
        imagepng($img);
        $content = ob_get_clean();
        imagedestroy($img);
        
        return $content;
    }
    
    /**
     * Enregistre les informations de recherche dans les logs
     * 
     * @param string $message Message principal
     * @param array $paths Chemins vérifiés
     * @param string $filename Nom du fichier recherché
     * @param int|null $userId ID de l'utilisateur concerné
     */
    private function logSearchProcess($message, $paths = [], $filename = '', $userId = null)
    {
        \Log::info($message, [
            'paths' => $paths,
            'filename' => $filename,
            'userId' => $userId
        ]);
    }
} 