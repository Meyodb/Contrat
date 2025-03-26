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
        
        try {
            // Utiliser toujours le même nom pour la signature admin
            $privateSignaturePath = 'private/signatures/admin_signature.png';
            $publicSignaturePath = 'public/signatures/admin_signature.png';
            
            // Vérifier si la signature existe déjà, sinon utiliser une signature par défaut
            if (!Storage::exists($publicSignaturePath)) {
                // Si on n'a pas de signature, copier une signature par défaut ou en créer une
                $defaultSignature = base64_encode(file_get_contents(public_path('img/default_admin_signature.png')));
                Storage::put($privateSignaturePath, base64_decode($defaultSignature));
                Storage::put($publicSignaturePath, base64_decode($defaultSignature));
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
        // Vérifier que l'utilisateur est autorisé à télécharger ce contrat
        if (!auth()->user()->is_admin && auth()->id() !== $contract->user_id) {
            return redirect()->route('home')->with('status', 'Vous n\'êtes pas autorisé à télécharger ce contrat.');
        }
        
        try {
            // Augmenter le temps d'exécution maximum pour éviter les timeouts
            set_time_limit(300);
            ini_set('memory_limit', '512M');
            
            // Charger les données du contrat
            $contract->load(['user', 'data']);
            
            // Créer un nouveau document PHPWord
            $phpWord = new \PhpOffice\PhpWord\PhpWord();
            
            // Définir les styles de base
            $phpWord->setDefaultFontName('Arial');
            $phpWord->setDefaultFontSize(11);

            // Ajouter une section
            $section = $phpWord->addSection();
            
            // Ajouter le titre
            $section->addText('CONTRAT DE TRAVAIL A DUREE INDETERMINEE', 
                ['bold' => true, 'size' => 14], 
                ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
            );
            $section->addTextBreak(2);
            
            // Introduction
            $section->addText('Entre Les Soussignés :', ['bold' => true]);
            $section->addTextBreak();
            
            // Informations de la société
            $section->addText('La Société WHAT EVER SAS, société à responsabilité limitée au capital de 200 000 Euros dont le siège social est situé 54 Avenue Kléber 75016 PARIS,');
            $section->addText('Immatriculée au Registre du Commerce et des Sociétés de Paris sous le n° 439 077 462 00026,');
            $section->addText('Représentée par Monsieur BRIAND Grégory ayant tous pouvoirs à l\'effet des présentes,');
            $section->addText('Cotisant à l\'URSSAF de Paris sous le n° 758 2320572850010116');
            $section->addText('d\'une part,');
            $section->addTextBreak();
            
            $section->addText('et,');
            $section->addTextBreak();
            
            // Informations de l'employé
            $gender = ($contract->data->gender ?? '') == 'M' ? 'Monsieur' : 'Madame';
            $fullName = $contract->data->full_name ?? ($contract->data->first_name ?? '') . ' ' . ($contract->data->last_name ?? '');
            $birthDate = $contract->data->birth_date ? date('d/m/Y', strtotime($contract->data->birth_date)) : '___________';
            $birthPlace = $contract->data->birth_place ?? '___________';
            $nationality = $contract->data->nationality ?? '___________';
            $ssn = $contract->data->social_security_number ?? '___________';
            $address = $contract->data->address ?? '___________';
            $postalCode = $contract->data->postal_code ?? '___________';
            $city = $contract->data->city ?? '___________';
            
            $section->addText("$gender $fullName");
            $section->addText("Né" . ($gender !== 'Monsieur' ? 'e' : '') . " le $birthDate à $birthPlace,");
            $section->addText("De nationalité $nationality,");
            $section->addText("Numéro Sécurité Sociale : $ssn");
            $section->addText("Demeurant : $address, $postalCode $city");
            $section->addText("d'autre part,");
            $section->addTextBreak();
            
            $section->addText("Il a été convenu ce qui suit :");
            $section->addTextBreak();
            
            // Articles du contrat
            $section->addText("ARTICLE 1 - ENGAGEMENT", ['bold' => true]);
            $section->addText("Le présent contrat est régi par les dispositions de la convention collective de la restauration rapide et du code du travail avec pour obligation de s'acquitter des avantages repas s'ils sont consommés, ou alors de recevoir une indemnité compensatoire, si les créneaux horaires de travail le justifient.");
            $section->addTextBreak();
            
            $section->addText("ARTICLE 2 - DUREE DU CONTRAT - PÉRIODE D'ESSAI", ['bold' => true]);
            $signingDate = $contract->data->contract_signing_date ? date('d/m/Y', strtotime($contract->data->contract_signing_date)) : '___________';
            $trialMonths = $contract->data->trial_period_months ?? '___________';
            $trialEndDate = $contract->data->trial_period_end_date ? date('d/m/Y', strtotime($contract->data->trial_period_end_date)) : '___________';
            
            $section->addText("Le présent contrat est conclu pour une durée indéterminée à compter du $signingDate.");
            $section->addText("Il ne deviendra définitif qu'à l'issue d'une période d'essai de $trialMonths, soit jusqu'au $trialEndDate, renouvelable 1 mois.");
            $section->addText("Durant cette période, chacune des parties pourra, à tout moment, mettre fin au présent contrat sans qu'aucune indemnité ni préavis ne soient dus.");
            $section->addTextBreak();
            
            // Article 3
            $section->addText("ARTICLE 3 - FONCTIONS", ['bold' => true]);
            $section->addText("$gender $fullName est employé(e) en qualité d'Employée de restauration.");
            $section->addText("$gender $fullName exercera ses fonctions dans le cadre des directives écrites ou verbales qui lui seront données par M Briand ou toute personne qui pourrait lui être substituée.");
            $section->addTextBreak();
            
            // Article 4
            $section->addText("ARTICLE 4 - REMUNERATION", ['bold' => true]);
            $salary = $contract->data->monthly_gross_salary ?? '___________';
            $monthlyHours = $contract->data->monthly_hours ?? '___________';
            $section->addText("La rémunération mensuelle brute de $gender $fullName sera de $salary euros pour $monthlyHours heures mensuel.");
            $section->addTextBreak();
            
            // Article 5
            $section->addText("ARTICLE 5 - HORAIRES DE TRAVAIL", ['bold' => true]);
            $weeklyHours = $contract->data->weekly_hours ?? '___________';
            $section->addText("La durée de travail sera de $weeklyHours heures hebdomadaires, réparties du lundi au dimanche.");
            $section->addText("Les jours et horaires de travail seront indiqués à $gender $fullName, par le biais de plannings hebdomadaires, établis et affichés à l'avance, dans chaque établissement.");
            $section->addTextBreak();
            
            // Article 6
            $section->addText("ARTICLE 6 - LIEU DE TRAVAIL", ['bold' => true]);
            $section->addText("$gender $fullName exercera ses fonctions à Paris et sa proche banlieue. Toutefois, en fonction des nécessités de service, il/elle pourra être amené(e) à exercer temporairement ses fonctions en tout lieu où la société WHAT EVER SAS exerce son activité.");
            $section->addTextBreak();
            
            // Article 7
            $section->addText("ARTICLE 7 - CONGÉS PAYÉS", ['bold' => true]);
            $section->addText("$gender $fullName bénéficiera des congés payés conformément aux dispositions légales en vigueur. Les dates de congés seront déterminées par accord entre la Direction et $gender $fullName, en fonction des nécessités du service.");
            $section->addTextBreak();
            
            // Article 8
            $section->addText("ARTICLE 8 - OBLIGATIONS PROFESSIONNELLES", ['bold' => true]);
            $section->addText("$gender $fullName s'engage pendant la durée de son contrat à respecter les instructions qui lui seront données par la Direction et à se conformer aux règles régissant le fonctionnement interne de l'entreprise.");
            $section->addText("$gender $fullName s'engage à informer la société WHAT EVER SAS de tout changement concernant sa situation personnelle (domicile, situation de famille, etc).");
            $section->addText("$gender $fullName s'engage à conserver une discrétion absolue sur tous les faits, informations et documents dont il/elle pourrait avoir connaissance dans l'exercice de ses fonctions.");
            $section->addTextBreak();
            
            // Article 9
            $section->addText("ARTICLE 9 - RUPTURE DU CONTRAT", ['bold' => true]);
            $section->addText("A l'issue de la période d'essai, le présent contrat pourra être rompu par l'une ou l'autre des parties dans les conditions prévues par la législation en vigueur, sous réserve du respect du préavis fixé par la convention collective.");
            $section->addTextBreak();
            
            // Article 10
            $section->addText("ARTICLE 10 - RÉGIME DE PRÉVOYANCE ET MUTUELLE", ['bold' => true]);
            $section->addText("$gender $fullName sera affilié(e) dès son entrée dans l'entreprise au régime de prévoyance et de complémentaire santé en vigueur dans l'entreprise, selon les conditions générales prévues par ces régimes.");
            $section->addTextBreak();
            
            // Signatures
            $section->addText("Fait en double exemplaire originaux dont un pour chacune des parties.", null, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            $section->addText("A Paris, le " . date('d/m/Y'), null, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            $section->addTextBreak(2);
            
            // Créer une table pour les signatures
            $table = $section->addTable(['borderSize' => 0, 'cellMargin' => 80]);
            $table->addRow();
            
            // Cellule pour la signature de l'employeur
            $cell1 = $table->addCell(4000, ['valign' => 'center']);
            $cell1->addText('L\'employeur', ['bold' => true], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            $cell1->addText('M BRIAND Grégory', null, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            $cell1->addText('Pour la société', null, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            
            // Insérer l'image de signature de l'administrateur
            $adminSignaturePath = storage_path('app/public/signatures/admin_signature.png');
            if (file_exists($adminSignaturePath)) {
                try {
                    $tempDir = sys_get_temp_dir();
                    $tempImagePath = $tempDir . '/admin_signature_' . time() . '.png';
                    copy($adminSignaturePath, $tempImagePath);
                    
                    $cell1->addImage(
                        $tempImagePath,
                        ['width' => 150, 'height' => 75, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
                    );
                    
                    // Si la signature a une date
                    if ($contract->admin_signed_at) {
                        $cell1->addText('Le ' . date('d/m/Y à H:i', strtotime($contract->admin_signed_at)), 
                            ['size' => 8], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                    }
                } catch (\Exception $e) {
                    \Log::error('Erreur lors de l\'ajout de l\'image de signature admin: ' . $e->getMessage());
                    // Fallback à la version texte
                    $cell1->addText('SIGNATURE ÉLECTRONIQUE', ['italic' => true, 'bold' => true, 'color' => 'FFFFFF', 'bgcolor' => '333333'], 
                        ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                    $cell1->addText('Grégory BRIAND', ['bold' => true], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                }
            } else {
                // Fallback si l'image n'existe pas
                $cell1->addText('SIGNATURE ÉLECTRONIQUE', ['italic' => true, 'bold' => true, 'color' => 'FFFFFF', 'bgcolor' => '333333'], 
                    ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                $cell1->addText('Grégory BRIAND', ['bold' => true], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            }
            
            // Cellule pour l'espace entre les signatures
            $table->addCell(1000);
            
            // Cellule pour la signature de l'employé
            $cell2 = $table->addCell(4000, ['valign' => 'center']);
            $cell2->addText('L\'employé(e)', ['bold' => true], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            $cell2->addText(($contract->data->first_name ?? '') . ' ' . ($contract->data->last_name ?? ''), null, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            
            // Si l'employé a signé
            if ($contract->employee_signed_at && $contract->user_id) {
                // Chercher l'image de signature de l'employé
                $employeeSignaturePath = storage_path('app/public/signatures/' . $contract->user_id . '_employee.png');
                if (file_exists($employeeSignaturePath)) {
                    try {
                        $tempDir = sys_get_temp_dir();
                        $tempImagePath = $tempDir . '/employee_signature_' . time() . '.png';
                        copy($employeeSignaturePath, $tempImagePath);
                        
                        $cell2->addImage(
                            $tempImagePath,
                            ['width' => 150, 'height' => 75, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
                        );
                        
                        // Ajouter la date de signature
                        $cell2->addText('Le ' . date('d/m/Y à H:i', strtotime($contract->employee_signed_at)), 
                            ['size' => 8], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                    } catch (\Exception $e) {
                        \Log::error('Erreur lors de l\'ajout de l\'image de signature employé: ' . $e->getMessage());
                        // Fallback à la version texte
                        $cell2->addText('SIGNATURE ÉLECTRONIQUE', ['italic' => true, 'bold' => true, 'color' => 'FFFFFF', 'bgcolor' => '333333'], 
                            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                        $cell2->addText(($contract->data->first_name ?? '') . ' ' . ($contract->data->last_name ?? ''), 
                            ['bold' => true], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                    }
                } else {
                    // Fallback si l'image n'existe pas
                    $cell2->addText('SIGNATURE ÉLECTRONIQUE', ['italic' => true, 'bold' => true, 'color' => 'FFFFFF', 'bgcolor' => '333333'], 
                        ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                    $cell2->addText(($contract->data->first_name ?? '') . ' ' . ($contract->data->last_name ?? ''), 
                        ['bold' => true], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                }
            } else {
                $cell2->addText('___________________', null, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                $cell2->addText('(Signature non apposée)', ['italic' => true, 'size' => 8], 
                    ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            }
            
            // Générer un nom de fichier pour le document Word
            $filename = 'contrat_' . $contract->id . '_' . ($contract->user->name ?? 'employe') . '.docx';
            $filePath = storage_path('app/contracts/' . $filename);
            
            // Créer le répertoire si nécessaire
            if (!file_exists(storage_path('app/contracts'))) {
                mkdir(storage_path('app/contracts'), 0755, true);
            }
            
            // Sauvegarder le document
            $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
            $objWriter->save($filePath);
            
            // Mettre à jour le chemin du document final dans la base de données
            $contract->final_document_path = 'contracts/' . $filename;
            
            // Si les deux signatures sont présentes et que le statut n'est pas déjà "completed"
            if (!empty($contract->employee_signature) && !empty($contract->admin_signature) && $contract->status !== 'completed') {
                $contract->status = 'completed';
            }
            
            // Mettre à jour la date de génération
            $contract->generated_at = now();
            $contract->save();
            
            // Retourner le document pour téléchargement
            return response()->download($filePath, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Erreur téléchargement Word: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return redirect()->back()->with('error', 'Erreur lors de la génération du document Word: ' . $e->getMessage());
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
        
        try {
            // Créer un nouveau document PHPWord
            $phpWord = new \PhpOffice\PhpWord\PhpWord();
            
            // Définir les styles de base
            $phpWord->setDefaultFontName('Arial');
            $phpWord->setDefaultFontSize(11);

            // Ajouter une section
            $section = $phpWord->addSection();
            
            // Ajouter le titre
            $section->addText('CONTRAT DE TRAVAIL A DUREE INDETERMINEE', 
                ['bold' => true, 'size' => 14], 
                ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
            );
            $section->addTextBreak(2);
            
            // Introduction
            $section->addText('Entre Les Soussignés :', ['bold' => true]);
            $section->addTextBreak();
            
            // Informations de la société
            $section->addText('La Société WHAT EVER SAS, société à responsabilité limitée au capital de 200 000 Euros dont le siège social est situé 54 Avenue Kléber 75016 PARIS,');
            $section->addText('Immatriculée au Registre du Commerce et des Sociétés de Paris sous le n° 439 077 462 00026,');
            $section->addText('Représentée par Monsieur BRIAND Grégory ayant tous pouvoirs à l\'effet des présentes,');
            $section->addText('Cotisant à l\'URSSAF de Paris sous le n° 758 2320572850010116');
            $section->addText('d\'une part,');
            $section->addTextBreak();
            
            $section->addText('et,');
            $section->addTextBreak();
            
            // Informations de l'employé
            $gender = ($contract->data->gender ?? '') == 'M' ? 'Monsieur' : 'Madame';
            $fullName = $contract->data->full_name ?? ($contract->data->first_name ?? '') . ' ' . ($contract->data->last_name ?? '');
            $birthDate = $contract->data->birth_date ? date('d/m/Y', strtotime($contract->data->birth_date)) : '___________';
            $birthPlace = $contract->data->birth_place ?? '___________';
            $nationality = $contract->data->nationality ?? '___________';
            $ssn = $contract->data->social_security_number ?? '___________';
            $address = $contract->data->address ?? '___________';
            $postalCode = $contract->data->postal_code ?? '___________';
            $city = $contract->data->city ?? '___________';
            
            $section->addText("$gender $fullName");
            $section->addText("Né" . ($gender !== 'Monsieur' ? 'e' : '') . " le $birthDate à $birthPlace,");
            $section->addText("De nationalité $nationality,");
            $section->addText("Numéro Sécurité Sociale : $ssn");
            $section->addText("Demeurant : $address, $postalCode $city");
            $section->addText("d'autre part,");
            $section->addTextBreak();
            
            $section->addText("Il a été convenu ce qui suit :");
            $section->addTextBreak();
            
            // Articles du contrat
            $section->addText("ARTICLE 1 - ENGAGEMENT", ['bold' => true]);
            $section->addText("Le présent contrat est régi par les dispositions de la convention collective de la restauration rapide et du code du travail avec pour obligation de s'acquitter des avantages repas s'ils sont consommés, ou alors de recevoir une indemnité compensatoire, si les créneaux horaires de travail le justifient.");
            $section->addTextBreak();
            
            $section->addText("ARTICLE 2 - DUREE DU CONTRAT - PÉRIODE D'ESSAI", ['bold' => true]);
            $signingDate = $contract->data->contract_signing_date ? date('d/m/Y', strtotime($contract->data->contract_signing_date)) : '___________';
            $trialMonths = $contract->data->trial_period_months ?? '___________';
            $trialEndDate = $contract->data->trial_period_end_date ? date('d/m/Y', strtotime($contract->data->trial_period_end_date)) : '___________';
            
            $section->addText("Le présent contrat est conclu pour une durée indéterminée à compter du $signingDate.");
            $section->addText("Il ne deviendra définitif qu'à l'issue d'une période d'essai de $trialMonths, soit jusqu'au $trialEndDate, renouvelable 1 mois.");
            $section->addText("Durant cette période, chacune des parties pourra, à tout moment, mettre fin au présent contrat sans qu'aucune indemnité ni préavis ne soient dus.");
            $section->addTextBreak();
            
            // Article 3
            $section->addText("ARTICLE 3 - FONCTIONS", ['bold' => true]);
            $section->addText("$gender $fullName est employé(e) en qualité d'Employée de restauration.");
            $section->addText("$gender $fullName exercera ses fonctions dans le cadre des directives écrites ou verbales qui lui seront données par M Briand ou toute personne qui pourrait lui être substituée.");
            $section->addTextBreak();
            
            // Article 4
            $section->addText("ARTICLE 4 - REMUNERATION", ['bold' => true]);
            $salary = $contract->data->monthly_gross_salary ?? '___________';
            $monthlyHours = $contract->data->monthly_hours ?? '___________';
            $section->addText("La rémunération mensuelle brute de $gender $fullName sera de $salary euros pour $monthlyHours heures mensuel.");
            $section->addTextBreak();
            
            // Article 5
            $section->addText("ARTICLE 5 - HORAIRES DE TRAVAIL", ['bold' => true]);
            $weeklyHours = $contract->data->weekly_hours ?? '___________';
            $section->addText("La durée de travail sera de $weeklyHours heures hebdomadaires, réparties du lundi au dimanche.");
            $section->addText("Les jours et horaires de travail seront indiqués à $gender $fullName, par le biais de plannings hebdomadaires, établis et affichés à l'avance, dans chaque établissement.");
            $section->addTextBreak();
            
            // Article 6
            $section->addText("ARTICLE 6 - LIEU DE TRAVAIL", ['bold' => true]);
            $section->addText("$gender $fullName exercera ses fonctions à Paris et sa proche banlieue. Toutefois, en fonction des nécessités de service, il/elle pourra être amené(e) à exercer temporairement ses fonctions en tout lieu où la société WHAT EVER SAS exerce son activité.");
            $section->addTextBreak();
            
            // Article 7
            $section->addText("ARTICLE 7 - CONGÉS PAYÉS", ['bold' => true]);
            $section->addText("$gender $fullName bénéficiera des congés payés conformément aux dispositions légales en vigueur. Les dates de congés seront déterminées par accord entre la Direction et $gender $fullName, en fonction des nécessités du service.");
            $section->addTextBreak();
            
            // Article 8
            $section->addText("ARTICLE 8 - OBLIGATIONS PROFESSIONNELLES", ['bold' => true]);
            $section->addText("$gender $fullName s'engage pendant la durée de son contrat à respecter les instructions qui lui seront données par la Direction et à se conformer aux règles régissant le fonctionnement interne de l'entreprise.");
            $section->addText("$gender $fullName s'engage à informer la société WHAT EVER SAS de tout changement concernant sa situation personnelle (domicile, situation de famille, etc).");
            $section->addText("$gender $fullName s'engage à conserver une discrétion absolue sur tous les faits, informations et documents dont il/elle pourrait avoir connaissance dans l'exercice de ses fonctions.");
            $section->addTextBreak();
            
            // Article 9
            $section->addText("ARTICLE 9 - RUPTURE DU CONTRAT", ['bold' => true]);
            $section->addText("A l'issue de la période d'essai, le présent contrat pourra être rompu par l'une ou l'autre des parties dans les conditions prévues par la législation en vigueur, sous réserve du respect du préavis fixé par la convention collective.");
            $section->addTextBreak();
            
            // Article 10
            $section->addText("ARTICLE 10 - RÉGIME DE PRÉVOYANCE ET MUTUELLE", ['bold' => true]);
            $section->addText("$gender $fullName sera affilié(e) dès son entrée dans l'entreprise au régime de prévoyance et de complémentaire santé en vigueur dans l'entreprise, selon les conditions générales prévues par ces régimes.");
            $section->addTextBreak();
            
            // Signatures
            $section->addText("Fait en double exemplaire originaux dont un pour chacune des parties.", null, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            $section->addText("A Paris, le " . date('d/m/Y'), null, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            $section->addTextBreak(2);
            
            // Créer une table pour les signatures
            $table = $section->addTable(['borderSize' => 0, 'cellMargin' => 80]);
            $table->addRow();
            
            // Cellule pour la signature de l'employeur
            $cell1 = $table->addCell(4000, ['valign' => 'center']);
            $cell1->addText('L\'employeur', ['bold' => true], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            $cell1->addText('M BRIAND Grégory', null, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            $cell1->addText('Pour la société', null, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            
            // Insérer l'image de signature de l'administrateur
            $adminSignaturePath = storage_path('app/public/signatures/admin_signature.png');
            if (file_exists($adminSignaturePath)) {
                try {
                    $tempDir = sys_get_temp_dir();
                    $tempImagePath = $tempDir . '/admin_signature_' . time() . '.png';
                    copy($adminSignaturePath, $tempImagePath);
                    
                    $cell1->addImage(
                        $tempImagePath,
                        ['width' => 150, 'height' => 75, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
                    );
                    
                    // Si la signature a une date
                    if ($contract->admin_signed_at) {
                        $cell1->addText('Le ' . date('d/m/Y à H:i', strtotime($contract->admin_signed_at)), 
                            ['size' => 8], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                    }
                } catch (\Exception $e) {
                    \Log::error('Erreur lors de l\'ajout de l\'image de signature admin: ' . $e->getMessage());
                    // Fallback à la version texte
                    $cell1->addText('SIGNATURE ÉLECTRONIQUE', ['italic' => true, 'bold' => true, 'color' => 'FFFFFF', 'bgcolor' => '333333'], 
                        ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                    $cell1->addText('Grégory BRIAND', ['bold' => true], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                }
            } else {
                // Fallback si l'image n'existe pas
                $cell1->addText('SIGNATURE ÉLECTRONIQUE', ['italic' => true, 'bold' => true, 'color' => 'FFFFFF', 'bgcolor' => '333333'], 
                    ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                $cell1->addText('Grégory BRIAND', ['bold' => true], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            }
            
            // Cellule pour l'espace entre les signatures
            $table->addCell(1000);
            
            // Cellule pour la signature de l'employé
            $cell2 = $table->addCell(4000, ['valign' => 'center']);
            $cell2->addText('L\'employé(e)', ['bold' => true], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            $cell2->addText(($contract->data->first_name ?? '') . ' ' . ($contract->data->last_name ?? ''), null, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            
            // Si l'employé a signé
            if ($contract->employee_signed_at && $contract->user_id) {
                // Chercher l'image de signature de l'employé
                $employeeSignaturePath = storage_path('app/public/signatures/' . $contract->user_id . '_employee.png');
                if (file_exists($employeeSignaturePath)) {
                    try {
                        $tempDir = sys_get_temp_dir();
                        $tempImagePath = $tempDir . '/employee_signature_' . time() . '.png';
                        copy($employeeSignaturePath, $tempImagePath);
                        
                        $cell2->addImage(
                            $tempImagePath,
                            ['width' => 150, 'height' => 75, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
                        );
                        
                        // Ajouter la date de signature
                        $cell2->addText('Le ' . date('d/m/Y à H:i', strtotime($contract->employee_signed_at)), 
                            ['size' => 8], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                    } catch (\Exception $e) {
                        \Log::error('Erreur lors de l\'ajout de l\'image de signature employé: ' . $e->getMessage());
                        // Fallback à la version texte
                        $cell2->addText('SIGNATURE ÉLECTRONIQUE', ['italic' => true, 'bold' => true, 'color' => 'FFFFFF', 'bgcolor' => '333333'], 
                            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                        $cell2->addText(($contract->data->first_name ?? '') . ' ' . ($contract->data->last_name ?? ''), 
                            ['bold' => true], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                    }
                } else {
                    // Fallback si l'image n'existe pas
                    $cell2->addText('SIGNATURE ÉLECTRONIQUE', ['italic' => true, 'bold' => true, 'color' => 'FFFFFF', 'bgcolor' => '333333'], 
                        ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                    $cell2->addText(($contract->data->first_name ?? '') . ' ' . ($contract->data->last_name ?? ''), 
                        ['bold' => true], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                }
            } else {
                $cell2->addText('___________________', null, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                $cell2->addText('(Signature non apposée)', ['italic' => true, 'size' => 8], 
                    ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            }
            
            // Générer un nom de fichier pour le document Word
            $filename = 'contrat_' . $contract->id . '_' . time() . '.docx';
            $filePath = storage_path('app/temp/' . $filename);
            
            // Créer le répertoire si nécessaire
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }
            
            // Sauvegarder le document
            $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
            $objWriter->save($filePath);
            
            // Retourner le document pour téléchargement
            return response()->download($filePath, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'Content-Disposition' => 'inline; filename="' . $filename . '"'
            ])->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            // En cas d'erreur, rediriger avec un message d'erreur
            \Log::error('Erreur génération Word: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return back()->with('error', 'Erreur lors de la génération du document: ' . $e->getMessage());
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
