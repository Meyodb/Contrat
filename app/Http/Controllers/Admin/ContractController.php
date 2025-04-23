<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\User;
use App\Models\ContractData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Temp_Fixes\SignatureHelper;
use App\Notifications\AvenantCreated;
use Carbon\Carbon;
use App\Http\Controllers\PdfController;
use App\Models\CompanyInfo;
use Illuminate\Support\Facades\Auth;

class ContractController extends Controller
{
    /**
     * Affiche la liste des contrats
     */
    public function index()
    {
        $contracts = Contract::with(['user', 'data'])->orderBy('created_at', 'desc')->paginate(10);
        return view('admin.contracts.index', compact('contracts'));
    }

    /**
     * Affiche les détails d'un contrat
     */
    public function show(Contract $contract)
    {
        $contract->load(['user', 'template', 'data']);
        return view('admin.contracts.show', compact('contract'));
    }

    /**
     * Affiche le formulaire de modification des informations administratives du contrat
     */
    public function edit(Contract $contract)
    {
        // S'assurer que les données du contrat sont chargées
        $contract->load(['template', 'data', 'user']);
        
        // Créer une entrée ContractData si elle n'existe pas encore
        if (!$contract->data) {
            ContractData::create([
                'contract_id' => $contract->id,
                'contract_signing_date' => now(), // Date de signature par défaut à aujourd'hui
                'trial_period_months' => 1 // Période d'essai par défaut à 1 mois
            ]);
            // Recharger la relation
            $contract->refresh();
        } else {
            // Si les données existent mais que ces champs sont vides, les initialiser
            if (!$contract->data->contract_signing_date) {
                $contract->data->contract_signing_date = now();
            }
            if (!$contract->data->trial_period_months) {
                $contract->data->trial_period_months = 1;
            }
            $contract->data->save();
        }

        return view('admin.contracts.edit', [
            'contract' => $contract
        ]);
    }

