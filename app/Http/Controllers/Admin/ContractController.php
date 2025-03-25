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
        
        // Traiter la signature de l'administrateur
        if ($request->has('admin_signature')) {
            // Utiliser la signature enregistrée de l'administrateur
            if ($request->has('admin_signature') && strpos($request->admin_signature, 'data:image/png;base64,') === 0) {
                $signatureData = $request->admin_signature;
                
                // Utiliser toujours le même nom pour la signature admin
                $privateSignaturePath = 'private/signatures/admin_signature.png';
                Storage::put($privateSignaturePath, base64_decode(explode(',', $signatureData)[1]));
                
                $publicSignaturePath = 'public/signatures/admin_signature.png';
                Storage::put($publicSignaturePath, base64_decode(explode(',', $signatureData)[1]));
                
                // L'URL est maintenant toujours la même
                $signatureUrl = asset('storage/signatures/admin_signature.png');
                
                // Mettre à jour le contrat avec la signature de l'administrateur
                $contract->update([
                    'admin_signature' => $signatureUrl,
                    'admin_signed_at' => now(),
                    'status' => 'admin_signed'
                ]);
            }
        }
        
        // Notification à l'employé que le contrat a été signé par l'administrateur
        $contract->user->notify(new \App\Notifications\ContractSignedByAdmin($contract));
        
        return redirect()->route('admin.contracts.index')
            ->with('success', 'Contrat signé avec succès');
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
        
        // Utiliser directement le template "CDI Whatever"
        $templatePath = storage_path('app/templates/CDI Whatever.docx');
        
        // Vérifier si le fichier template existe
        if (!file_exists($templatePath)) {
            return redirect()->route('admin.contracts.show', $contract)
                ->with('error', 'Le template de contrat "CDI Whatever" est introuvable.');
        }
        
        // Generate a unique filename for the contract
        $user = $contract->user;
        
        // Format NOM_PRENOM_CONTRAT
        $nameParts = explode(' ', trim($user->name));
        $lastName = strtoupper(array_shift($nameParts));
        $firstName = implode('_', array_map('ucfirst', $nameParts));
        $filename = $lastName . '_' . $firstName . '_CONTRAT.docx';
        
        $generatedPath = storage_path('app/contracts/' . $filename);
        
        // Ensure the contracts directory exists
        if (!Storage::exists('contracts')) {
            Storage::makeDirectory('contracts');
        }
        
        // Copy the template file
        copy($templatePath, $generatedPath);
        
        // Use PHPWord to replace placeholders
        $phpWord = IOFactory::load($generatedPath);
        
        // Replace placeholders with user data
        $contractData = $contract->data ? $contract->data->toArray() : [];
        
        // Get signature placeholders
        $adminSignaturePlaceholder = "[Signature de l'administrateur]";
        $employeeSignaturePlaceholder = "[Signature de l'employé]";
        
        // If admin has signed, replace admin signature placeholder with actual signature
        if ($contract->admin_signature) {
            if (filter_var($contract->admin_signature, FILTER_VALIDATE_URL)) {
                // It's an image URL (from the pre-registered signature)
                $adminSignaturePlaceholder = '<img src="' . $contract->admin_signature . '" width="100" height="50" />';
            } else {
                // It's a text signature
                $adminSignaturePlaceholder = $contract->admin_signature;
            }
        }
        
        // If employee has signed, replace employee signature placeholder with actual signature
        if ($contract->employee_signature) {
            $employeeSignaturePlaceholder = $contract->employee_signature;
        }
        
        // Search and replace in the document
        $this->replacePlaceholdersInDocument($phpWord, [
            '{{USER_NAME}}' => $user->name,
            '{{USER_EMAIL}}' => $user->email,
            '{{CONTRACT_DATE}}' => date('Y-m-d'),
            '{{ADMIN_SIGNATURE}}' => $adminSignaturePlaceholder,
            '{{EMPLOYEE_SIGNATURE}}' => $employeeSignaturePlaceholder,
            // Add more placeholders as needed
        ]);
        
        // Also replace any contract-specific data
        if (!empty($contractData)) {
            $placeholders = [];
            foreach ($contractData as $key => $value) {
                if (is_string($value) || is_numeric($value)) {
                    $placeholders['{{'.$key.'}}'] = $value;
                }
            }
            $this->replacePlaceholdersInDocument($phpWord, $placeholders);
        }
        
        // Save the document
        $phpWord->save($generatedPath);
        
        // Convert to PDF with the required naming format
        Settings::setPdfRendererName(Settings::PDF_RENDERER_DOMPDF);
        Settings::setPdfRendererPath(base_path('vendor/dompdf/dompdf'));
        
        $pdfFilename = pathinfo($filename, PATHINFO_FILENAME) . '.pdf';
        $pdfPath = storage_path('app/contracts/' . $pdfFilename);
        
        $pdf = IOFactory::createWriter($phpWord, 'PDF');
        $pdf->save($pdfPath);
        
        // Update the contract with the PDF path and change status
        $contract->final_document_path = 'contracts/' . $pdfFilename;
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
            ->with('success', 'Le document du contrat a été généré avec succès.');
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
     * Permet le téléchargement du document final du contrat
     */
    public function download(Contract $contract)
    {
        try {
            // Augmenter le temps d'exécution maximum pour éviter les timeouts
            set_time_limit(300);
            ini_set('memory_limit', '512M');
            
            // Charger les données du contrat
            $contractData = ContractData::where('contract_id', $contract->id)->first();
            if (!$contractData) {
                return redirect()->back()->with('error', 'Données du contrat non trouvées.');
            }
            
            // Préparer les données pour le template
            $data = $contractData;
            
            // Configuration optimisée de DomPDF
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $options->set('isPhpEnabled', true);
            $options->set('defaultFont', 'Arial');
            $options->set('isFontSubsettingEnabled', true);
            $options->set('dpi', 96);
            $options->set('defaultMediaType', 'screen');
            $options->set('defaultPaperSize', 'a4');
            
            // Créer l'instance DomPDF avec les options optimisées
            $dompdf = new Dompdf($options);
            
            // Générer le HTML du contrat
            $html = view('pdf.cdi-template', [
                'data' => $data, 
                'contract' => $contract,
                'admin_signature' => $contract->admin_signature,
                'employee_signature' => $contract->employee_signature
            ])->render();
            
            // Activer le débogage pour voir les erreurs
            $dompdf->setOptions(new Options([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'isPhpEnabled' => true,
                'defaultFont' => 'Arial',
                'isFontSubsettingEnabled' => true,
                'dpi' => 96,
                'defaultMediaType' => 'screen',
                'defaultPaperSize' => 'a4',
                'debugKeepTemp' => true,
                'debugCss' => true,
                'debugLayout' => true
            ]));
            
            // Charger le HTML dans DomPDF
            $dompdf->loadHtml($html);
            
            // Rendre le PDF
            $dompdf->render();
            
            // Générer le PDF en mémoire
            $output = $dompdf->output();
            
            // Créer les répertoires des contrats s'ils n'existent pas
            $contractsDir = storage_path('app/contracts');
            if (!file_exists($contractsDir)) {
                mkdir($contractsDir, 0755, true);
            }
            
            $privateContractsDir = storage_path('app/private/contracts');
            if (!file_exists($privateContractsDir)) {
                mkdir($privateContractsDir, 0755, true);
            }
            
            // Définir le chemin du fichier PDF
            $filename = 'contrat_' . $contract->id . '_' . str_replace(' ', '_', $contract->user->name) . '.pdf';
            $pdfPath = 'contracts/' . $filename;
            $privatePdfPath = 'private/contracts/' . $filename;
            
            // Sauvegarder le PDF dans les deux emplacements
            Storage::put($pdfPath, $output);
            Storage::put($privatePdfPath, $output);
            
            // Mettre à jour le chemin du document final dans la base de données
            $contract->final_document_path = $pdfPath;
            
            // Si les deux signatures sont présentes et que le statut n'est pas déjà "completed"
            if (!empty($contract->employee_signature) && !empty($contract->admin_signature) && $contract->status !== 'completed') {
                $contract->status = 'completed';
            }
            
            // Mettre à jour la date de génération
            $contract->generated_at = now();
            $contract->save();
            
            // Télécharger le PDF directement depuis la mémoire
            return response($output)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de la génération du PDF: ' . $e->getMessage());
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
     * Prévisualise le contrat
     */
    public function preview(Contract $contract)
    {
        // Vérifier que l'utilisateur est autorisé à voir ce contrat
        if (!auth()->user()->is_admin && auth()->id() !== $contract->user_id) {
            abort(403, 'Vous n\'êtes pas autorisé à accéder à ce contrat.');
        }
        
        // Charger les données du contrat
        $contract->load(['user', 'data']);
        
        // Préparer les données pour le template
        $data = $contract->data ?: new \stdClass();
        $admin = auth()->user();
        
        try {
            // Utiliser le template Blade pour générer le PDF
            $contractData = [
                'contract' => $contract,
                'user' => $contract->user,
                'admin' => $admin,
                'data' => $data
            ];
            
            // Utiliser spécifiquement le template cdi-template comme dans l'espace employé
            $html = view('pdf.cdi-template', $contractData)->render();
            
            // Configurer DomPDF
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            
            // Créer l'instance DomPDF
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            
            // Générer un nom de fichier unique
            $filename = 'preview_' . $contract->id . '_' . time() . '.pdf';
            
            // Retourner le PDF
            return response($dompdf->output())
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
        } catch (\Exception $e) {
            // En cas d'erreur, rediriger avec un message d'erreur
            return back()->with('error', 'Erreur lors de la génération du PDF: ' . $e->getMessage());
        }
    }

    /**
     * Supprime les coordonnées bancaires d'un contrat
     */
    public function deleteBankDetails(Contract $contract)
    {
        // Vérifier que le contrat a des données
        if ($contract->data) {
            // Sauvegarder l'action dans les logs
            Log::info('Suppression des coordonnées bancaires pour le contrat #' . $contract->id . ' par l\'administrateur #' . auth()->id());
            
            // Supprimer les coordonnées bancaires
            $contract->data->bank_details = null;
            $contract->data->save();
            
            return redirect()->route('admin.contracts.show', $contract)
                ->with('success', 'Les coordonnées bancaires ont été supprimées avec succès.');
        }
        
        return redirect()->route('admin.contracts.show', $contract)
            ->with('error', 'Aucune donnée de contrat trouvée.');
    }
}
