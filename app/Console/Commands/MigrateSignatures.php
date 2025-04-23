<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class MigrateSignatures extends Command
{
    protected $signature = 'signatures:migrate';
    protected $description = 'Migre toutes les signatures vers la nouvelle structure de dossiers unifiée';

    public function handle()
    {
        $this->info('Début de la migration des signatures...');
        
        // Créer les nouveaux répertoires
        $newDirectories = [
            storage_path('app/public/signatures/admin'),
            storage_path('app/public/signatures/employees'),
            storage_path('app/public/signatures/contracts'),
        ];
        
        foreach ($newDirectories as $dir) {
            if (!File::exists($dir)) {
                File::makeDirectory($dir, 0755, true);
                $this->info("Répertoire créé: {$dir}");
            }
        }
        
        // Anciens emplacements à rechercher
        $oldLocations = [
            storage_path('app/public/signatures'),
            storage_path('app/private/signatures'),
            storage_path('app/private/contracts'),
            public_path('images'),
        ];
        
        // Migrer les signatures d'administrateur
        $adminPatterns = ['admin*.png', 'admin_signature.png', 'admin.png'];
        $adminSignatures = [];
        
        foreach ($adminPatterns as $pattern) {
            foreach ($oldLocations as $location) {
                if (File::exists($location)) {
                    $found = File::glob("{$location}/{$pattern}");
                    $adminSignatures = array_merge($adminSignatures, $found);
                }
            }
        }
        
        if (count($adminSignatures) > 0) {
            $newAdminPath = storage_path('app/public/signatures/admin/admin_signature.png');
            $this->copyFile($adminSignatures[0], $newAdminPath);
            $this->info("Signature admin migrée: {$newAdminPath}");
        } else {
            $this->warn("Aucune signature admin trouvée à migrer");
        }
        
        // Migrer les signatures d'employés
        $employeePatterns = ['*_employee.png', 'employee-*.png', 'employee.png'];
        $employeeSignatures = [];
        
        foreach ($employeePatterns as $pattern) {
            foreach ($oldLocations as $location) {
                if (File::exists($location)) {
                    $found = File::glob("{$location}/{$pattern}");
                    $employeeSignatures = array_merge($employeeSignatures, $found);
                }
            }
        }
        
        $migratedCount = 0;
        foreach ($employeeSignatures as $signature) {
            $fileName = basename($signature);
            $userId = null;
            
            // Extraire l'ID d'utilisateur du nom de fichier
            if (preg_match('/^(\d+)_employee\.png$/', $fileName, $matches)) {
                $userId = $matches[1];
            } else if (preg_match('/^employee-(\d+)\.png$/', $fileName, $matches)) {
                $userId = $matches[1];
            } else {
                // C'est une signature employé générique
                $newPath = storage_path('app/public/signatures/employees/default.png');
                if (!File::exists($newPath)) {
                    $this->copyFile($signature, $newPath);
                    $this->info("Signature employé générique migrée: {$newPath}");
                }
                continue;
            }
            
            if ($userId) {
                $newPath = storage_path("app/public/signatures/employees/{$userId}.png");
                $this->copyFile($signature, $newPath);
                $migratedCount++;
            }
        }
        
        $this->info("Nombre de signatures d'employés migrées: {$migratedCount}");
        
        // Migrer les signatures de contrats spécifiques (si elles existent)
        $contractDirs = File::directories(storage_path('app/private/contracts'));
        $contractMigratedCount = 0;
        
        foreach ($contractDirs as $contractDir) {
            $contractId = basename($contractDir);
            $signatureDir = "{$contractDir}/signatures";
            
            if (File::exists($signatureDir)) {
                $contractSignatures = File::glob("{$signatureDir}/*.png");
                
                foreach ($contractSignatures as $signature) {
                    $fileName = basename($signature);
                    $userId = null;
                    
                    // Extraire l'ID d'utilisateur du nom de fichier
                    if (preg_match('/^(\d+)_employee\.png$/', $fileName, $matches) || 
                        preg_match('/^employee-(\d+)\.png$/', $fileName, $matches)) {
                        $userId = $matches[1];
                    } else {
                        continue; // Ignorer les fichiers qui ne correspondent pas au pattern
                    }
                    
                    $newContractDir = storage_path("app/public/signatures/contracts/{$contractId}");
                    
                    if (!File::exists($newContractDir)) {
                        File::makeDirectory($newContractDir, 0755, true);
                    }
                    
                    $newPath = "{$newContractDir}/employee_{$userId}.png";
                    $this->copyFile($signature, $newPath);
                    $contractMigratedCount++;
                }
            }
        }
        
        $this->info("Nombre de signatures de contrats migrées: {$contractMigratedCount}");
        $this->info('Migration des signatures terminée.');
    }
    
    private function copyFile($source, $destination)
    {
        try {
            File::copy($source, $destination);
            $this->info("Fichier copié: {$source} → {$destination}");
        } catch (\Exception $e) {
            $this->error("Erreur lors de la copie du fichier {$source}: " . $e->getMessage());
            Log::error("Erreur de migration de signature", [
                'source' => $source,
                'destination' => $destination,
                'error' => $e->getMessage()
            ]);
        }
    }
} 