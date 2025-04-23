<?php

/**
 * Script de correction des problèmes de signatures
 * Ce script permet de corriger les problèmes liés aux signatures
 * dans l'application de gestion de contrats.
 * 
 * Utilisation:
 * php fix_signatures.php
 */

// Définir le chemin de base de l'application
$basePath = __DIR__;

// Charger l'autoloader
require $basePath . '/vendor/autoload.php';

// Charger l'application Laravel
$app = require_once $basePath . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Temp_Fixes\SignatureHelper;

// Désactiver temporairement la limite de temps d'exécution
set_time_limit(0);

// Fonction pour afficher un message
function output($message) {
    echo $message . "\n";
    Log::info($message);
}

// Fonction pour modifier les permissions avec gestion d'erreurs
function safeChmod($path, $permissions) {
    try {
        if (file_exists($path)) {
            chmod($path, $permissions);
            return true;
        }
        return false;
    } catch (\Exception $e) {
        return false;
    }
}

// Fonction pour créer un répertoire avec gestion d'erreurs
function safeMkdir($path, $permissions = 0777, $recursive = true) {
    try {
        if (!file_exists($path)) {
            return mkdir($path, $permissions, $recursive);
        }
        return true;
    } catch (\Exception $e) {
        return false;
    }
}

// En-tête
output("=========================================================");
output("  CORRECTION DES PROBLÈMES DE SIGNATURES - " . date('Y-m-d H:i:s'));
output("=========================================================");

// Vérifier si nous avons les droits de modification
$testPath = storage_path('app/public');
$canModify = true;

if (file_exists($testPath)) {
    $canModify = safeChmod($testPath, 0777);
} else {
    $canModify = safeMkdir($testPath, 0777, true);
}

if (!$canModify) {
    output("\n⚠️ AVERTISSEMENT: Vous n'avez pas les droits suffisants pour modifier les permissions");
    output("Pour résoudre ce problème, exécutez les commandes suivantes:");
    output("sudo chown -R www-data:www-data " . storage_path('app/public'));
    output("sudo chmod -R 777 " . storage_path('app/public'));
    output("sudo chown -R www-data:www-data " . public_path('signatures'));
    output("sudo chmod -R 777 " . public_path('signatures'));
    
    $continueAnyway = readline("\nVoulez-vous continuer quand même? (o/n): ");
    if (strtolower($continueAnyway) !== 'o' && strtolower($continueAnyway) !== 'oui') {
        output("\nInterruption de la correction. Veuillez réexécuter le script avec les droits appropriés.");
        exit;
    }
    
    output("\nPoursuite du script sans modification des permissions...");
}

