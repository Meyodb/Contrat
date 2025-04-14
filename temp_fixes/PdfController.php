<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use App\Temp_Fixes\SignatureHelper;

class PdfController extends Controller
{
    /**
     * Génère un PDF de contrat
     *
     * @param Contract $contract Le contrat à convertir en PDF
     * @param bool $isPreview Si c'est une prévisualisation ou le document final
     * @return string|null Le chemin du fichier généré ou null en cas d'erreur
     */
    public function generateContractPdf(Contract $contract, bool $isPreview = false)
    {
        try {
            Log::info("Début de génération du PDF pour le contrat #{$contract->id}", [
                'contract_id' => $contract->id, 
                'isPreview' => $isPreview
            ]);
            
            // Charger les relations nécessaires
            $contract->load(['employee', 'employee.user', 'admin', 'admin.user']);
            
            // Préparer les données pour le template
            $data = $contract->employee;
            $admin = $contract->admin;
            $contractData = [
                'data' => $data,
                'admin' => $admin,
                'contract' => $contract
            ];
            
            // Utiliser SignatureHelper pour préparer les signatures
            $signatureHelper = new SignatureHelper();
            
            // Récupérer la signature de l'administrateur
            $adminSignatureBase64 = $signatureHelper->prepareSignatureForPdf('admin_signature.png');
            
            // Récupérer la signature de l'employé
            $employeeId = $contract->employee->user->id;
            $employeeSignatureBase64 = $signatureHelper->prepareSignatureForPdf('employee_signature.png', $employeeId);
            
            // Ajouter les signatures aux données du contrat
            $contractData['adminSignatureBase64'] = $adminSignatureBase64;
            $contractData['employeeSignatureBase64'] = $employeeSignatureBase64;
            
            // Ajouter date de génération
            $contractData['generatedAt'] = Carbon::now()->format('d/m/Y H:i:s');
            
            Log::info("Génération PDF - Signatures préparées", [
                'admin_signature' => !empty($adminSignatureBase64) ? 'Base64 présent (' . strlen($adminSignatureBase64) . ' octets)' : 'Manquante',
                'employee_signature' => !empty($employeeSignatureBase64) ? 'Base64 présent (' . strlen($employeeSignatureBase64) . ' octets)' : 'Manquante',
            ]);
            
            // Générer le HTML depuis le template Blade
            $html = View::make('temp_fixes.contract-pdf', $contractData)->render();
            
            // Sauvegarder le HTML pour debug si nécessaire
            Storage::disk('local')->put('debug/contract_' . $contract->id . '.html', $html);
            
            // Générer le PDF
            $pdf = Pdf::loadHTML($html);
            $pdf->setPaper('a4');
            $pdf->setWarnings(false);
            
            // Définir le nom du fichier en fonction du type (preview ou final)
            $fileName = $isPreview 
                ? 'previews/contract_' . $contract->id . '_preview.pdf'
                : 'contracts/contract_' . $contract->id . '_' . strtolower(str_replace(' ', '_', $contract->employee->user->name)) . '.pdf';
            
            $storagePath = 'public/' . $fileName;
            
            // Sauvegarder le PDF
            $pdf->save(storage_path('app/' . $storagePath));
            
            // Vérifier que le PDF a bien été créé
            if (!Storage::exists($storagePath)) {
                Log::error("Erreur lors de la génération du PDF: fichier non créé", [
                    'contract_id' => $contract->id,
                    'path' => $storagePath
                ]);
                return null;
            }
            
            // Si ce n'est pas une prévisualisation, mettre à jour le statut du contrat
            if (!$isPreview) {
                $contract->status = 'signed';
                $contract->save();
                Log::info("Contrat #{$contract->id} marqué comme signé", ['contract_id' => $contract->id]);
            }
            
            Log::info("PDF généré avec succès", [
                'contract_id' => $contract->id,
                'file' => $fileName,
                'preview' => $isPreview
            ]);
            
            return $storagePath;
        } catch (\Exception $e) {
            Log::error("Erreur lors de la génération du PDF", [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }
    
    /**
     * Prévisualise un contrat en PDF
     * 
     * @param Contract $contract Le contrat à prévisualiser
     * @return mixed Le PDF à télécharger ou une réponse d'erreur
     */
    public function previewContract(Contract $contract)
    {
        // Vérifier si un PDF de prévisualisation existe déjà
        $previewPath = 'public/previews/contract_' . $contract->id . '_preview.pdf';
        
        // Si le PDF n'existe pas encore, le générer
        if (!Storage::exists($previewPath)) {
            $previewPath = $this->generateContractPdf($contract, true);
            
            if ($previewPath === null) {
                return response()->json(['error' => 'Impossible de générer le PDF'], 500);
            }
        }
        
        // Retourner le fichier PDF
        return response()->file(storage_path('app/' . $previewPath));
    }
    
    /**
     * Télécharge un contrat en PDF
     * 
     * @param Contract $contract Le contrat à télécharger
     * @return mixed Le PDF à télécharger ou une réponse d'erreur
     */
    public function downloadContract(Contract $contract)
    {
        // Construire le chemin du fichier PDF final
        $fileName = 'contracts/contract_' . $contract->id . '_' . strtolower(str_replace(' ', '_', $contract->employee->user->name)) . '.pdf';
        $filePath = 'public/' . $fileName;
        
        // Vérifier si le PDF existe déjà
        if (!Storage::exists($filePath)) {
            $filePath = $this->generateContractPdf($contract);
            
            if ($filePath === null) {
                return response()->json(['error' => 'Impossible de générer le PDF'], 500);
            }
        }
        
        // Nom du fichier pour le téléchargement
        $downloadName = 'Contrat_' . $contract->employee->user->name . '.pdf';
        
        // Retourner le fichier PDF pour téléchargement
        return response()->download(storage_path('app/' . $filePath), $downloadName);
    }
} 