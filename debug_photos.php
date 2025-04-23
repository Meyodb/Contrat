<?php
// Script de débogage pour vérifier les problèmes liés aux photos

// Charger l'autoloader de Composer pour utiliser les classes du projet
require __DIR__ . '/vendor/autoload.php';

// Charger les variables d'environnement
$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Connexion à la base de données
$db = new PDO(
    'mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_DATABASE'] . ';charset=utf8mb4',
    $_ENV['DB_USERNAME'],
    $_ENV['DB_PASSWORD']
);

// Requête pour obtenir tous les utilisateurs avec leur chemin de photo
$query = $db->query('SELECT id, name, email, profile_photo_path FROM users');
$users = $query->fetchAll(PDO::FETCH_ASSOC);

echo "=== VÉRIFICATION DES PHOTOS DE PROFIL ===\n\n";
echo "Nombre d'utilisateurs trouvés: " . count($users) . "\n\n";

foreach ($users as $user) {
    echo "Utilisateur: " . $user['name'] . " (" . $user['email'] . ")\n";
    
    if (empty($user['profile_photo_path'])) {
        echo "  - Aucun chemin de photo défini\n";
    } else {
        echo "  - Chemin de photo: " . $user['profile_photo_path'] . "\n";
        
        // Vérifier si le fichier existe dans storage/app/public
        $storagePath = __DIR__ . '/storage/app/public/' . $user['profile_photo_path'];
        if (file_exists($storagePath)) {
            echo "  - SUCCÈS: Le fichier existe dans storage/app/public\n";
        } else {
            echo "  - ERREUR: Le fichier n'existe pas dans storage/app/public\n";
        }
        
        // Vérifier si le fichier existe dans public/storage
        $publicPath = __DIR__ . '/public/storage/' . $user['profile_photo_path'];
        if (file_exists($publicPath)) {
            echo "  - SUCCÈS: Le fichier existe dans public/storage\n";
        } else {
            echo "  - ERREUR: Le fichier n'existe pas dans public/storage\n";
            
            // Créer le répertoire parent si nécessaire
            $dir = dirname($publicPath);
            if (!file_exists($dir)) {
                echo "      - Création du répertoire: " . $dir . "\n";
                if (mkdir($dir, 0755, true)) {
                    echo "      - Répertoire créé avec succès\n";
                } else {
                    echo "      - Échec de la création du répertoire\n";
                }
            }
            
            // Essayer de copier le fichier depuis storage/app/public si possible
            if (file_exists($storagePath)) {
                echo "      - Tentative de copie du fichier de storage/app/public vers public/storage\n";
                if (copy($storagePath, $publicPath)) {
                    echo "      - Fichier copié avec succès\n";
                } else {
                    echo "      - Échec de la copie du fichier\n";
                }
            }
        }
    }
    
    echo "\n";
}

// Vérifier la configuration du lien symbolique
echo "=== VÉRIFICATION DU LIEN SYMBOLIQUE ===\n\n";

$target = __DIR__ . '/storage/app/public';
$link = __DIR__ . '/public/storage';

if (is_link($link)) {
    echo "Le lien symbolique existe.\n";
    echo "  - Cible du lien: " . readlink($link) . "\n";
    
    if (file_exists($link)) {
        echo "  - Le lien est valide et pointe vers un répertoire existant.\n";
    } else {
        echo "  - ERREUR: Le lien existe mais pointe vers un répertoire inexistant.\n";
    }
} else {
    if (file_exists($link)) {
        echo "ATTENTION: /public/storage existe mais n'est pas un lien symbolique.\n";
    } else {
        echo "ERREUR: /public/storage n'existe pas du tout.\n";
    }
    
    echo "Tentative de création du lien symbolique...\n";
    if (symlink($target, $link)) {
        echo "  - Lien symbolique créé avec succès.\n";
    } else {
        echo "  - Échec de la création du lien symbolique: " . error_get_last()['message'] . "\n";
    }
}

echo "\nDébogage terminé.\n";
?> 