    /**
     * Met à jour les informations administratives du contrat
     */
    public function update(Request $request, Contract $contract)
    {
        // Valider les données du formulaire
        $validated = $request->validate([
            'data.work_hours' => 'required|numeric|min:0|max:999.99',
            'data.hourly_rate' => 'required|numeric|min:0|max:9999.99',
            'data.contract_start_date' => 'required|date',
            'data.contract_signing_date' => 'required|date',
            'data.trial_period_months' => 'required|integer|min:0|max:12',
            // Informations personnelles modifiables
            'data.first_name' => 'nullable|string|max:255',
            'data.last_name' => 'nullable|string|max:255',
            'data.gender' => 'nullable|in:M,F',
            'data.address' => 'nullable|string|max:255',
            'data.postal_code' => 'nullable|string|max:20',
            'data.city' => 'nullable|string|max:100',
            'data.phone' => 'nullable|string|max:20',
            'data.email' => 'nullable|email|max:255',
            'data.nationality' => 'nullable|string|max:100',
            'data.social_security_number' => 'nullable',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
        
        // Préparer les données à mettre à jour
        $dataToUpdate = [
            'work_hours' => $validated['data']['work_hours'],
            'hourly_rate' => $validated['data']['hourly_rate'],
            'contract_start_date' => $validated['data']['contract_start_date'],
            'contract_signing_date' => $validated['data']['contract_signing_date'],
            'trial_period_months' => $validated['data']['trial_period_months'],
        ];
        
        // Ajouter les informations personnelles si elles sont fournies
        $personalFields = ['first_name', 'last_name', 'gender', 'address', 'postal_code', 'city', 'phone', 'email', 'nationality'];
        foreach ($personalFields as $field) {
            if (isset($validated['data'][$field])) {
                $dataToUpdate[$field] = $validated['data'][$field];
            }
        }
        
        // Traiter la photo de profil si elle est fournie
        if ($request->hasFile('profile_photo')) {
            try {
                // Récupérer le fichier
                $photo = $request->file('profile_photo');
                $photoName = 'profile_' . $contract->user->id . '_' . time() . '.' . $photo->getClientOriginalExtension();
                
                // Méthode 1: utiliser Storage::putFileAs qui gère automatiquement les permissions
                $photoPath = 'profile-photos/' . $photoName;
                Storage::disk('public')->putFileAs('profile-photos', $photo, $photoName);
                
                // En cas d'échec de la première méthode, essayer une approche alternative
                if (!Storage::disk('public')->exists($photoPath)) {
                    // Méthode 2: utiliser le contenu de l'image directement
                    $imageContent = file_get_contents($photo->getRealPath());
                    Storage::disk('public')->put($photoPath, $imageContent);
                    
                    \Log::info('Admin: Photo sauvegardée avec la méthode 2 (contenu)', [
                        'path' => $photoPath,
                        'size' => strlen($imageContent)
                    ]);
                } else {
                    \Log::info('Admin: Photo sauvegardée avec la méthode 1 (putFileAs)', [
                        'path' => $photoPath
                    ]);
                }
                
                // Vérifier que le fichier a bien été sauvegardé
                if (Storage::disk('public')->exists($photoPath)) {
                    // Mettre à jour le champ profile_photo_path de l'utilisateur
                    if ($contract->user) {
                        $contract->user->update([
                            'profile_photo_path' => $photoPath
                        ]);
                        \Log::info('Admin: Photo de profil utilisateur mise à jour avec succès: ' . $photoName);
                        
                        // Flash message pour informer l'administrateur
                        session()->flash('info', 'La photo de profil a été mise à jour avec succès.');
                    }
                } else {
                    // Méthode 3: sauvegarder dans un dossier public directement
                    $publicDir = public_path('photos');
                    if (!file_exists($publicDir)) {
                        mkdir($publicDir, 0777, true);
                    }
                    
                    $publicPath = $publicDir . '/' . $photoName;
                    move_uploaded_file($photo->getRealPath(), $publicPath);
                    
                    if (file_exists($publicPath)) {
                        if ($contract->user) {
                            $contract->user->update([
                                'profile_photo_path' => 'photos/' . $photoName
                            ]);
                            \Log::info('Admin: Photo sauvegardée avec la méthode 3 (public dir)', [
                                'path' => 'photos/' . $photoName
                            ]);
                            
                            session()->flash('info', 'La photo de profil a été mise à jour avec succès (méthode alternative).');
                        }
                    } else {
                        \Log::error('Admin: Impossible de sauvegarder la photo avec toutes les méthodes');
                        session()->flash('error', 'Impossible de sauvegarder la photo de profil. Veuillez essayer une autre méthode.');
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Admin: Erreur lors de l\'upload de la photo: ' . $e->getMessage(), [
                    'exception' => $e
                ]);
                session()->flash('error', 'Une erreur est survenue lors de l\'upload de la photo: ' . $e->getMessage());
            }
        }
        
        // Récupérer ou créer l'entrée ContractData
        $contractData = ContractData::updateOrCreate(
            ['contract_id' => $contract->id],
            $dataToUpdate
        );
        
        // Calculer les champs dérivés
        $this->calculateDerivedFields($contractData);
        
        // Préparer les informations pour la notification
        $changes = [
            'Heures de travail' => $validated['data']['work_hours'] . ' heures',
            'Taux horaire' => $validated['data']['hourly_rate'] . ' €',
            'Date de début' => date('d/m/Y', strtotime($validated['data']['contract_start_date'])),
            'Période d\'essai' => $validated['data']['trial_period_months'] . ' mois'
        ];
        
        // Envoyer une notification à l'employé
        $contract->user->notify(new \App\Notifications\ContractUpdated($contract, $changes));
        
        // Rediriger vers la page du contrat avec un message de succès
        return redirect()->route('admin.contracts.show', $contract)
            ->with('status', 'Les informations du contrat ont été mises à jour avec succès.');
    }

    /**
     * Calcule les champs dérivés à partir des données de base
     */
    private function calculateDerivedFields($contractData)
    {
        // Les heures de travail sont maintenant directement hebdomadaires
        // Calculer les heures mensuelles (work_hours / 5 * 21.6)
        if ($contractData->work_hours) {
            $contractData->weekly_hours = $contractData->work_hours;
            $contractData->monthly_hours = round($contractData->work_hours / 5 * 21.6, 2);
            
            // Calculer les heures supplémentaires hebdomadaires (20% des heures hebdomadaires)
            $contractData->weekly_overtime = round($contractData->weekly_hours * 0.2, 2);
            // Calculer les heures supplémentaires mensuelles (weekly_overtime * 4)
            $contractData->monthly_overtime = round($contractData->weekly_overtime * 4, 2);
        }
        
        // Calculer le salaire brut mensuel (hourly_rate * monthly_hours)
        if ($contractData->hourly_rate && $contractData->monthly_hours) {
            $contractData->monthly_gross_salary = round($contractData->hourly_rate * $contractData->monthly_hours, 2);
        }
        
        // Calculer la date de fin de période d'essai
        if ($contractData->contract_start_date && $contractData->trial_period_months) {
            // Convertir trial_period_months en entier
            $trialMonths = intval($contractData->trial_period_months);
            
            // Calculer la date de fin de période d'essai
            $contractData->trial_period_end_date = $contractData->contract_start_date->copy()
                ->addMonths($trialMonths)
                ->subDay();
        }
        
        // Calculer le nom complet
        if (isset($contractData->first_name) && isset($contractData->last_name)) {
            $contractData->full_name = $contractData->first_name . ' ' . $contractData->last_name;
        }
        
        // Sauvegarder les modifications
        $contractData->save();
    }

    /**
     * Affiche le formulaire de signature pour un contrat
     * Mais redirige directement vers la signature automatique
     */
    public function showSignForm(Contract $contract)
    {
        // Rediriger directement vers la signature automatique
        return $this->sign(request(), $contract);
    }

    /**
     * Signe le contrat (administrateur) - automatiquement
     */
    public function sign(Request $request, Contract $contract)
    {
        // Vérifier que l'utilisateur est un administrateur
        if (!auth()->user()->is_admin) {
            return redirect()->route('home')->with('status', 'Vous n\'êtes pas autorisé à signer ce contrat.');
        }
        
        // Vérifier que le contrat est signable
        if (!in_array($contract->status, ['submitted', 'draft', 'approved'])) {
            return redirect()->route('admin.contracts.show', $contract)
                ->with('error', 'Ce contrat ne peut pas être signé pour le moment.');
        }
        
        try {
            // Utiliser SignatureHelper pour gérer la signature
            $signatureHelper = new \App\Temp_Fixes\SignatureHelper();
            
            // Vérifier si la signature admin existe déjà, sinon la créer
            $adminSignature = $signatureHelper->prepareSignatureForPdf('admin');
            
            if (!$adminSignature) {
                \Log::error('Erreur lors de la génération ou récupération de la signature admin');
                return redirect()->route('admin.contracts.show', $contract)
                    ->with('error', 'Une erreur est survenue avec la signature administrateur.');
            }
            
            // L'URL est maintenant toujours la même
            $signaturePath = 'signatures/admin_signature.png';
            
            // Mettre à jour le contrat avec la signature de l'administrateur
            $contract->update([
                'admin_signature' => $signaturePath,
                'admin_signed_at' => now(),
                'status' => 'admin_signed'
            ]);
            
            // Log pour debugger
            \Log::info('Contrat #' . $contract->id . ' signé par admin, statut mis à jour: ' . $contract->status);
            
            // Notification à l'employé que le contrat a été signé par l'administrateur
            $contract->user->notify(new \App\Notifications\ContractSignedByAdmin($contract));
            
            return redirect()->route('admin.contracts.show', $contract)
                ->with('success', 'Contrat signé avec succès');
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la signature du contrat: ' . $e->getMessage());
            return redirect()->route('admin.contracts.show', $contract)
                ->with('error', 'Une erreur est survenue lors de la signature du contrat: ' . $e->getMessage());
        }
    }

    /**
     * Génère le document final du contrat
     */
    public function generate(Request $request, Contract $contract)
    {
        // Validate that contract is in the appropriate state
        if ($contract->status !== 'approved' && $contract->status !== 'admin_signed' && $contract->status !== 'employee_signed') {
            return redirect()->route('admin.contracts.show', $contract)
                ->with('error', 'Le contrat doit être approuvé ou signé avant de pouvoir être généré.');
        }
        
        try {
            // Nettoyer les anciennes versions des contrats et fichiers temporaires
            $pdfController = new \App\Http\Controllers\PdfController();
            
            // Format NOM_PRENOM_CONTRAT ou NOM_PRENOM_AVENANT_N
        $user = $contract->user;
        $nameParts = explode(' ', trim($user->name));
        $lastName = strtoupper(array_shift($nameParts));
        $firstName = implode('_', array_map('ucfirst', $nameParts));
                
            if ($contract->isAvenant()) {
                $filename = $lastName . '_' . $firstName . '_AVENANT_' . $contract->avenant_number . '.pdf';
                // Supprimer les anciennes versions de cet avenant
                $pattern = $lastName . '_' . $firstName . '_AVENANT_' . $contract->avenant_number . '.pdf';
                $pdfController->cleanupOldFiles('contracts', $pattern, 0); // 0 pour tout supprimer
            } else {
                $filename = $lastName . '_' . $firstName . '_CONTRAT.pdf';
                // Supprimer les anciennes versions de ce contrat
                $pattern = $lastName . '_' . $firstName . '_CONTRAT.pdf';
                $pdfController->cleanupOldFiles('contracts', $pattern, 0); // 0 pour tout supprimer
            }
            
            // Nettoyer les fichiers temporaires associés
            $pdfController->cleanupOldFiles('temp', 'preview_contract_' . $contract->id . '_*');
            $pdfController->cleanupOldFiles('temp', 'debug_*_preview_contract_' . $contract->id . '_*.html');
            
            // Ensure the contracts directory exists with correct permissions
            $contractsDir = storage_path('app/private/contracts');
            if (!file_exists($contractsDir)) {
                if (!mkdir($contractsDir, 0755, true)) {
                    throw new \Exception('Impossible de créer le répertoire des contrats: ' . $contractsDir);
                }
                \Log::info('Répertoire des contrats créé: ' . $contractsDir);
            }
            
            // Vérifier si le répertoire a bien été créé et est accessible en écriture
            if (!is_dir($contractsDir)) {
                throw new \Exception('Le répertoire des contrats n\'existe pas: ' . $contractsDir);
            }
            
            if (!is_writable($contractsDir)) {
                // Tenter de modifier les permissions
                chmod($contractsDir, 0755);
                if (!is_writable($contractsDir)) {
                    throw new \Exception('Le répertoire des contrats n\'est pas accessible en écriture: ' . $contractsDir);
                }
            }
            
            // Créer aussi le répertoire temp s'il n'existe pas
            $tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) {
                if (!mkdir($tempDir, 0755, true)) {
                    throw new \Exception('Impossible de créer le répertoire temporaire: ' . $tempDir);
                }
                \Log::info('Répertoire temporaire créé: ' . $tempDir);
            }
            
            // Vérifier que les fichiers de signature existent
            $adminSignaturePath = storage_path('app/public/signatures/admin_signature.png');
            $employeeSignaturePath = $contract->user_id ? storage_path('app/public/signatures/' . $contract->user_id . '_employee.png') : null;
            
            \Log::info('Génération PDF - Vérification des signatures', [
                'admin_exists' => file_exists($adminSignaturePath),
                'admin_size' => file_exists($adminSignaturePath) ? filesize($adminSignaturePath) : 0,
                'employee_exists' => $employeeSignaturePath ? file_exists($employeeSignaturePath) : false,
                'employee_size' => ($employeeSignaturePath && file_exists($employeeSignaturePath)) ? filesize($employeeSignaturePath) : 0
            ]);
            
            // Si la signature de l'employé existe, créer une copie avec un nom standardisé
            $employeeSignatureFilename = null;
            if ($employeeSignaturePath && file_exists($employeeSignaturePath)) {
                $employeeSignatureFilename = 'employee_signature_' . time() . '.png';
                $employeeSignatureTempPath = storage_path('app/public/signatures/' . $employeeSignatureFilename);
                
                if (copy($employeeSignaturePath, $employeeSignatureTempPath)) {
                    chmod($employeeSignatureTempPath, 0777);
                    \Log::info('Signature employé copiée pour le PDF', [
                        'source' => $employeeSignaturePath,
                        'destination' => $employeeSignatureTempPath
                    ]);
                } else {
                    \Log::error('Échec de la copie de la signature employé', [
                        'source' => $employeeSignaturePath
                    ]);
                }
            } else if ($contract->employee_signature) {
                // Si on a un chemin dans la base de données mais pas de fichier, tenter avec ce chemin
                $dbPath = $contract->employee_signature;
                $dbFilePath = storage_path('app/public/' . str_replace('signatures/', '', $dbPath));
                
                \Log::info('Tentative alternative pour signature employé', [
                    'db_path' => $dbPath,
                    'file_path' => $dbFilePath,
                    'exists' => file_exists($dbFilePath)
                ]);
                
                if (file_exists($dbFilePath)) {
                    $employeeSignatureFilename = 'employee_signature_' . time() . '.png';
                    $employeeSignatureTempPath = storage_path('app/public/signatures/' . $employeeSignatureFilename);
                    
                    if (copy($dbFilePath, $employeeSignatureTempPath)) {
                        chmod($employeeSignatureTempPath, 0777);
                        \Log::info('Signature employé copiée depuis chemin DB pour le PDF', [
                            'source' => $dbFilePath,
                            'destination' => $employeeSignatureTempPath
                        ]);
                    }
                }
            }
            
            // Préparer les données pour le template
            $contractData = [
                'contract' => $contract,
                'user' => $contract->user,
                'admin' => $contract->admin ?? auth()->user(),
                'data' => $contract->data,
            ];
            
            // Utiliser SignatureHelper pour préparer les signatures
            $signatureHelper = new SignatureHelper();
            
            // Récupérer la signature de l'administrateur
            $adminSignatureBase64 = $signatureHelper->prepareSignatureForPdf('admin_signature.png');
            
            // Récupérer la signature de l'employé
            $employeeId = $contract->user_id;
            $employeeSignatureBase64 = $signatureHelper->prepareSignatureForPdf($employeeId . '_employee.png', $employeeId);
            
            // Ajouter les signatures aux données du contrat
            $contractData['adminSignatureBase64'] = $adminSignatureBase64;
            $contractData['employeeSignatureBase64'] = $employeeSignatureBase64;
            
            // Ajouter date de génération
            $contractData['generatedAt'] = now()->format('d/m/Y H:i:s');
            
            // Pour les avenants, ajouter les données spécifiques
            if ($contract->isAvenant()) {
                $contractData['avenant_number'] = $contract->avenant_number;
                $contractData['employee_name'] = $contract->data->full_name ?? $contract->user->name;
                $contractData['employee_gender'] = $contract->data->gender ?? 'M';
                $contractData['contract_date'] = $contract->parentContract->data->contract_signing_date ?? now();
                $contractData['effective_date'] = $contract->data->effective_date ?? now();
                $contractData['signing_date'] = $contract->data->contract_signing_date ?? now();
                $contractData['new_hours'] = $contract->data->work_hours ?? '';
                $contractData['new_salary'] = $contract->data->monthly_gross_salary ?? '';
                $contractData['monthly_hours'] = $contract->data->monthly_hours ?? '';
                
                // Générer le HTML avec le template d'avenant
                $html = view('pdf.avenant-template', $contractData)->render();
            } else {
                // Générer le HTML avec le template Blade
                $html = view('temp_fixes.contract-pdf', $contractData)->render();
            }
            
            // Pour débogage, sauvegarder le HTML généré
            $htmlDebugDir = storage_path('app/temp');
            if (!file_exists($htmlDebugDir)) {
                mkdir($htmlDebugDir, 0755, true);
            }
            $htmlDebugPath = $htmlDebugDir . '/debug_' . $filename . '.html';
            file_put_contents($htmlDebugPath, $html);
            \Log::info('Génération PDF - HTML sauvegardé pour débogage', ['path' => $htmlDebugPath]);
            
            // Créer le PDF
            $pdf = Pdf::loadHTML($html);
            $pdf->setPaper('a4');
            
            // Sauvegarder le PDF
            $pdfPath = storage_path('app/private/contracts/' . $filename);
            \Log::info('Tentative de sauvegarde du PDF', ['path' => $pdfPath]);
            
            try {
        $pdf->save($pdfPath);
                \Log::info('PDF sauvegardé avec succès', ['path' => $pdfPath, 'size' => filesize($pdfPath)]);
            } catch (\Exception $e) {
                \Log::error('Erreur lors de la sauvegarde du PDF', [
                    'path' => $pdfPath,
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }
            
            // Vérifier que le fichier a bien été créé
            if (!file_exists($pdfPath)) {
                $errorMsg = 'Le fichier PDF n\'a pas été créé: ' . $pdfPath;
                \Log::error($errorMsg);
                throw new \Exception($errorMsg);
            }
            
            // Verification du fichier généré
            $fileSize = filesize($pdfPath);
            if ($fileSize <= 0) {
                $errorMsg = 'Le fichier PDF a été créé mais est vide (0 octets): ' . $pdfPath;
                \Log::error($errorMsg);
                throw new \Exception($errorMsg);
            }
        
        // Update the contract with the PDF path and change status
            $contract->final_document_path = 'contracts/' . $filename;
        $contract->generated_at = now();
        
        // Si le contrat est signé par les deux parties, marquer comme complété
        if ($contract->admin_signature && $contract->employee_signature) {
            $contract->status = 'completed';
            $contract->completed_at = now();
        } else {
            $contract->status = 'generated';
        }
        
        $contract->save();
            
            \Log::info('Contrat généré et enregistré avec succès', [
                'contract_id' => $contract->id,
                'path' => $contract->final_document_path,
                'size' => $fileSize,
                'status' => $contract->status
            ]);

        return redirect()->route('admin.contracts.show', $contract)
                ->with('success', 'Le document a été généré avec succès.');
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la génération du contrat: ' . $e->getMessage(), [
                'contract_id' => $contract->id,
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('admin.contracts.show', $contract)
                ->with('error', 'Une erreur est survenue lors de la génération du contrat: ' . $e->getMessage());
        }
    }

    private function createDefaultTemplate($templatePath)
    {
        // Assurer que le répertoire existe
        $directory = dirname($templatePath);
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }
        
        // Créer un nouveau document PHPWord
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        
        // Ajouter du contenu par défaut avec des placeholders
        $section->addText("CONTRAT À DURÉE INDÉTERMINÉE (CDI)", ['bold' => true, 'size' => 16], ['alignment' => 'center']);
        $section->addTextBreak(2);
        $section->addText("Entre les soussignés :");
        $section->addTextBreak();
        $section->addText("La société ABC, représentée par M. Directeur, d'une part,");
        $section->addTextBreak();
        $section->addText("Et");
        $section->addTextBreak();
        $section->addText("{{USER_NAME}}, demeurant à [Adresse], d'autre part,");
        $section->addTextBreak();
        $section->addText("Il a été convenu ce qui suit :");
        $section->addTextBreak();
        $section->addText("Article 1 - Engagement");
        $section->addText("{{USER_NAME}} est engagé(e) à compter du [Date] en qualité de [Poste].");
        $section->addTextBreak();
        $section->addText("Article 2 - Rémunération");
        $section->addText("La rémunération brute mensuelle est fixée à [Montant] euros pour un horaire hebdomadaire de 35 heures.");
        
        // Sauvegarder le document
        $phpWord->save($templatePath);
    }

    private function replacePlaceholdersInDocument($phpWord, $placeholders)
    {
        // Replace in all sections
        foreach ($phpWord->getSections() as $section) {
            // Replace in all elements that might contain text
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getElements')) {
                    foreach ($element->getElements() as $childElement) {
                        if (method_exists($childElement, 'setText') && method_exists($childElement, 'getText')) {
                            $text = $childElement->getText();
                            foreach ($placeholders as $search => $replace) {
                                $text = str_replace($search, $replace, $text);
                            }
                            $childElement->setText($text);
                        }
                    }
                }
            }
        }
    }

    /**
     * Rejette un contrat
     */
    public function reject(Request $request, Contract $contract)
    {
        $validated = $request->validate([
            'admin_notes' => 'required|string',
        ]);

        $contract->update([
            'status' => 'rejected',
            'admin_notes' => $validated['admin_notes']
        ]);

        // Notification à l'employé que le contrat a été rejeté
        $contract->user->notify(new \App\Notifications\ContractRejected($contract, $validated['admin_notes']));

        return redirect()->route('admin.contracts.index')
            ->with('success', 'Contrat rejeté');
    }

    /**
     * Prévisualise un contrat (génère un aperçu PDF)
     * 
     * @param Contract $contract Le contrat à prévisualiser
     * @return \Illuminate\Http\Response
     */
    public function preview(Contract $contract)
    {
        try {
            // Vérifier que l'utilisateur est bien autorisé
            if (!auth()->user()->is_admin) {
                return back()->with('error', 'Vous n\'êtes pas autorisé à prévisualiser ce contrat.');
            }
            
            \Log::info('Demande de prévisualisation d\'un contrat par un administrateur', [
                'contract_id' => $contract->id,
                'admin_id' => auth()->id(),
                'contract_type' => $contract->contract_type,
            ]);
            
            // Utiliser SignatureHelper pour préparer la signature de l'admin
            $signatureHelper = new \App\Temp_Fixes\SignatureHelper();
            
            // S'assurer que la signature admin existe
            $adminSignaturePath = storage_path('app/public/signatures/admin/admin_signature.png');
            if (!file_exists($adminSignaturePath)) {
                \Log::info('Création de la signature admin car elle n\'existe pas');
                $signatureHelper->createAdminSignature();
            }
            
            // Forcer l'affichage de la signature admin en prévisualisation
            $contract->show_admin_signature = true;
            $contract->admin_signed_at = $contract->admin_signed_at ?? now();
            
            // Si le contrat est dans l'état "completed", forcer aussi l'affichage de la signature employé
            if ($contract->status === 'completed' || $contract->status === 'employee_signed') {
                $contract->show_employee_signature = true;
                
                // Générer la signature de l'employé en base64 pour le PDF
                $signatureHelper = new \App\Temp_Fixes\SignatureHelper();
                $employeeId = $contract->user_id;
                $contract->employeeSignatureBase64 = $signatureHelper->prepareSignatureForPdf('employee', $employeeId);
                
                \Log::info('Signature employé générée pour prévisualisation admin', [
                    'contract_id' => $contract->id,
                    'employee_id' => $employeeId,
                    'status' => $contract->status
                ]);
            }
            
            // Générer directement la signature admin en base64 pour le PDF
            try {
                if (file_exists($adminSignaturePath)) {
                    $adminMime = mime_content_type($adminSignaturePath);
                    $adminData = file_get_contents($adminSignaturePath);
                    $contract->adminSignatureBase64 = 'data:' . $adminMime . ';base64,' . base64_encode($adminData);
                    
                    // Vérifier si la signature est correctement chargée et non vide
                    if (empty($contract->adminSignatureBase64) || strlen($contract->adminSignatureBase64) < 100) {
                        \Log::error('Signature admin vide ou trop petite', [
                            'path' => $adminSignaturePath,
                            'size' => strlen($adminData),
                            'base64_length' => strlen($contract->adminSignatureBase64 ?? '')
                        ]);
                        
                        // Fallback - utiliser SignatureHelper pour générer une nouvelle signature
                        $contract->adminSignatureBase64 = $signatureHelper->prepareSignatureForPdf('admin', auth()->id());
                        \Log::info('Utilisation d\'une signature admin de secours générée par SignatureHelper');
                    } else {
                        \Log::info('Signature admin convertie en base64 pour prévisualisation admin', [
                            'path' => $adminSignaturePath,
                            'size' => strlen($adminData)
                        ]);
                    }
                } else {
                    \Log::error('Signature admin introuvable', ['path' => $adminSignaturePath]);
                    // Fallback - utiliser SignatureHelper pour générer une nouvelle signature
                    $contract->adminSignatureBase64 = $signatureHelper->prepareSignatureForPdf('admin', auth()->id());
                    \Log::info('Utilisation d\'une signature admin de secours générée par SignatureHelper');
                }
            } catch (\Exception $e) {
                \Log::error('Erreur lors de la conversion de la signature admin', [
                    'error' => $e->getMessage()
                ]);
                // Fallback - utiliser SignatureHelper pour générer une nouvelle signature
                $contract->adminSignatureBase64 = $signatureHelper->prepareSignatureForPdf('admin', auth()->id());
                \Log::info('Utilisation d\'une signature admin de secours générée par SignatureHelper après exception');
            }
            
            // Utiliser le contrôleur PDF pour générer la prévisualisation
            $pdfController = new \App\Http\Controllers\PdfController();
            $response = $pdfController->previewContract($contract);
            
            return $response;
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la prévisualisation du contrat:', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Erreur lors de la prévisualisation du contrat: ' . $e->getMessage());
        }
    }

