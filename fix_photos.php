<?php
// Script de correction des photos manquantes dans le système

require __DIR__.'/vendor/autoload.php';

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Application;

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Script de correction des photos de profil\n";
echo "======================================\n\n";

// Vérifier si le répertoire des photos existe
$photoDir = storage_path('app/public/profile-photos');
if (!file_exists($photoDir)) {
    mkdir($photoDir, 0777, true);
    echo "✅ Répertoire profile-photos créé: $photoDir\n";
} else {
    echo "✓ Le répertoire profile-photos existe déjà\n";
}

// Vérifier que le répertoire est accessible en écriture
if (!is_writable($photoDir)) {
    chmod($photoDir, 0777);
    echo "✅ Permissions du répertoire profile-photos ajustées à 0777\n";
} else {
    echo "✓ Le répertoire profile-photos est accessible en écriture\n";
}

// Vérifier si le lien symbolique existe
if (!file_exists(public_path('storage'))) {
    echo "ℹ️ Le lien symbolique storage n'existe pas, création en cours...\n";
    try {
        \Artisan::call('storage:link');
        echo "✅ Lien symbolique storage:link créé\n";
    } catch (Exception $e) {
        echo "❌ Erreur lors de la création du lien symbolique: " . $e->getMessage() . "\n";
    }
} else {
    echo "✓ Le lien symbolique storage existe déjà\n";
}

// Récupérer tous les utilisateurs avec une photo de profil
echo "\nRecherche des utilisateurs avec photos de profil...\n";
$usersWithPhotos = \App\Models\User::whereNotNull('profile_photo_path')->get();
echo "Trouvé " . count($usersWithPhotos) . " utilisateurs avec photos\n\n";

$count = 0;
$errors = 0;
$fixed = 0;

foreach ($usersWithPhotos as $user) {
    $photoPath = $user->profile_photo_path;
    echo "Traitement de la photo de l'utilisateur #" . $user->id . ": " . $photoPath . "\n";
    
    // Vérifier si le chemin contient "public/"
    if (strpos($photoPath, 'public/') === 0) {
        // Supprimer "public/" du chemin
        $newPath = str_replace('public/', '', $photoPath);
        echo "⚠️ Correction du chemin photo (suppression de 'public/'): " . $newPath . "\n";
        $user->profile_photo_path = $newPath;
        $user->save();
        $fixed++;
    }
    
    // Vérifier si le fichier existe physiquement
    $fullPath = storage_path('app/public/' . $user->profile_photo_path);
    if (!file_exists($fullPath)) {
        echo "❌ Photo manquante: " . $fullPath . "\n";
        $errors++;
        continue;
    }
    
    // Vérifier et corriger les permissions du fichier
    if (file_exists($fullPath) && !is_readable($fullPath)) {
        chmod($fullPath, 0644);
        echo "✅ Permissions du fichier ajustées à 0644: " . $fullPath . "\n";
        $fixed++;
    }
    
    echo "✓ La photo est accessible: " . $fullPath . "\n";
    $count++;
}

// Récupérer aussi les données de contrat avec des photos
echo "\nRecherche des photos dans les données de contrat...\n";
$contractsWithPhotos = \App\Models\ContractData::whereNotNull('photo_path')->get();
echo "Trouvé " . count($contractsWithPhotos) . " contrats avec des photos\n\n";

foreach ($contractsWithPhotos as $data) {
    // Vérifier si le fichier existe
    $photoPath = $data->photo_path;
    echo "Traitement de la photo du contrat #" . $data->contract_id . ": " . $photoPath . "\n";
    
    // Vérifier si le chemin contient "public/"
    if (strpos($photoPath, 'public/') === 0) {
        // Supprimer "public/" du chemin
        $newPath = str_replace('public/', '', $photoPath);
        echo "⚠️ Correction du chemin photo (suppression de 'public/'): " . $newPath . "\n";
        $data->photo_path = $newPath;
        $data->save();
        $fixed++;
    }
    
    $fullPath = storage_path('app/public/' . $data->photo_path);
    if (!file_exists($fullPath)) {
        echo "❌ Photo manquante: " . $fullPath . "\n";
        $errors++;
        continue;
    }
    
    // Synchroniser avec le profil utilisateur si nécessaire
    $contract = \App\Models\Contract::find($data->contract_id);
    if ($contract && $contract->user) {
        if (empty($contract->user->profile_photo_path)) {
            $contract->user->update([
                'profile_photo_path' => $data->photo_path
            ]);
            echo "✅ Photo de profil synchronisée pour l'utilisateur #" . $contract->user_id . "\n";
            $fixed++;
        }
        $count++;
    }
}

echo "\n======== RÉSUMÉ ========\n";
echo "Total de photos traitées: " . $count . "\n";
echo "Photos corrigées: " . $fixed . "\n";
echo "Erreurs: " . $errors . "\n";
echo "========================\n";

echo "\nTraitement terminé.\n"; 