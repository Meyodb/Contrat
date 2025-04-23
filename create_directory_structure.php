<?php
// Script pour créer la structure des répertoires nécessaires pour le stockage des photos

// Répertoires à créer
$directories = [
    __DIR__ . '/storage/app/public/employee_photos',
    __DIR__ . '/storage/app/public/profile-photos',
    __DIR__ . '/public/storage/employee_photos',
    __DIR__ . '/public/storage/profile-photos'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "Répertoire créé: $dir\n";
        } else {
            echo "Erreur lors de la création du répertoire: $dir\n";
        }
    } else {
        echo "Le répertoire existe déjà: $dir\n";
    }
}

// Vérifier que le lien symbolique existe
if (!file_exists(__DIR__ . '/public/storage') || !is_link(__DIR__ . '/public/storage')) {
    echo "Le lien symbolique public/storage ne semble pas être configuré correctement.\n";
    echo "Essayons de créer le lien manuellement...\n";
    
    // Créer le lien symbolique manuellement
    if (symlink(__DIR__ . '/storage/app/public', __DIR__ . '/public/storage')) {
        echo "Lien symbolique créé avec succès: public/storage -> storage/app/public\n";
    } else {
        echo "Échec de la création du lien symbolique. Erreur: " . error_get_last()['message'] . "\n";
    }
} else {
    echo "Le lien symbolique public/storage existe déjà.\n";
}

echo "Configuration terminée.\n";
?> 