<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

class MoveSignatures extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'signatures:move';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Déplace les signatures dans les bons répertoires';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Début de la migration des signatures...');
        
        // Créer les répertoires cibles s'ils n'existent pas
        if (!Storage::disk('public')->exists('signatures/admin')) {
            Storage::disk('public')->makeDirectory('signatures/admin');
            $this->info('Répertoire signatures/admin créé');
        }
        
        if (!Storage::disk('public')->exists('signatures/employees')) {
            Storage::disk('public')->makeDirectory('signatures/employees');
            $this->info('Répertoire signatures/employees créé');
        }
        
        // 1. Déplacer signature admin
        $this->info('Traitement de la signature admin...');
        $adminSources = [
            'signatures/admin_signature.png',
            'signatures/admin.png'
        ];
        
        $adminMoved = false;
        foreach ($adminSources as $source) {
            if (Storage::disk('public')->exists($source)) {
                try {
                    $content = Storage::disk('public')->get($source);
                    Storage::disk('public')->put('signatures/admin/admin_signature.png', $content);
                    $this->info("Signature admin copiée depuis {$source}");
                    $adminMoved = true;
                    break;
                } catch (\Exception $e) {
                    $this->error("Erreur lors de la copie de {$source}: " . $e->getMessage());
                }
            }
        }
        
        if (!$adminMoved) {
            $this->warn('Aucune signature admin trouvée à déplacer');
        }
        
        // 2. Déplacer les signatures des employés
        $this->info('Traitement des signatures employés...');
        
        // Récupérer tous les fichiers du dossier signatures
        $allFiles = Storage::disk('public')->files('signatures');
        
        // Filtrer pour ne garder que les fichiers de signature employé
        $employeeSignatures = array_filter($allFiles, function($file) {
            return strpos($file, 'employee') !== false || 
                   preg_match('/signatures\/\d+\.png/', $file);
        });
        
        $movedCount = 0;
        
        // Traiter chaque fichier
        foreach ($employeeSignatures as $file) {
            $filename = basename($file);
            
            // Déterminer l'ID de l'employé à partir du nom de fichier
            $userId = null;
            
            if (preg_match('/(\d+)_employee\.png/', $filename, $matches)) {
                $userId = $matches[1];
            } elseif (preg_match('/employee-(\d+)\.png/', $filename, $matches)) {
                $userId = $matches[1];
            } elseif (preg_match('/^(\d+)\.png$/', $filename, $matches)) {
                $userId = $matches[1];
            }
            
            if ($userId) {
                try {
                    $content = Storage::disk('public')->get($file);
                    Storage::disk('public')->put("signatures/employees/{$userId}.png", $content);
                    $this->info("Signature de l'employé {$userId} copiée depuis {$file}");
                    $movedCount++;
                } catch (\Exception $e) {
                    $this->error("Erreur lors de la copie de {$file}: " . $e->getMessage());
                }
            } else {
                $this->warn("Impossible de déterminer l'ID de l'employé pour le fichier {$file}");
            }
        }
        
        $this->info("Migration terminée: {$movedCount} signatures d'employés traitées");
        
        return Command::SUCCESS;
    }
} 