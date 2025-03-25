# Système de Gestion de Contrats d'Employés

Application web développée avec Laravel pour gérer les contrats d'employés, avec génération de PDF et signature électronique.

## Fonctionnalités

- Création et gestion de contrats de travail
- Interface administrateur pour la validation et la signature des contrats
- Interface employé pour remplir et signer les contrats
- Génération de PDF des contrats signés
- Signature électronique directement dans l'application
- Gestion des photos d'identité des employés
- Gestion des données personnelles et professionnelles
- Suivi du statut des contrats (brouillon, soumis, signé, etc.)

## Installation

### Prérequis

- PHP 8.0 ou plus récent
- Composer
- MySQL ou MariaDB
- Serveur Web (Apache, Nginx)
- Extension PHP GD (pour les signatures)

### Instructions d'installation

1. Cloner le dépôt
```bash
git clone https://github.com/votre-username/contract-management.git
cd contract-management
```

2. Installer les dépendances
```bash
composer install
```

3. Configurer l'environnement
```bash
cp .env.example .env
php artisan key:generate
```

4. Configurer la base de données dans le fichier .env

5. Exécuter les migrations
```bash
php artisan migrate
```

6. Créer le lien symbolique pour le stockage
```bash
php artisan storage:link
```

7. Lancer le serveur
```bash
php artisan serve
```

## Structure du projet

- `app/Http/Controllers/Admin` - Contrôleurs pour les administrateurs
- `app/Http/Controllers/Employee` - Contrôleurs pour les employés
- `app/Models` - Modèles de données
- `resources/views/admin` - Vues pour l'interface administrateur
- `resources/views/employee` - Vues pour l'interface employé
- `resources/views/pdf` - Templates pour la génération de PDF
- `storage/app/public/signatures` - Stockage des signatures
- `storage/app/public/employee_photos` - Stockage des photos d'identité

## Licence

Ce projet est sous licence [MIT](https://opensource.org/licenses/MIT).
