<?php

// Chemins des fichiers
$adminSignaturePath = __DIR__ . '/storage/app/public/signatures/admin_signature.png';
$employeeSignaturePath = __DIR__ . '/storage/app/public/signatures/1_employee.png';

echo "Test de lecture des fichiers d'image:\n";
echo "----------------------------------------\n";

// Vérifier l'existence des fichiers
echo "Fichier admin_signature.png existe: " . (file_exists($adminSignaturePath) ? 'Oui' : 'Non') . "\n";
echo "Fichier 1_employee.png existe: " . (file_exists($employeeSignaturePath) ? 'Oui' : 'Non') . "\n";
echo "\n";

// Vérifier la taille des fichiers
echo "Taille admin_signature.png: " . (file_exists($adminSignaturePath) ? filesize($adminSignaturePath) . ' octets' : 'N/A') . "\n";
echo "Taille 1_employee.png: " . (file_exists($employeeSignaturePath) ? filesize($employeeSignaturePath) . ' octets' : 'N/A') . "\n";
echo "\n";

// Tenter de lire les fichiers
echo "Lecture de admin_signature.png:\n";
if (file_exists($adminSignaturePath)) {
    try {
        $content = file_get_contents($adminSignaturePath);
        if ($content !== false) {
            $base64 = base64_encode($content);
            echo "Lecture réussie! Taille encodé en base64: " . strlen($base64) . " caractères\n";
            echo "Début du base64: " . substr($base64, 0, 30) . "...\n";
        } else {
            echo "ERREUR: Impossible de lire le fichier\n";
        }
    } catch (Exception $e) {
        echo "EXCEPTION: " . $e->getMessage() . "\n";
    }
} else {
    echo "ERREUR: Fichier inexistant\n";
}
echo "\n";

echo "Lecture de 1_employee.png:\n";
if (file_exists($employeeSignaturePath)) {
    try {
        $content = file_get_contents($employeeSignaturePath);
        if ($content !== false) {
            $base64 = base64_encode($content);
            echo "Lecture réussie! Taille encodé en base64: " . strlen($base64) . " caractères\n";
            echo "Début du base64: " . substr($base64, 0, 30) . "...\n";
        } else {
            echo "ERREUR: Impossible de lire le fichier\n";
        }
    } catch (Exception $e) {
        echo "EXCEPTION: " . $e->getMessage() . "\n";
    }
} else {
    echo "ERREUR: Fichier inexistant\n";
}
echo "\n";

// Résumé
echo "----------------------------------------\n";
echo "Si tous les tests ont réussi, le problème ne vient pas de la lecture des fichiers.\n";
echo "Le problème pourrait être lié au template Blade ou à la génération PDF.\n"; 