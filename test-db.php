<?php

// Charger les variables d'environnement depuis le fichier .env
$env = parse_ini_file(__DIR__ . '/.env');

// Informations de connexion à la base de données
$host = $env['DB_HOST'];
$database = $env['DB_DATABASE'];
$username = $env['DB_USERNAME'];
$password = $env['DB_PASSWORD'];

try {
    // Connexion à la base de données
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connexion à la base de données réussie!\n";
    
    // Vérifier si la table model_has_roles existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'model_has_roles'");
    if ($stmt->rowCount() > 0) {
        echo "La table model_has_roles existe.\n";
        
        // Compter les entrées dans la table
        $count = $pdo->query("SELECT COUNT(*) FROM model_has_roles")->fetchColumn();
        echo "Nombre d'entrées dans model_has_roles: $count\n";
    } else {
        echo "La table model_has_roles n'existe PAS!\n";
    }
    
    // Vérifier si la table roles existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'roles'");
    if ($stmt->rowCount() > 0) {
        echo "La table roles existe.\n";
        
        // Compter les entrées dans la table
        $count = $pdo->query("SELECT COUNT(*) FROM roles")->fetchColumn();
        echo "Nombre d'entrées dans roles: $count\n";
        
        // Afficher les rôles
        $roles = $pdo->query("SELECT * FROM roles")->fetchAll(PDO::FETCH_ASSOC);
        echo "Rôles disponibles:\n";
        foreach ($roles as $role) {
            echo "- ID: {$role['id']}, Nom: {$role['name']}, Guard: {$role['guard_name']}\n";
        }
    } else {
        echo "La table roles n'existe PAS!\n";
    }
    
    // Lister toutes les tables
    echo "\nListe de toutes les tables dans la base de données:\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo "- $table\n";
    }
    
} catch (PDOException $e) {
    echo "Erreur de connexion à la base de données: " . $e->getMessage() . "\n";
} 