    /**
     * Permet le téléchargement du document final du contrat
     */
    public function download(Contract $contract)
    {
        // Vérifier que l'utilisateur est autorisé à télécharger ce contrat
        if (!auth()->user()->is_admin && auth()->id() !== $contract->user_id) {
            return redirect()->route('home')->with('status', 'Vous n\'êtes pas autorisé à télécharger ce contrat.');
        }
        
        try {
            // Augmenter le temps d'exécution maximum pour éviter les timeouts
            set_time_limit(300);
            ini_set('memory_limit', '512M');
            
            // Si le contrat a déjà un document final et qu'il est accessible, le télécharger directement
            if ($contract->final_document_path && Storage::exists($contract->final_document_path)) {
                $path = storage_path('app/' . $contract->final_document_path);
                
                // Double vérification que le fichier existe physiquement
                if (file_exists($path)) {
                    \Log::info('Téléchargement du contrat existant', ['path' => $path]);
                    $filename = 'contrat_' . $contract->id . '_' . str_replace(' ', '_', $contract->user->name) . '.pdf';
                    
                    return response()->download($path, $filename, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'attachment; filename="' . $filename . '"'
                    ]);
                } else {
                    \Log::warning('Le fichier PDF existe dans la base de données mais pas physiquement', [
                        'contract_id' => $contract->id,
                        'path' => $path
                    ]);
                    // Continuer pour générer un nouveau fichier
                }
            } else {
                \Log::warning('Tentative de téléchargement d\'un contrat inexistant', [
                    'contract_id' => $contract->id,
                    'final_document_path' => $contract->final_document_path,
                    'exists' => $contract->final_document_path ? Storage::exists($contract->final_document_path) : false
                ]);
            }
            
            // Créer les répertoires nécessaires s'ils n'existent pas
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }
            
