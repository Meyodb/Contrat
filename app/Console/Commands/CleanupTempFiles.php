<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanupTempFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-temp-files {--days=7 : Nombre de jours après lesquels les fichiers sont considérés comme obsolètes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Nettoie les fichiers temporaires et les prévisualisations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $this->info("Nettoyage des fichiers temporaires plus anciens que {$days} jours");
        
        // Date limite (timestamp)
        $olderThan = time() - ($days * 86400);
        
        // Répertoires à nettoyer
        $patterns = [
            'temp' => ['*.pdf', '*.docx', '*.html'],
            'private/previews' => ['*.pdf'],
        ];
        
        $totalDeleted = 0;
        
        foreach ($patterns as $directory => $pattern) {
            $this->info("Nettoyage du répertoire {$directory}...");
            
            // S'assurer que le répertoire existe
            $path = storage_path('app/' . $directory);
            if (!is_dir($path)) {
                $this->warn("Répertoire {$directory} introuvable, création...");
                mkdir($path, 0755, true);
                continue;
            }
            
            foreach ($pattern as $pattern) {
                $deleted = $this->cleanDirectory($directory, $pattern, $olderThan);
                $totalDeleted += $deleted;
                $this->line("  - {$deleted} fichiers {$pattern} supprimés");
            }
        }
        
        // Nettoyage des dossiers de debug HTML
        $this->info("Nettoyage des fichiers de debug HTML...");
        $debugDeleted = $this->cleanDirectory('temp', 'debug_*.html', $olderThan);
        $totalDeleted += $debugDeleted;
        $this->line("  - {$debugDeleted} fichiers de debug HTML supprimés");
        
        $this->info("Nettoyage terminé : {$totalDeleted} fichiers supprimés au total");
        
        return Command::SUCCESS;
    }
    
    /**
     * Nettoie les fichiers obsolètes d'un répertoire
     */
    private function cleanDirectory($directory, $pattern, $olderThan)
    {
        $count = 0;
        $path = storage_path('app/' . $directory);
        
        $files = glob($path . '/' . $pattern);
        
        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $olderThan) {
                if (unlink($file)) {
                    $count++;
                    Log::info("Fichier temporaire supprimé", ['file' => $file]);
                } else {
                    Log::error("Impossible de supprimer le fichier", ['file' => $file]);
                }
            }
        }
        
        return $count;
    }
} 