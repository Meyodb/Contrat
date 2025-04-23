<?php
// Script de correction pour les problèmes d'affichage des photos de profil

require __DIR__.'/vendor/autoload.php';

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Application;

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Script de correction d'affichage des photos de profil\n";
echo "==================================================\n\n";

// Vérifier si le lien symbolique existe
if (!file_exists(public_path('storage'))) {
    echo "⚠️ Le lien symbolique storage n'existe pas, création en cours...\n";
    try {
        \Artisan::call('storage:link');
        echo "✅ Lien symbolique storage:link créé\n";
    } catch (Exception $e) {
        echo "❌ Erreur lors de la création du lien symbolique: " . $e->getMessage() . "\n";
    }
} else {
    echo "✓ Le lien symbolique storage existe\n";
}

// Créer le lien manuellement si nécessaire
$publicStoragePath = public_path('storage');
if (!file_exists($publicStoragePath)) {
    echo "⚠️ Tentative de création manuelle du lien symbolique...\n";
    $target = storage_path('app/public');
    
    try {
        if (symlink($target, $publicStoragePath)) {
            echo "✅ Lien symbolique créé manuellement\n";
        } else {
            echo "❌ Échec de la création manuelle du lien symbolique\n";
        }
    } catch (Exception $e) {
        echo "❌ Exception lors de la création manuelle: " . $e->getMessage() . "\n";
    }
}

// Vérifier le répertoire de stockage sans modifier les permissions
$photoDir = storage_path('app/public/profile-photos');
if (!file_exists($photoDir)) {
    try {
        mkdir($photoDir, 0755, true);
        echo "✅ Répertoire profile-photos créé: $photoDir\n";
    } catch (Exception $e) {
        echo "❌ Erreur lors de la création du répertoire: " . $e->getMessage() . "\n";
    }
} else {
    echo "✓ Le répertoire profile-photos existe\n";
}

// Récupérer tous les utilisateurs avec une photo de profil
echo "\nRecherche des utilisateurs avec photos de profil...\n";
$usersWithPhotos = \App\Models\User::whereNotNull('profile_photo_path')->get();
echo "Trouvé " . count($usersWithPhotos) . " utilisateurs avec photos\n\n";

$fixed = 0;

foreach ($usersWithPhotos as $user) {
    echo "Traitement de la photo de l'utilisateur #{$user->id} - {$user->name}:\n";
    
    // Vérifier et corriger le chemin de la photo si nécessaire
    $photoPath = $user->profile_photo_path;
    $correctedPath = $photoPath;
    
    // Enlever "public/" si présent
    if (strpos($photoPath, 'public/') === 0) {
        $correctedPath = str_replace('public/', '', $photoPath);
        echo "  ⚠️ Correction du chemin (suppression de 'public/'): {$correctedPath}\n";
    }
    
    // S'assurer que le chemin commence par "profile-photos/"
    if (strpos($correctedPath, 'profile-photos/') !== 0) {
        $correctedPath = 'profile-photos/' . basename($correctedPath);
        echo "  ⚠️ Correction du chemin (ajout du préfixe 'profile-photos/'): {$correctedPath}\n";
    }
    
    // Mettre à jour si le chemin a été corrigé
    if ($correctedPath !== $photoPath) {
        $user->profile_photo_path = $correctedPath;
        try {
            $user->save();
            echo "  ✅ Chemin de photo mis à jour dans la base de données\n";
            $fixed++;
        } catch (Exception $e) {
            echo "  ❌ Erreur lors de la mise à jour: " . $e->getMessage() . "\n";
        }
    }
    
    // Vérifier si le fichier existe physiquement
    $fullPath = storage_path('app/public/' . $correctedPath);
    echo "  📂 Chemin complet: {$fullPath}\n";
    
    if (file_exists($fullPath)) {
        echo "  ✅ Fichier existant\n";
        // Ne pas essayer de modifier les permissions, juste vérifier si le fichier est lisible
        if (is_readable($fullPath)) {
            echo "  ✓ Le fichier est lisible\n";
        } else {
            echo "  ⚠️ Le fichier existe mais n'est pas lisible\n";
        }
    } else {
        echo "  ❌ ERREUR: Fichier introuvable\n";
    }
    
    // Tester si le fichier est accessible via l'URL
    $url = Storage::url($correctedPath);
    echo "  🔗 URL d'accès: {$url}\n";
    
    echo "\n";
}

echo "\n======== RÉSUMÉ ========\n";
echo "Utilisateurs traités: " . count($usersWithPhotos) . "\n";
echo "Chemins corrigés: " . $fixed . "\n";
echo "========================\n";

echo "\nVérification terminée. Si des photos ne s'affichent toujours pas:\n";
echo "1. Vérifiez que le lien symbolique 'public/storage' pointe vers 'storage/app/public'\n";
echo "2. Assurez-vous que les permissions permettent au serveur web de lire les fichiers\n";
echo "3. Vérifiez la configuration de la façade Storage dans config/filesystems.php\n"; 