            if (!file_exists(storage_path('app/private/contracts'))) {
                mkdir(storage_path('app/private/contracts'), 0755, true);
            }
            
            // Générer un nom de fichier pour le document PDF
            $filename = 'contrat_' . $contract->id . '_' . str_replace(' ', '_', $contract->user->name ?? 'employe') . '.pdf';
            $finalPath = 'contracts/' . $filename;
            $finalFullPath = storage_path('app/' . $finalPath);
            
            // Si le contrat est un avenant, utiliser la méthode generate qui gère correctement les signatures
            if ($contract->isAvenant()) {
                // Appeler la méthode generate qui a déjà la logique complète de génération
                $result = $this->generate(request(), $contract);
                
                // Vérifier si le fichier a été généré avec succès
                if ($contract->final_document_path && Storage::exists($contract->final_document_path)) {
                    $path = storage_path('app/' . $contract->final_document_path);
                    $generatedFilename = basename($contract->final_document_path);
                    
                    \Log::info('Téléchargement du PDF généré', [
                        'path' => $path,
                        'exists' => file_exists($path)
                    ]);
                    
                    return response()->download($path, $generatedFilename, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'attachment; filename="' . $generatedFilename . '"'
                    ]);
                } else {
                    throw new \Exception('Le fichier n\'a pas pu être généré correctement.');
                }
            }
            
            // Si c'est un contrat standard, générer un document basique
            $phpWord = new \PhpOffice\PhpWord\PhpWord();
            
            // Définir les styles de base
            $phpWord->setDefaultFontName('Arial');
            $phpWord->setDefaultFontSize(11);

            // Ajouter une section
            $section = $phpWord->addSection([
                'marginTop' => 600,
                'marginLeft' => 600,
                'marginRight' => 600,
                'marginBottom' => 600
            ]);
            
            // Charger les données du contrat
            $contract->load(['user', 'data']);
            
            // Générer le contenu du contrat
            $section->addText('CONTRAT DE TRAVAIL', ['bold' => true, 'size' => 16], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            $section->addTextBreak(2);
            
            // Ajouter les informations du contrat
            if ($contract->data) {
                $fullName = $contract->data->full_name ?? $contract->user->name;
                $section->addText("Employé: $fullName");
                
                if ($contract->data->work_hours) {
                    $section->addText("Heures de travail: {$contract->data->work_hours} heures par semaine");
                }
                
                if ($contract->data->monthly_gross_salary) {
                    $section->addText("Salaire brut mensuel: {$contract->data->monthly_gross_salary} €");
                }
            }
            
            $tempWordPath = storage_path('app/temp/temp_word_' . time() . '.docx');
            $pdfPath = storage_path('app/temp/' . $filename);
            
            // Sauvegarder le document Word temporaire
            $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
            $objWriter->save($tempWordPath);
            
            // Configurer le moteur de rendu PDF
            Settings::setPdfRendererName(Settings::PDF_RENDERER_DOMPDF);
            Settings::setPdfRendererPath(base_path('vendor/dompdf/dompdf'));
            
            // Convertir en PDF
            $pdfWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'PDF');
            $pdfWriter->save($pdfPath);
            
            // Copier le fichier temporaire vers son emplacement final
            copy($pdfPath, $finalFullPath);
            
            // Mettre à jour le chemin dans la base de données
            $contract->final_document_path = $finalPath;
            $contract->generated_at = now();
            $contract->save();
            
            // Supprimer le fichier Word temporaire
            if (file_exists($tempWordPath)) {
                unlink($tempWordPath);
            }
            
            // Vérifier que le fichier final existe
            if (!file_exists($finalFullPath)) {
                throw new \Exception("Le fichier PDF n'a pas été correctement généré: " . $finalFullPath);
            }
            
            // Retourner le PDF pour téléchargement
            return response()->download($finalFullPath, $filename, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la génération du contrat: ' . $e->getMessage(), [
                'contract_id' => $contract->id,
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Erreur lors du téléchargement du contrat: ' . $e->getMessage());
        }
    }

    /**
     * Supprime un contrat
     */
    public function destroy(Contract $contract)
    {
        // Vérifier si le contrat a un document associé
        if ($contract->final_document_path && Storage::exists($contract->final_document_path)) {
            // Supprimer le fichier du disque
            Storage::delete($contract->final_document_path);
        }
        
        // Supprimer les données associées au contrat
        if ($contract->data) {
            $contract->data->delete();
        }
        
        // Supprimer le contrat
        $contract->delete();
        
        return redirect()->route('admin.contracts.index')
            ->with('success', 'Le contrat a été supprimé avec succès.');
    }

    /**
     * Supprime les coordonnées bancaires d'un contrat
     */
    public function deleteBankDetails(Contract $contract)
    {
        if ($contract->data && $contract->data->bank_details) {
            $contractData = $contract->data;
            $contractData->bank_details = null;
            $contractData->save();
            
            return redirect()->route('admin.contracts.show', $contract)
                ->with('success', 'Les coordonnées bancaires ont été supprimées avec succès.');
        }
        
        return redirect()->route('admin.contracts.show', $contract)
            ->with('error', 'Aucune coordonnée bancaire à supprimer.');
    }

    /**
     * Affiche le formulaire de création d'un avenant
     */
    public function showCreateAvenantForm(Contract $contract)
    {
        // Vérifier que le contrat est à un état qui permet de créer un avenant
        if (!in_array($contract->status, ['completed', 'employee_signed'])) {
            return redirect()->route('admin.contracts.show', $contract)
                ->with('error', 'Impossible de créer un avenant pour un contrat qui n\'est pas finalisé.');
        }
        
        // Obtenir le numéro du prochain avenant
        $nextAvenantNumber = Contract::getNextAvenantNumber($contract->id);
        
        return view('admin.contracts.create-avenant', compact('contract', 'nextAvenantNumber'));
    }
    
    /**
     * Enregistre un nouvel avenant
     */
    public function storeAvenant(Request $request, Contract $contract)
    {
        // Vérifier que le contrat est à un état qui permet de créer un avenant
        if (!in_array($contract->status, ['completed', 'employee_signed'])) {
            return redirect()->route('admin.contracts.show', $contract)
                ->with('error', 'Impossible de créer un avenant pour un contrat qui n\'est pas finalisé.');
        }
        
        // Valider les données de l'avenant
        $validated = $request->validate([
            'avenant_number' => 'required|string',
            'effective_date' => 'required|date',
            'signing_date' => 'required|date',
            'new_hours' => 'required|numeric|min:0|max:40',
            'new_salary' => 'required|numeric|min:0',
            'new_hourly_rate' => 'required|numeric|min:0',
            'motif' => 'nullable|string',
        ]);
        
        // Créer un nouvel avenant
        $avenant = new Contract([
            'user_id' => $contract->user_id,
            'admin_id' => auth()->id(),
            'parent_contract_id' => $contract->id,
            'title' => 'Avenant n°' . $validated['avenant_number'] . ' au contrat de ' . $contract->user->name,
            'avenant_number' => $validated['avenant_number'],
            'contract_type' => 'avenant',
            'status' => 'admin_signed', // L'avenant est signé seulement par l'administrateur
            'admin_signature' => 'signatures/admin_signature.png',
            'admin_signed_at' => now(),
            'employee_signature' => null, // Pas de signature employé
            'employee_signed_at' => null, // Pas de date de signature employé
            'completed_at' => null, // Pas de date de complétion
            'is_avenant' => true,
            'effective_date' => $validated['effective_date'],
            'signing_date' => $validated['signing_date'],
            'motif' => $validated['motif'] ?? 'Modification des conditions de travail',
            'contract_template_id' => $contract->contract_template_id,
        ]);
        
        $avenant->save();
        
        \Log::info('Avenant créé avec signature administrateur', [
            'avenant_id' => $avenant->id,
            'parent_contract_id' => $contract->id,
            'admin_signature' => $avenant->admin_signature,
            'status' => $avenant->status
        ]);
        
        // Créer les données de l'avenant
        $contractData = new ContractData([
            'contract_id' => $avenant->id,
            // Copier les données du contrat parent
            'full_name' => $contract->data->full_name ?? null,
            'first_name' => $contract->data->first_name ?? null,
            'last_name' => $contract->data->last_name ?? null,
            'gender' => $contract->data->gender ?? null,
            'birth_date' => $contract->data->birth_date ?? null,
            'birth_place' => $contract->data->birth_place ?? null,
            'nationality' => $contract->data->nationality ?? null,
            'address' => $contract->data->address ?? null,
            'postal_code' => $contract->data->postal_code ?? null,
            'city' => $contract->data->city ?? null,
            'social_security_number' => $contract->data->social_security_number ?? null,
            'email' => $contract->data->email ?? null,
            'phone' => $contract->data->phone ?? null,
            'bank_details' => $contract->data->bank_details ?? null,
            'photo_path' => $contract->data->photo_path ?? null,
            // Nouvelles valeurs de l'avenant
            'work_hours' => $validated['new_hours'],
            'hourly_rate' => $validated['new_hourly_rate'],
            'monthly_gross_salary' => $validated['new_salary'],
            'contract_start_date' => $contract->data->contract_start_date ?? null,
            'contract_signing_date' => $validated['signing_date'],
            'trial_period_months' => 0, // Pas de période d'essai pour un avenant
            // Informations spécifiques à l'avenant
            'effective_date' => $validated['effective_date'],
            'original_contract_date' => $contract->data->contract_signing_date ?? null,
        ]);
        
        $contractData->save();
        
        // Mettre à jour les champs calculés
        $this->calculateDerivedFields($contractData);
        
        // Notifier l'employé qu'un avenant est disponible pour signature
        $contract->user->notify(new \App\Notifications\AvenantCreated($avenant));
        
        return redirect()->route('admin.contracts.show', $avenant)
            ->with('success', 'L\'avenant a été créé avec succès. L\'employé doit maintenant le signer.');
    }

    /**
     * Prévisualise un avenant avant de le générer
     * 
     * @param Request $request
     * @param Contract $contract
     * @return \Illuminate\Http\Response
     */
    public function previewAvenant(Request $request)
    {
        try {
            // Pour la prévisualisation, récupérer ce qui est disponible sans validation stricte
            $avenantNumber = $request->input('avenant_number', 1);
            $effectiveDate = $request->input('effective_date', now()->format('Y-m-d'));
            $signingDate = $request->input('signing_date', now()->format('Y-m-d'));
            $newHours = $request->input('new_hours', 35);
            $newSalary = $request->input('new_salary', 1000);
            $newHourlyRate = $request->input('new_hourly_rate', 10);
            $motif = $request->input('motif', 'Modification des conditions de travail');
            
            // Récupérer le contrat parent depuis la route ou le formulaire
            $parentContractId = $request->route('contract')->id ?? $request->input('parent_contract_id');
            $parentContract = Contract::with(['user', 'data'])->findOrFail($parentContractId);
            
            \Log::info('Prévisualisation d\'avenant', [
                'parent_contract_id' => $parentContractId,
                'avenant_number' => $avenantNumber,
                'new_hours' => $newHours
            ]);
            
            // Vérifier que le template de l'avenant existe
            if (!view()->exists('pdf.avenant-template')) {
                \Log::error('Le template d\'avenant n\'existe pas', [
                    'template' => 'pdf.avenant-template',
                    'path' => resource_path('views/pdf/avenant-template.blade.php')
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Le template d\'avenant n\'a pas été trouvé. Veuillez contacter l\'administrateur.'
                ]);
            }

            // Créer un contrat temporaire pour la prévisualisation
            $contract = new Contract();
            $contract->user_id = $parentContract->user_id;
            $contract->admin_id = Auth::id();
            $contract->status = 'preview';
            $contract->contract_type = 'avenant';
            $contract->avenant_number = $avenantNumber;
            $contract->parent_contract_id = $parentContractId;
            $contract->user = $parentContract->user;
            $contract->parentContract = $parentContract;
            
            // Créer les données du contrat temporaire
            $contractData = new ContractData();
            $contractData->work_hours = $newHours;
            $contractData->monthly_gross_salary = $newSalary;
            $contractData->hourly_rate = $newHourlyRate;
            $contractData->effective_date = $effectiveDate;
            $contractData->contract_signing_date = $signingDate;
            $contractData->motif = $motif;
            
            // Associer les données au contrat
            $contract->data = $contractData;
            
            // Récupérer les informations de l'entreprise
            $company = CompanyInfo::first();
            $contract->company = $company;
            
            // Préparer les signatures
            $signatureHelper = new SignatureHelper();
            
            // Signature de l'administrateur (utilisateur connecté)
            $adminId = Auth::id();
            $contract->adminSignatureBase64 = $signatureHelper->prepareSignatureForPdf('admin', $adminId);
            
            // Signature de l'employé (du contrat parent)
            $employeeId = $parentContract->user_id;
            $contract->employeeSignatureBase64 = $signatureHelper->prepareSignatureForPdf('employee', $employeeId, $parentContract->id);
            
            // Générer le PDF en mode prévisualisation et en spécifiant qu'il s'agit d'un avenant
            $pdfPath = app(PdfController::class)->generateContractPdf($contract, true, true);
            
            if ($pdfPath && file_exists($pdfPath)) {
                // Au lieu de renvoyer un JSON, renvoyer directement le PDF
                return response()->file($pdfPath, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="' . basename($pdfPath) . '"'
                ]);
            } else {
                Log::error('Échec de génération de la prévisualisation de l\'avenant', [
                    'parent_contract_id' => $parentContractId,
                    'user_id' => $parentContract->user_id
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de générer la prévisualisation. Veuillez réessayer.'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de la prévisualisation de l\'avenant', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Affiche le formulaire pour créer un nouveau contrat
     */
    public function create()
    {
        // Récupérer tous les employés (utilisateurs qui ne sont pas administrateurs)
        $employees = User::where('is_admin', false)
            ->where('archived', false)
            ->get();
        
        return view('admin.contracts.create', compact('employees'));
    }

    /**
     * Traite la création d'un nouveau contrat
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'contract_type' => 'required|in:cdi,avenant',
        ]);
        
        $user = User::findOrFail($validated['user_id']);
        
        if ($validated['contract_type'] === 'cdi') {
            // Créer un nouveau contrat CDI
            $contract = new Contract([
                'user_id' => $user->id,
                'admin_id' => auth()->id(),
                'title' => 'Contrat CDI pour ' . $user->name,
                'contract_type' => 'cdi',
                'status' => 'draft',
                'is_avenant' => false
            ]);
            
            $contract->save();
            
            return redirect()->route('admin.contracts.edit', $contract)
                ->with('success', 'Contrat CDI créé avec succès. Veuillez compléter les informations.');
        } 
        else if ($validated['contract_type'] === 'avenant') {
            // Trouver le dernier contrat complété
            $parentContract = $user->contracts()
                ->where('is_avenant', false)
                ->where('status', 'completed')
                ->latest()
                ->first();
                
            if (!$parentContract) {
                return redirect()->route('admin.contracts.create')
                    ->with('error', 'Aucun contrat principal trouvé pour cet employé.');
            }
            
            // Rediriger vers le formulaire de création d'avenant
            return redirect()->route('admin.contracts.create-avenant', $parentContract)
                ->with('success', 'Veuillez compléter les informations de l\'avenant.');
        }
        
        return redirect()->route('admin.contracts.index')
            ->with('error', 'Type de contrat non valide.');
    }

    /**
     * Synchronise et corrige les problèmes de photos d'employés
     */
    public function fixPhotos()
    {
        try {
            // S'assurer que le répertoire des photos existe
            $photoDir = storage_path('app/public/employee_photos');
            if (!file_exists($photoDir)) {
                mkdir($photoDir, 0755, true);
                \Log::info('Répertoire employee_photos créé: ' . $photoDir);
            }
            
            // S'assurer que le lien symbolique existe
            if (!file_exists(public_path('storage'))) {
                \Artisan::call('storage:link');
                \Log::info('Lien symbolique storage:link créé');
            }
            
            // Récupérer toutes les données de contrat avec des photos
            $contractsWithPhotos = ContractData::whereNotNull('photo_path')->get();
            $count = 0;
            $errors = 0;
            
            foreach ($contractsWithPhotos as $data) {
                // Vérifier si le fichier existe
                $photoPath = storage_path('app/public/' . $data->photo_path);
                if (!file_exists($photoPath)) {
                    \Log::warning('Photo manquante: ' . $photoPath);
                    $errors++;
                    continue;
                }
                
                // Mettre à jour le profil utilisateur associé
                $contract = Contract::find($data->contract_id);
                if ($contract && $contract->user) {
                    $contract->user->update([
                        'profile_photo_path' => $data->photo_path
                    ]);
                    $count++;
                    \Log::info('Photo de profil synchronisée: ' . $data->photo_path . ' pour l\'utilisateur #' . $contract->user_id);
                }
            }
            
            return redirect()->route('admin.contracts.index')
                ->with('success', 'Synchronisation des photos terminée: ' . $count . ' photos mises à jour, ' . $errors . ' erreurs.');
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la correction des photos: ' . $e->getMessage());
            return redirect()->route('admin.contracts.index')
                ->with('error', 'Une erreur est survenue: ' . $e->getMessage());
        }
    }
}
