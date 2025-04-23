<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\User;
use App\Models\CompanyInfo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Temp_Fixes\SignatureHelper;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class PdfController extends Controller
{
    /**
     * Génère un PDF pour un contrat
     * 
     * @param Contract $contract Le contrat pour lequel générer le PDF
     * @param bool $isPreview Si vrai, génère une prévisualisation
     * @return string|null Le chemin vers le fichier PDF généré
     */
    public function generateContractPdf($contract, $isPreview = false, $isAvenant = false)
    {
        Log::info('Début de la génération du PDF', [
            'contract_id' => $contract->id ?? 'temp',
            'is_preview' => $isPreview ? 'oui' : 'non',
            'is_avenant' => $isAvenant ? 'oui' : 'non'
        ]);

        // Augmenter la limite de mémoire pour éviter les erreurs lors de la génération
        ini_set('memory_limit', '512M');

        try {
            // Récupérer les données nécessaires
            $user = $contract->user;
            $contractData = $contract->data;
            
            // Déterminer le template à utiliser
            $templateView = $isAvenant ? 'pdf.avenant-template' : 'pdf.cdi-template';
            if (!view()->exists($templateView)) {
                Log::warning('Template spécifié non trouvé, utilisation du template par défaut', [
                    'template_demandé' => $templateView,
                    'template_utilisé' => 'temp_fixes.contract-pdf'
                ]);
                $templateView = 'temp_fixes.contract-pdf';
            }
            
            // Si les signatures ne sont pas déjà définies, les préparer
            $signatureHelper = new SignatureHelper();
            
            // Forcer l'affichage des signatures pour les contrats complétés
            if ($contract->status === 'completed') {
                $contract->show_admin_signature = true;
                $contract->show_employee_signature = true;
                
                // Charger les signatures depuis le helper si elles ne sont pas déjà définies
                if (empty($contract->adminSignatureBase64)) {
                    $adminId = $contract->admin_id ?? Auth::id();
                    $contract->adminSignatureBase64 = $signatureHelper->prepareSignatureForPdf('admin', $adminId);
                    Log::info('Signature admin chargée pour contrat completé', [
                        'contract_id' => $contract->id,
                        'admin_id' => $adminId
                    ]);
                }
                
                if (empty($contract->employeeSignatureBase64)) {
                    $employeeId = $contract->user_id;
                    $contract->employeeSignatureBase64 = $signatureHelper->prepareSignatureForPdf('employee', $employeeId);
                    Log::info('Signature employé chargée pour contrat completé', [
                        'contract_id' => $contract->id,
                        'employee_id' => $employeeId
                    ]);
                }
            }
            
            // Préparer la signature admin seulement si le contrat est signé par l'admin
            // ou si le contrat est en mode prévisualisation avec show_admin_signature=true
            if (isset($contract->show_admin_signature) && $contract->show_admin_signature) {
                $adminId = $contract->admin_id ?? Auth::id();
                $contract->adminSignatureBase64 = $signatureHelper->prepareSignatureForPdf('admin', $adminId);
            }
            
            // En prévisualisation, inclure les signatures si l'option est activée
            if ($isPreview) {
                // En prévisualisation, toujours afficher la signature admin
                $contract->show_admin_signature = true;
                Log::info('Signature admin activée en prévisualisation', [
                    'contract_id' => $contract->id ?? 'temp'
                ]);
                
                // S'assurer que nous avons une signature admin
                $adminId = $contract->admin_id ?? Auth::id();
                if (empty($contract->adminSignatureBase64)) {
                    $contract->adminSignatureBase64 = $signatureHelper->prepareSignatureForPdf('admin', $adminId);
                    Log::info('Signature admin préparée pour la prévisualisation', [
                        'admin_id' => $adminId,
                        'signature_length' => strlen($contract->adminSignatureBase64 ?? '')
                    ]);
                }
                
                // Pour l'employé, seulement si demandé ou déjà signé
                if (!empty($contract->employeeSignatureBase64) || 
                    (isset($contract->show_employee_signature) && $contract->show_employee_signature) ||
                    $contract->status === 'employee_signed' || 
                    $contract->status === 'completed') {
                    
                    // Préparer la signature de l'employé
                    $employeeId = $user->id;
                    $contract->employeeSignatureBase64 = $signatureHelper->prepareSignatureForPdf('employee', $employeeId, $contract->id);
                    $contract->show_employee_signature = true;
                }
                
                Log::info('Prévisualisation avec signatures', [
                    'admin_signature' => $contract->show_admin_signature ? 'visible' : 'masquée',
                    'employee_signature' => !empty($contract->employeeSignatureBase64) ? 'présente' : 'absente'
                ]);
            } else if (empty($contract->employeeSignatureBase64) && !$isPreview) {
                $employeeId = $user->id;
                $contract->employeeSignatureBase64 = $signatureHelper->prepareSignatureForPdf('employee', $employeeId, $contract->id);
            }
            
            // Récupérer les données de l'entreprise
            $company = $contract->company ?? CompanyInfo::first();
            
            // Améliorer les options PDF pour une meilleure qualité des signatures
            $pdfOptions = [
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'dpi' => 150,
                'defaultFont' => 'sans-serif',
                'margin_left' => 10,
                'margin_right' => 10,
                'margin_top' => 10,
                'margin_bottom' => 10,
                'margin_header' => 0,
                'margin_footer' => 0,
                'orientation' => 'portrait',
                'format' => 'A4',
            ];
            
            // Générer le HTML pour le PDF
            $html = view($templateView, [
                'contract' => $contract,
                'user' => $user,
                'data' => $contractData,
                'company' => $company,
                'isPreview' => $isPreview,
                'isAvenant' => $isAvenant,
                'parentContract' => $isAvenant ? $contract->parentContract : null,
            ])->render();

            // Créer le dossier temporaire si inexistant
            $tempDir = storage_path('app/temp');
            if (!File::exists($tempDir)) {
                File::makeDirectory($tempDir, 0755, true);
            }
            
            // Générer un nom de fichier unique
            $suffix = $isAvenant ? '-avenant-' . ($contract->avenant_number ?? '1') : '';
            $timestamp = now()->format('YmdHis');
            $filename = ($isPreview ? 'preview-' : '') . 
                       'contract-' . ($contract->id ?? 'temp') . 
                       $suffix . '-' . $timestamp . '.pdf';
            
            $filePath = $tempDir . '/' . $filename;
            
            // Générer le PDF
            $pdf = Pdf::loadHTML($html);
            $pdf->setPaper('a4');
            $pdf->save($filePath);
            
            if (file_exists($filePath)) {
                Log::info('PDF généré avec succès', [
                    'contract_id' => $contract->id ?? 'temp',
                    'file_path' => $filePath,
                    'file_size' => filesize($filePath)
                ]);
                return $filePath;
            } else {
                Log::error('Échec de la sauvegarde du PDF', [
                    'contract_id' => $contract->id ?? 'temp',
                    'file_path' => $filePath
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de la génération du PDF', [
                'contract_id' => $contract->id ?? 'temp',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Tenter de créer un PDF d'erreur pour indiquer le problème
            try {
                $errorFilePath = storage_path('app/temp/error-pdf-' . now()->format('YmdHis') . '.pdf');
                $errorPdf = Pdf::loadHTML('<h1>Erreur de génération du PDF</h1><p>' . $e->getMessage() . '</p>');
                $errorPdf->save($errorFilePath);
                Log::info('PDF d\'erreur généré', ['file_path' => $errorFilePath]);
                return $errorFilePath;
            } catch (\Exception $innerException) {
                Log::error('Impossible de générer même un PDF d\'erreur', [
                    'error' => $innerException->getMessage()
                ]);
                return false;
            }
        }
    }
    
    /**
     * Génère un PDF minimal de secours en cas d'échec de la génération normale
     */
    private function generateFallbackPdf(Contract $contract)
    {
        Log::info('Tentative de génération d\'un PDF de secours');
        
        // Créer le répertoire si nécessaire
        $this->createDirectoryIfNotExists('temp');
        
        // Créer un HTML minimal
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Contrat - Prévisualisation (secours)</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .error { color: red; padding: 20px; border: 1px solid red; margin: 20px 0; }
                .contract-info { margin-bottom: 20px; }
            </style>
        </head>
        <body>
            <h1>Prévisualisation du contrat (version de secours)</h1>
            <div class="error">
                <h2>Erreur lors de la génération du PDF</h2>
                <p>Le système n\'a pas pu générer la prévisualisation complète du contrat. Voici une version simplifiée.</p>
            </div>
            <div class="contract-info">
                <p><strong>ID du contrat:</strong> ' . $contract->id . '</p>
                <p><strong>Titre:</strong> ' . $contract->title . '</p>
                <p><strong>Employé:</strong> ' . $contract->user->name . '</p>
                <p><strong>Statut:</strong> ' . $contract->status . '</p>
                <p><strong>Type:</strong> ' . ($contract->isAvenant() ? 'Avenant' : 'Contrat standard') . '</p>
                <p><strong>Créé le:</strong> ' . $contract->created_at . '</p>
            </div>
            <p>Veuillez contacter l\'administrateur système pour plus d\'informations.</p>
        </body>
        </html>';
        
        // Sauvegarder dans un fichier temporaire
        $filename = 'fallback_preview_' . $contract->id . '_' . time() . '.pdf';
        $pdfPath = storage_path('app/temp/' . $filename);
        
        // Générer le PDF
        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('a4');
        $pdf->save($pdfPath);
        
        // Copier vers le dossier previews pour référence future
        $this->createDirectoryIfNotExists('private/previews');
        $previewPath = 'private/previews/contract_' . $contract->id . '_preview.pdf';
        Storage::copy('temp/' . $filename, $previewPath);
        
        Log::info('PDF de secours généré', [
            'path' => $pdfPath
        ]);
        
        return $pdfPath;
    }
    
    /**
     * Prévisualise un contrat (PDF)
     */
    public function previewContract(Contract $contract)
    {
        try {
            Log::info('Début de la prévisualisation du contrat', [
                'contract_id' => $contract->id
            ]);
            
            // Augmenter le temps d'exécution maximum
            set_time_limit(300);
            ini_set('memory_limit', '512M');
            
            // S'assurer que les données de signature sont cohérentes avec le statut du contrat
            if (!isset($contract->show_admin_signature)) {
                // Par défaut, ne pas montrer la signature admin en prévisualisation
                $contract->show_admin_signature = false;
                
                // Sauf si le contrat est déjà signé par l'admin
                if ($contract->status === 'admin_signed' || $contract->status === 'employee_signed' || 
                    $contract->status === 'completed') {
                    $contract->show_admin_signature = true;
                }
            }
            
            // Vérifier si la signature admin existe en base64 et si elle doit être affichée
            if ($contract->show_admin_signature && (!isset($contract->adminSignatureBase64) || empty($contract->adminSignatureBase64))) {
                // Essayer de charger la signature admin
                $adminSignaturePath = storage_path('app/public/signatures/admin/admin_signature.png');
                if (file_exists($adminSignaturePath)) {
                    try {
                        $adminMime = mime_content_type($adminSignaturePath);
                        $adminData = file_get_contents($adminSignaturePath);
                        $contract->adminSignatureBase64 = 'data:' . $adminMime . ';base64,' . base64_encode($adminData);
                        
                        Log::info('Signature admin chargée pour prévisualisation', [
                            'path' => $adminSignaturePath,
                            'taille' => strlen($adminData)
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Erreur lors du chargement de la signature admin', [
                            'error' => $e->getMessage() 
                        ]);
                    }
                } else {
                    Log::warning('Fichier de signature admin introuvable', [
                        'path' => $adminSignaturePath
                    ]);
                }
            }
            
            // Nettoyer les anciennes prévisualisations pour ce contrat
            $this->cleanupOldFiles('private/previews', 'contract_' . $contract->id . '_preview.pdf');
            
            // Nettoyer les fichiers temporaires pour ce contrat
            $this->cleanupOldFiles('temp', 'preview_contract_' . $contract->id . '_*');
            $this->cleanupOldFiles('temp', 'debug_*_preview_contract_' . $contract->id . '_*.html');
            
            // Vérifier si un preview récent existe déjà
            $previewFilename = 'contract_' . $contract->id . '_preview.pdf';
            $previewPath = 'private/previews/' . $previewFilename;
            
            // Si le fichier existe et a été généré il y a moins de 5 minutes, l'utiliser
            if (Storage::exists($previewPath) && Storage::lastModified($previewPath) > (time() - 300)) {
                Log::info('Utilisation d\'une prévisualisation existante récente', [
                    'path' => $previewPath,
                    'age' => time() - Storage::lastModified($previewPath)
                ]);
                
                // Vérifier que le fichier est accessible
                $fullPath = storage_path('app/' . $previewPath);
                if (file_exists($fullPath) && filesize($fullPath) > 0) {
                    return response()->file($fullPath);
                } else {
                    Log::warning('Le fichier existe mais n\'est pas accessible', [
                        'path' => $fullPath,
                        'exists' => file_exists($fullPath),
                        'size' => file_exists($fullPath) ? filesize($fullPath) : 0
                    ]);
                }
            }
            
            // Sinon, générer un nouveau preview
            $pdfPath = $this->generateContractPdf($contract, true);
            
            if ($pdfPath && file_exists($pdfPath) && filesize($pdfPath) > 0) {
                Log::info('Prévisualisation générée avec succès', [
                    'path' => $pdfPath,
                    'size' => filesize($pdfPath)
                ]);
                return response()->file($pdfPath);
            } else {
                Log::error('Échec de la prévisualisation', [
                    'path' => $pdfPath,
                    'exists' => $pdfPath ? file_exists($pdfPath) : false,
                    'size' => ($pdfPath && file_exists($pdfPath)) ? filesize($pdfPath) : 0
                ]);
                return back()->with('error', 'Erreur lors de la génération du preview du contrat. Veuillez réessayer.');
            }
        } catch (Exception $e) {
            Log::error('Exception lors de la prévisualisation du contrat', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Erreur lors de la prévisualisation du contrat: ' . $e->getMessage());
        }
    }
    
    /**
     * Prépare les données spécifiques pour un avenant
     */
    private function prepareAvenantData(Contract $contract, array &$contractData)
    {
        // S'assurer que le contrat parent est chargé
        if ($contract->parentContract) {
            $parentData = $contract->parentContract->data;
        } else {
            $parentData = null;
        }
        
        $contractData['avenant_number'] = $contract->avenant_number;
        $contractData['employee_name'] = $contract->data->full_name ?? $contract->user->name;
        $contractData['employee_gender'] = $contract->data->gender ?? 'M';
        $contractData['contract_date'] = $parentData && $parentData->contract_signing_date 
            ? $parentData->contract_signing_date 
            : now();
        $contractData['effective_date'] = $contract->data->effective_date ?? now();
        $contractData['signing_date'] = $contract->data->contract_signing_date ?? now();
        $contractData['new_hours'] = $contract->data->work_hours ?? '';
        $contractData['new_salary'] = $contract->data->monthly_gross_salary ?? '';
        $contractData['monthly_hours'] = $contract->data->monthly_hours ?? '';
        
        Log::info('Données d\'avenant préparées', [
            'avenant_number' => $contractData['avenant_number'],
            'employee_name' => $contractData['employee_name'],
            'signing_date' => $contractData['signing_date'],
        ]);
    }
    
    /**
     * Crée un répertoire s'il n'existe pas
     */
    private function createDirectoryIfNotExists($directory)
    {
        $fullPath = storage_path('app/' . $directory);
        
        if (!file_exists($fullPath)) {
            $created = mkdir($fullPath, 0755, true);
            
            Log::info('Création du répertoire', [
                'directory' => $fullPath,
                'success' => $created
            ]);
        }
        
        // Assurer que Storage connaît ce répertoire
        if (!Storage::exists($directory)) {
            Storage::makeDirectory($directory);
        }
    }
    
    /**
     * Nettoie les anciens fichiers correspondant à un pattern
     * 
     * @param string $directory Le dossier à nettoyer
     * @param string $pattern Le pattern de fichier à supprimer
     * @param int $keepLatest Nombre de fichiers les plus récents à conserver (0 pour supprimer tous)
     * @return int Nombre de fichiers supprimés
     */
    private function cleanupOldFiles($directory, $pattern, $keepLatest = 1)
    {
        try {
            // S'assurer que le répertoire existe
            $this->createDirectoryIfNotExists($directory);
            
            // Récupérer tous les fichiers correspondant au pattern
            $files = [];
            
            if (strpos($pattern, '*') !== false) {
                // Si le pattern contient un wildcard, utiliser glob
                $globPattern = storage_path('app/' . $directory . '/' . $pattern);
                $matchedFiles = glob($globPattern);
                
                foreach ($matchedFiles as $file) {
                    if (is_file($file)) {
                        $files[] = [
                            'path' => $file,
                            'mtime' => filemtime($file)
                        ];
                    }
                }
            } else {
                // Si le pattern est un nom de fichier exact
                $filePath = storage_path('app/' . $directory . '/' . $pattern);
                if (file_exists($filePath) && is_file($filePath)) {
                    $files[] = [
                        'path' => $filePath,
                        'mtime' => filemtime($filePath)
                    ];
                }
            }
            
            // Trier les fichiers par date de modification (plus récent en premier)
            usort($files, function($a, $b) {
                return $b['mtime'] - $a['mtime'];
            });
            
            // Ne pas supprimer les N fichiers les plus récents
            $filesToDelete = array_slice($files, $keepLatest);
            
            // Supprimer les fichiers
            $deleted = 0;
            foreach ($filesToDelete as $file) {
                if (unlink($file['path'])) {
                    $deleted++;
                }
            }
            
            if ($deleted > 0) {
                Log::info("Nettoyage effectué dans {$directory}", [
                    'pattern' => $pattern,
                    'files_deleted' => $deleted,
                    'files_kept' => $keepLatest
                ]);
            }
            
            return $deleted;
        } catch (Exception $e) {
            Log::error("Erreur lors du nettoyage des fichiers dans {$directory}", [
                'pattern' => $pattern,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }
} 