try {
    // 1. Créer/corriger les répertoires nécessaires
    output("\n1. Vérification et création des répertoires nécessaires");
    
    $directories = [
        storage_path('app/public'),
        storage_path('app/public/signatures'),
        storage_path('app/public/signatures/admin'),
        storage_path('app/public/signatures/employees'),
        public_path('signatures')
    ];
    
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            output("   - Création du répertoire: " . $dir);
            if (safeMkdir($dir, 0777, true)) {
                output("   - Répertoire créé avec succès");
            } else {
                output("   - ⚠️ Impossible de créer le répertoire");
            }
        } else {
            output("   - Correction des permissions du répertoire: " . $dir);
            if (safeChmod($dir, 0777)) {
                output("   - Permissions modifiées avec succès");
            } else {
                output("   - ⚠️ Impossible de modifier les permissions");
            }
        }
    }
    
    // 2. Créer une signature admin par défaut si elle n'existe pas
    output("\n2. Vérification de la signature administrative");
    
    $adminSignaturePath = storage_path('app/public/signatures/admin/admin_signature.png');
    $publicAdminSignaturePath = public_path('signatures/admin_signature.png');
    
    if (!file_exists($adminSignaturePath)) {
        output("   - Création d'une signature admin par défaut");
        try {
            $helper = new SignatureHelper();
            $helper->createAdminSignature();
            output("   - Signature créée avec succès");
        } catch (\Exception $e) {
            output("   - ⚠️ Erreur lors de la création de la signature: " . $e->getMessage());
        }
    } else {
        output("   - Signature admin existante: " . $adminSignaturePath);
    }
    
    // S'assurer que la signature admin est accessible
    output("   - Correction des permissions de la signature admin");
    if (safeChmod($adminSignaturePath, 0777)) {
        output("   - Permissions modifiées avec succès");
    } else {
        output("   - ⚠️ Impossible de modifier les permissions");
    }
    
    // Copier également dans le dossier public
    if (!file_exists($publicAdminSignaturePath)) {
        output("   - Copie de la signature admin dans le dossier public");
        if (!file_exists(dirname($publicAdminSignaturePath))) {
            safeMkdir(dirname($publicAdminSignaturePath), 0777, true);
        }
        if (file_exists($adminSignaturePath) && copy($adminSignaturePath, $publicAdminSignaturePath)) {
            safeChmod($publicAdminSignaturePath, 0777);
            output("   - Signature copiée avec succès");
        } else {
            output("   - ⚠️ Impossible de copier la signature");
        }
    }
    
    // 3. Créer le lien symbolique si nécessaire
    output("\n3. Vérification du lien symbolique storage");
    
    if (!file_exists(public_path('storage'))) {
        output("   - Création du lien symbolique pour le dossier storage");
        try {
            // Tenter d'utiliser Artisan
            Illuminate\Support\Facades\Artisan::call('storage:link');
            output("   - Lien symbolique créé avec succès via Artisan");
        } catch (\Exception $e) {
            // En cas d'échec, créer manuellement
            output("   - Échec de la création via Artisan, tentative manuelle");
            if (symlink(storage_path('app/public'), public_path('storage'))) {
                output("   - Lien symbolique créé manuellement");
            } else {
                output("   - ⚠️ Impossible de créer le lien symbolique");
                output("   - Commande pour créer le lien: ln -s " . storage_path('app/public') . " " . public_path('storage'));
            }
        }
    } else {
        output("   - Lien symbolique storage déjà existant");
    }
    
    // 4. Corriger les permissions de tous les fichiers de signature
    output("\n4. Correction des permissions des fichiers de signature");
    
    // Trouver tous les fichiers de signature
    $signatureFiles = [
        $adminSignaturePath,
        $publicAdminSignaturePath
    ];
    
    // Ajouter les signatures des employés
    $employeeSignatures = glob(storage_path('app/public/signatures/employees/*.png'));
    if (!empty($employeeSignatures)) {
        output("   - Fichiers de signature d'employés trouvés: " . count($employeeSignatures));
        $signatureFiles = array_merge($signatureFiles, $employeeSignatures);
    } else {
        output("   - Aucun fichier de signature d'employé trouvé");
    }
    
    // Ajouter les signatures du dossier public
    $publicSignatures = glob(public_path('signatures/*.png'));
    if (!empty($publicSignatures)) {
        output("   - Fichiers de signature publics trouvés: " . count($publicSignatures));
        $signatureFiles = array_merge($signatureFiles, $publicSignatures);
    }
    
    // Appliquer les permissions
    $fixedCount = 0;
    foreach ($signatureFiles as $file) {
        if (file_exists($file) && safeChmod($file, 0777)) {
            $fixedCount++;
        }
    }
    
    output("   - {$fixedCount} fichiers de signature corrigés");
    
    // 5. Vérification des différentes copies des signatures
    output("\n5. Synchronisation des signatures entre les différentes locations");
    
    // Admin signature
    if (file_exists($adminSignaturePath) && !file_exists($publicAdminSignaturePath)) {
        if (copy($adminSignaturePath, $publicAdminSignaturePath)) {
            safeChmod($publicAdminSignaturePath, 0777);
            output("   - Signature admin copiée vers le dossier public");
        } else {
            output("   - ⚠️ Impossible de copier la signature admin vers le dossier public");
        }
    } elseif (!file_exists($adminSignaturePath) && file_exists($publicAdminSignaturePath)) {
        if (copy($publicAdminSignaturePath, $adminSignaturePath)) {
            safeChmod($adminSignaturePath, 0777);
            output("   - Signature admin copiée depuis le dossier public");
        } else {
            output("   - ⚠️ Impossible de copier la signature admin depuis le dossier public");
        }
    }
    
    // Employés
    try {
        $helper = new SignatureHelper();
        output("   - Appel de la méthode migrateSignatures pour synchroniser les signatures");
        $migrateResult = $helper->migrateSignatures();
        output("   - Résultat: " . $migrateResult['success'] . " signatures migrées, " . 
               $migrateResult['errors'] . " erreurs");
        
        // Afficher les détails
        if (!empty($migrateResult['details'])) {
            foreach ($migrateResult['details'] as $detail) {
                output("     ℹ️ " . $detail);
            }
        }
    } catch (\Exception $e) {
        output("   - ⚠️ Erreur lors de la migration des signatures: " . $e->getMessage());
    }

    // Résumé
    output("\n✅ Correction des signatures terminée");
    output("   - Répertoires vérifiés: " . count($directories));
    output("   - Permissions de fichiers corrigées: " . $fixedCount);
    
} catch (\Exception $e) {
    output("\n❌ ERREUR: " . $e->getMessage());
    output("Trace: " . $e->getTraceAsString());
}

output("\n=========================================================");
output("  CORRECTION TERMINÉE");
output("=========================================================");
output("\nVous pouvez maintenant utiliser l'application normalement."); 