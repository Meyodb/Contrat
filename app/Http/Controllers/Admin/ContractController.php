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

class ContractController extends Controller
{
    /**
     * Affiche la liste des contrats
     */
    public function index()
    {
        $contracts = Contract::with(['user', 'template', 'data'])->latest()->paginate(10);
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
            'employee_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
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
        
        // Traiter la photo de l'employé si elle est fournie
        if ($request->hasFile('employee_photo')) {
            // Créer le répertoire de stockage s'il n'existe pas
            $photoDir = storage_path('app/public/employee_photos');
            if (!file_exists($photoDir)) {
                mkdir($photoDir, 0755, true);
            }
            
            // S'assurer que le lien symbolique existe
            if (!file_exists(public_path('storage'))) {
                \Artisan::call('storage:link');
            }
            
            // Stocker la photo
            $photo = $request->file('employee_photo');
            $photoName = 'employee_' . $contract->id . '_' . time() . '.' . $photo->getClientOriginalExtension();
            $photo->storeAs('public/employee_photos', $photoName);
            
            // Ajouter le chemin de la photo aux données à mettre à jour
            $dataToUpdate['photo_path'] = 'employee_photos/' . $photoName;
            
            // Log pour débogage
            \Log::info('Photo enregistrée: ' . $photoName);
            \Log::info('Chemin complet: ' . storage_path('app/public/employee_photos/' . $photoName));
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
        // Calculer les heures mensuelles (work_hours * 4.33)
        if ($contractData->work_hours) {
            $contractData->weekly_hours = $contractData->work_hours;
            $contractData->monthly_hours = round($contractData->work_hours * 4.33, 2);
            
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
     * Signe le contrat (administrateur)
     */
    public function sign(Request $request, Contract $contract)
    {
        // Vérifier que l'utilisateur est un administrateur
        if (!auth()->user()->is_admin) {
            return redirect()->route('home')->with('status', 'Vous n\'êtes pas autorisé à signer ce contrat.');
        }
        
        // Vérifier que le contrat est signable (status = submitted)
        if ($contract->status !== 'submitted') {
            return redirect()->route('admin.contracts.show', $contract)
                ->with('status', 'Ce contrat ne peut pas être signé pour le moment.');
        }
        
        // Créer les dossiers pour les signatures si nécessaires
        $privateDir = storage_path('app/private/signatures');
        if (!file_exists($privateDir)) {
            mkdir($privateDir, 0755, true);
        }
        
        $publicDir = storage_path('app/public/signatures');
        if (!file_exists($publicDir)) {
            mkdir($publicDir, 0755, true);
        }
        
        // S'assurer que le lien symbolique existe
        if (!file_exists(public_path('storage'))) {
            \Artisan::call('storage:link');
        }
        
        try {
            // Utiliser toujours le même nom pour la signature admin
            $privateSignaturePath = 'private/signatures/admin_signature.png';
            $publicSignaturePath = 'public/signatures/admin_signature.png';
            
            // Vérifier si la signature existe déjà, sinon utiliser une signature par défaut
            if (!Storage::exists($publicSignaturePath)) {
                // Si on n'a pas de signature, créer une signature par défaut simple
                // Générer une image de signature basique
                $img = imagecreatetruecolor(300, 100);
                $background = imagecolorallocate($img, 255, 255, 255);
                $textcolor = imagecolorallocate($img, 0, 0, 0);
                
                // Fond blanc
                imagefilledrectangle($img, 0, 0, 300, 100, $background);
                
                // Dessiner une signature stylisée (lignes courbes)
                imageline($img, 50, 50, 100, 30, $textcolor);
                imageline($img, 100, 30, 150, 70, $textcolor);
                imageline($img, 150, 70, 200, 40, $textcolor);
                imageline($img, 200, 40, 250, 60, $textcolor);
                
                // Sauvegarder l'image
                ob_start();
                imagepng($img);
                $signatureContent = ob_get_clean();
                imagedestroy($img);
                
                // Sauvegarder dans les deux emplacements
                Storage::put($privateSignaturePath, $signatureContent);
                Storage::put($publicSignaturePath, $signatureContent);
                
                \Log::info('Signature admin créée', [
                    'private_path' => $privateSignaturePath,
                    'public_path' => $publicSignaturePath
                ]);
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
            // Generate a unique filename for the contract
            $user = $contract->user;
            
            // Format NOM_PRENOM_CONTRAT ou NOM_PRENOM_AVENANT_N
            $nameParts = explode(' ', trim($user->name));
            $lastName = strtoupper(array_shift($nameParts));
            $firstName = implode('_', array_map('ucfirst', $nameParts));
            
            if ($contract->isAvenant()) {
                $filename = $lastName . '_' . $firstName . '_AVENANT_' . $contract->avenant_number . '.pdf';
            } else {
                $filename = $lastName . '_' . $firstName . '_CONTRAT.pdf';
            }
            
            // Ensure the contracts directory exists with correct permissions
            if (!Storage::exists('contracts')) {
                Storage::makeDirectory('contracts', 0755);
            }
            
            // Ensure filesystem path exists with proper permissions
            $contractsDir = storage_path('app/contracts');
            if (!file_exists($contractsDir)) {
                mkdir($contractsDir, 0755, true);
            }
            
            // Vérifier si le répertoire a bien été créé
            if (!is_dir($contractsDir) || !is_writable($contractsDir)) {
                throw new \Exception('Le répertoire des contrats n\'existe pas ou n\'est pas accessible en écriture: ' . $contractsDir);
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
            $htmlDebugPath = storage_path('app/temp/debug_' . $filename . '.html');
            $htmlDebugDir = dirname($htmlDebugPath);
            if (!file_exists($htmlDebugDir)) {
                mkdir($htmlDebugDir, 0755, true);
            }
            file_put_contents($htmlDebugPath, $html);
            \Log::info('Génération PDF - HTML sauvegardé pour débogage', ['path' => $htmlDebugPath]);
            
            // Créer le PDF
            $pdf = Pdf::loadHTML($html);
            $pdf->setPaper('a4');
            
            // Sauvegarder le PDF
            $pdfPath = storage_path('app/contracts/' . $filename);
            \Log::info('Tentative de sauvegarde du PDF', ['path' => $pdfPath]);
            
            try {
                $pdf->save($pdfPath);
                \Log::info('PDF sauvegardé avec succès', ['path' => $pdfPath]);
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
            
            return redirect()->route('admin.contracts.show', $contract)
                ->with('success', 'Le document a été généré avec succès.');
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la génération du contrat: ' . $e->getMessage());
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
     * Prévisualise le contrat
     */
    public function preview(Contract $contract)
    {
        try {
            // Générer directement la prévisualisation comme dans l'ancienne méthode
            // Augmenter le temps d'exécution maximum pour éviter les timeouts
            set_time_limit(300);
            ini_set('memory_limit', '512M');
            
            // Préparer les données pour le template
            $contractData = [
                'contract' => $contract,
                'user' => $contract->user,
                'admin' => $contract->admin ?? auth()->user(),
                'data' => $contract->data,
            ];
            
            // Utiliser SignatureHelper pour préparer les signatures
            $signatureHelper = new \App\Temp_Fixes\SignatureHelper();
            
            // Récupérer la signature de l'administrateur
            $adminSignatureBase64 = $signatureHelper->prepareSignatureForPdf('admin_signature.png');
            
            // Récupérer la signature de l'employé
            $employeeId = $contract->user_id;
            $employeeSignatureBase64 = $signatureHelper->prepareSignatureForPdf('employee_signature.png', $employeeId);
            
            // Ajouter les signatures aux données du contrat seulement si le contrat est déjà signé
            if ($contract->status === 'admin_signed' || $contract->status === 'employee_signed' || $contract->status === 'completed') {
                // Afficher les signatures existantes dans la prévisualisation
                $contractData['adminSignatureBase64'] = $adminSignatureBase64;
                $contractData['employeeSignatureBase64'] = $employeeSignatureBase64;
                \Log::info('Prévisualisation PDF - Signatures incluses (contrat signé)', [
                    'admin_signature' => !empty($adminSignatureBase64) ? 'Base64 présent (' . strlen($adminSignatureBase64) . ' octets)' : 'Manquante',
                    'employee_signature' => !empty($employeeSignatureBase64) ? 'Base64 présent (' . strlen($employeeSignatureBase64) . ' octets)' : 'Manquante',
                    'status' => $contract->status
                ]);
            } else {
                // Ne pas inclure les signatures pour les contrats non signés
                $contractData['adminSignatureBase64'] = '';
                $contractData['employeeSignatureBase64'] = '';
                \Log::info('Prévisualisation PDF - Signatures non incluses (contrat non signé)', [
                    'status' => $contract->status
                ]);
            }
            
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
                // Générer le HTML avec le template Blade (utiliser le template CDI)
                $html = view('pdf.cdi-template', $contractData)->render();
            }
            
            // Créer le PDF
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
            $pdf->setPaper('a4');
            
            // Générer un nom de fichier pour le document PDF
            $filename = 'contrat_' . $contract->id . '_' . str_replace(' ', '_', $contract->user->name ?? 'employe') . '_preview.pdf';
            
            // Créer le répertoire si nécessaire avec les bonnes permissions
            $tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
            // Sauvegarder le PDF temporaire
            $pdfPath = storage_path('app/temp/' . $filename);
            $pdf->save($pdfPath);
            
            // Retourner le PDF pour prévisualisation
            return response()->file($pdfPath);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la prévisualisation du contrat: ' . $e->getMessage(), [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Erreur lors de la prévisualisation du contrat.');
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
                \Log::info('Téléchargement du contrat', ['path' => $path, 'exists' => file_exists($path)]);
                $filename = 'contrat_' . $contract->id . '_' . str_replace(' ', '_', $contract->user->name) . '.pdf';
                
                return response()->download($path, $filename, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"'
                ]);
                } else {
                \Log::warning('Tentative de téléchargement d\'un contrat inexistant', [
                    'contract_id' => $contract->id,
                    'final_document_path' => $contract->final_document_path,
                    'exists' => $contract->final_document_path ? Storage::exists($contract->final_document_path) : false
                ]);
            }
            
            // Si le document n'existe pas, le générer
            // Créer un nouveau document PHPWord
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
            
            // Générer le contenu du contrat (simplifié pour l'exemple)
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
            
            // Générer un nom de fichier pour le document PDF
            $filename = 'contrat_' . $contract->id . '_' . str_replace(' ', '_', $contract->user->name ?? 'employe') . '.pdf';
            
            // Créer les répertoires nécessaires
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }
            
            if (!file_exists(storage_path('app/contracts'))) {
                mkdir(storage_path('app/contracts'), 0755, true);
            }
            
            $tempWordPath = storage_path('app/temp/temp_word_' . time() . '.docx');
            $pdfPath = storage_path('app/temp/' . $filename);
            $finalPath = 'contracts/' . $filename;
            $finalFullPath = storage_path('app/' . $finalPath);
            
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
            
            // Retourner le PDF pour téléchargement
            return response()->download($pdfPath, $filename, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ])->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la génération du contrat: ' . $e->getMessage());
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
        ]);
        
        // Créer un nouvel avenant
        $avenant = new Contract([
            'user_id' => $contract->user_id,
            'admin_id' => auth()->id(),
            'contract_template_id' => $contract->contract_template_id,
            'parent_contract_id' => $contract->id,
            'title' => 'Avenant n°' . $validated['avenant_number'] . ' au contrat de ' . $contract->user->name,
            'avenant_number' => $validated['avenant_number'],
            'contract_type' => 'avenant',
            'status' => 'admin_signed', // L'avenant est directement signé par l'administrateur
            'admin_signature' => 'signatures/admin_signature.png',
            'admin_signed_at' => now(),
        ]);
        
        $avenant->save();
        
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
        
        // Notifier l'employé
        $contract->user->notify(new \App\Notifications\AvenantCreated($avenant));
        
        return redirect()->route('admin.contracts.show', $avenant)
            ->with('success', 'L\'avenant a été créé avec succès. L\'employé doit maintenant le signer.');
    }

    /**
     * Prévisualiser un avenant avant de le créer
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Contract  $contract
     * @return \Illuminate\Http\Response
     */
    public function previewAvenant(Request $request, Contract $contract)
    {
        // Valider les données du formulaire
        $validated = $request->validate([
            'avenant_number' => 'required|integer',
            'effective_date' => 'required|date',
            'signing_date' => 'required|date',
            'new_hours' => 'nullable|numeric',
            'new_salary' => 'nullable|numeric',
            'new_hourly_rate' => 'nullable|numeric',
        ]);
        
        // Créer un contrat temporaire pour la prévisualisation
        $avenantData = $contract->data->toArray();
        
        // Mettre à jour les données avec les nouvelles valeurs d'avenant
        if (!empty($validated['new_hours'])) {
            $avenantData['weekly_hours'] = $validated['new_hours'];
        }
        
        if (!empty($validated['new_salary'])) {
            $avenantData['monthly_salary'] = $validated['new_salary'];
        }
        
        if (!empty($validated['new_hourly_rate'])) {
            $avenantData['hourly_rate'] = $validated['new_hourly_rate'];
        }
        
        // Générer le PDF de prévisualisation
        $pdfController = new \App\Http\Controllers\PdfController();
        $tempContract = new Contract();
        $tempContract->id = 'preview_' . $contract->id . '_' . time();
        $tempContract->is_avenant = true;
        $tempContract->avenant_number = $validated['avenant_number'];
        $tempContract->parent_contract_id = $contract->id;
        $tempContract->user_id = $contract->user_id;
        $tempContract->effective_date = $validated['effective_date'];
        $tempContract->signing_date = $validated['signing_date'];
        
        // Associer les données temporairement
        $tempData = new \App\Models\ContractData();
        foreach ($avenantData as $key => $value) {
            $tempData->$key = $value;
        }
        $tempContract->setRelation('data', $tempData);
        $tempContract->setRelation('user', $contract->user);
        $tempContract->setRelation('parentContract', $contract);
        
        // Générer le PDF
        $pdfPath = $pdfController->generateContractPdf($tempContract, true);
        
        // Retourner le PDF comme réponse
        return response()->file(storage_path('app/' . $pdfPath));
    }
}
