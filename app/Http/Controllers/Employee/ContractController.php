<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\ContractData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\PhpWord;
use Dompdf\Dompdf;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\User;
use App\Temp_Fixes\SignatureHelper;

class ContractController extends Controller
{
    /**
     * Affiche la liste des contrats de l'employé
     */
    public function index()
    {
        $contract = Auth::user()->contract;
        if (!$contract) {
            return redirect()->route('home')->with('status', 'Vous n\'avez pas encore de contrat. Vous pouvez en créer un.');
        }
        return redirect()->route('employee.contracts.show', $contract);
    }

    /**
     * Affiche le formulaire de création d'un nouveau contrat
     */
    public function create()
    {
        // Vérifier si l'employé a déjà un contrat
        if (Auth::user()->contract) {
            return redirect()->route('home')->with('status', 'Vous avez déjà un contrat.');
        }
        
        // Trouver le template CDI
        $cdiTemplate = ContractTemplate::where('name', 'CDI')->first();
        if (!$cdiTemplate) {
            // Si le template CDI n'existe pas, utiliser le premier template disponible
            $cdiTemplate = ContractTemplate::first();
            if (!$cdiTemplate) {
                return redirect()->route('home')->with('status', 'Erreur: Aucun modèle de contrat disponible.');
            }
        }
        
        // Générer le titre du contrat au format NOM_PRENOM
        $userName = strtoupper(Auth::user()->name);
        $contractTitle = $userName . "_CDI";
        
        // Création du contrat avec le titre forcé et le template CDI
        $contract = Auth::user()->contract()->create([
            'contract_template_id' => $cdiTemplate->id,
            'title' => $contractTitle,
            'status' => 'draft',
        ]);

        // Créer l'entrée ContractData associée (vide)
        ContractData::create([
            'contract_id' => $contract->id,
            'field_name' => 'contract_data' // Valeur temporaire pour éviter l'erreur SQL
        ]);

        // Redirection directe vers le formulaire d'édition
        return redirect()->route('employee.contracts.edit', $contract)
            ->with('status', 'Votre contrat a été créé avec succès. Veuillez compléter les informations.');
    }

    /**
     * Enregistre un nouveau contrat
     */
    public function store(Request $request)
    {
        // Vérifier si l'employé a déjà un contrat
        if (Auth::user()->contract) {
            return redirect()->route('home')->with('status', 'Vous avez déjà un contrat.');
        }
        
        // Validation de base
        $request->validate([
            'contract_template_id' => 'required|exists:contract_templates,id',
            'address' => 'required|string',
            'postal_code' => 'required|string',
            'city' => 'required|string',
        ]);
        
        // Trouver le template CDI
        $cdiTemplate = ContractTemplate::where('name', 'CDI')->first();
        if (!$cdiTemplate) {
            // Si le template CDI n'existe pas, utiliser le premier template disponible
            $cdiTemplate = ContractTemplate::first();
            if (!$cdiTemplate) {
                return redirect()->route('home')->with('status', 'Erreur: Aucun modèle de contrat disponible.');
            }
        }
        
        // Générer le titre du contrat au format NOM_PRENOM
        $userName = strtoupper(Auth::user()->name);
        $contractTitle = $userName . "_CDI";
        
        // Création du contrat avec le titre forcé et le template CDI
        $contract = Auth::user()->contract()->create([
            'contract_template_id' => $cdiTemplate->id,
            'title' => $contractTitle,
            'status' => 'draft',
        ]);

        // Créer l'entrée ContractData associée avec les données d'adresse
        ContractData::create([
            'contract_id' => $contract->id,
            'address' => $request->input('address'),
            'postal_code' => $request->input('postal_code'),
            'city' => $request->input('city'),
            'field_name' => 'contract_data' // Valeur temporaire pour éviter l'erreur SQL
        ]);

        // Redirection vers le formulaire de saisie des données
        return redirect()->route('employee.contracts.edit', $contract)
            ->with('status', 'Votre contrat a été créé avec succès. Veuillez compléter les informations manquantes.');
    }

    /**
     * Affiche les détails d'un contrat
     */
    public function show(Contract $contract)
    {
        // Vérifier que l'utilisateur est bien le propriétaire du contrat
        if ($contract->user_id !== Auth::id() && !Auth::user()->is_admin) {
            return redirect()->route('home')->with('status', 'Vous n\'êtes pas autorisé à voir ce contrat.');
        }

        // S'assurer que les données du contrat sont chargées
        $contract->load(['template', 'data']);
        
        // Créer une entrée ContractData si elle n'existe pas encore
        if (!$contract->data) {
            ContractData::create([
                'contract_id' => $contract->id
            ]);
            // Recharger la relation
            $contract->refresh();
        }

        return view('employee.contracts.show', [
            'contract' => $contract
        ]);
    }

    /**
     * Affiche le formulaire d'édition d'un contrat
     */
    public function edit(Contract $contract)
    {
        // Vérification que le contrat appartient à l'employé connecté
        if ($contract->user_id !== Auth::id()) {
            abort(403, 'Non autorisé');
        }

        // Vérification que le contrat peut être modifié
        if (!in_array($contract->status, ['draft', 'rejected'])) {
            return redirect()->route('employee.contracts.show', $contract)
                ->with('error', 'Ce contrat ne peut plus être modifié.');
        }

        $contract->load(['template', 'data']);
        return view('employee.contracts.edit', compact('contract'));
    }

    /**
     * Met à jour les informations d'un contrat existant
     */
    public function update(Request $request, Contract $contract)
    {
        // Vérifier que l'utilisateur est bien le propriétaire du contrat
        if ($contract->user_id !== Auth::id()) {
            return redirect()->route('home')->with('status', 'Vous n\'êtes pas autorisé à modifier ce contrat.');
        }
        
        // Vérifier que le contrat est modifiable (status = draft)
        if ($contract->status !== 'draft' && $contract->status !== 'rejected') {
            return redirect()->route('employee.contracts.show', $contract)
                ->with('status', 'Ce contrat ne peut plus être modifié car il a déjà été soumis.');
        }
        
        // Valider les données du formulaire
        $validated = $request->validate([
            'data.first_name' => 'required|string|max:255',
            'data.last_name' => 'required|string|max:255',
            'data.gender' => 'required|in:M,F',
            'data.birth_date' => 'required|date',
            'data.birth_place' => 'required|string|max:255',
            'data.nationality' => 'required|string|max:255',
            'data.address' => 'required|string|max:255',
            'data.postal_code' => 'required|string|max:10',
            'data.city' => 'required|string|max:255',
            'data.social_security_number' => 'required|string|max:255',
            'data.email' => 'required|email|max:255',
            'data.phone' => 'required|string|max:20',
            'data.bank_details' => 'required|string',
            'employee_photo' => 'nullable|image|mimes:jpeg,png,gif|max:2048',
        ]);
        
        // Construire le nom complet
        $fullName = $validated['data']['first_name'] . ' ' . $validated['data']['last_name'];
        
        // Construire l'adresse complète pour l'affichage
        $fullAddress = $validated['data']['address'] . ', ' . $validated['data']['postal_code'] . ' ' . $validated['data']['city'];
        
        // Préparer les données à mettre à jour
        $dataToUpdate = [
            'full_name' => $fullName,
            'first_name' => $validated['data']['first_name'],
            'last_name' => $validated['data']['last_name'],
            'gender' => $validated['data']['gender'],
            'birth_date' => $validated['data']['birth_date'],
            'birth_place' => $validated['data']['birth_place'],
            'nationality' => $validated['data']['nationality'],
            'address' => $validated['data']['address'],
            'postal_code' => $validated['data']['postal_code'],
            'city' => $validated['data']['city'],
            'social_security_number' => $validated['data']['social_security_number'],
            'email' => $validated['data']['email'],
            'phone' => $validated['data']['phone'],
            'bank_details' => $validated['data']['bank_details'],
        ];
        
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
        }
        
        // Récupérer ou créer l'entrée ContractData
        $contractData = ContractData::updateOrCreate(
            ['contract_id' => $contract->id],
            $dataToUpdate
        );
        
        // Si l'action est de soumettre, rediriger vers la méthode submit
        if ($request->input('action') === 'submit') {
            // Mettre à jour le statut du contrat directement
            $contract->update([
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);
            
            return redirect()->route('employee.contracts.show', $contract)
                ->with('success', 'Votre contrat a été enregistré et soumis avec succès.');
        }
        
        return redirect()->route('employee.contracts.show', $contract)
            ->with('status', 'Informations du contrat mises à jour avec succès.');
    }

    /**
     * Soumet un contrat pour révision
     */
    public function submit(Contract $contract)
    {
        // Vérification que le contrat appartient à l'employé connecté
        if ($contract->user_id !== Auth::id()) {
            abort(403, 'Non autorisé');
        }

        // Vérification que le contrat peut être soumis
        if (!in_array($contract->status, ['draft', 'rejected'])) {
            return redirect()->route('employee.contracts.show', $contract)
                ->with('error', 'Ce contrat ne peut pas être soumis.');
        }

        // Vérifier que toutes les données requises sont présentes
        $contractData = $contract->data;
        if (!$contractData || 
            !$contractData->first_name || 
            !$contractData->last_name || 
            !$contractData->birth_date || 
            !$contractData->address) {
            return redirect()->route('employee.contracts.edit', $contract)
                ->with('error', 'Veuillez compléter toutes les informations requises avant de soumettre le contrat.');
        }

        $contract->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        // Notification aux administrateurs
        // $admins = User::where('is_admin', true)->get();
        // Notification::send($admins, new \App\Notifications\NewContractSubmitted($contract));

        return redirect()->route('employee.contracts.show', $contract)
            ->with('success', 'Votre contrat a été soumis avec succès.');
    }

    /**
     * Prévisualise le contrat avant signature
     */
    public function preview(Contract $contract)
    {
        // Vérifier que l'utilisateur est bien le propriétaire du contrat
        if ($contract->user_id !== Auth::id()) {
            return redirect()->route('home')->with('status', 'Vous n\'êtes pas autorisé à prévisualiser ce contrat.');
        }
        
        try {
            // Augmenter le temps d'exécution maximum pour éviter les timeouts
            set_time_limit(300);
            ini_set('memory_limit', '512M');
            
            // Préparer les données pour le template
            $contractData = [
                'contract' => $contract,
                'user' => $contract->user,
                'admin' => $contract->admin ?? User::where('is_admin', true)->first(),
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
                // Pour les contrats standards, utiliser le template existant
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
            return back()->with('error', 'Erreur lors de la prévisualisation du contrat: ' . $e->getMessage());
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
        $sections = $phpWord->getSections();
        foreach ($sections as $section) {
            $elements = $section->getElements();
            foreach ($elements as $element) {
                if ($element instanceof \PhpOffice\PhpWord\Element\Text) {
                    $text = $element->getText();
                    foreach ($placeholders as $placeholder => $value) {
                        $text = str_replace($placeholder, $value, $text);
                    }
                    $element->setText($text);
                }
            }
        }
    }

    /**
     * Signe le contrat (employé)
     */
    public function sign(Request $request, Contract $contract)
    {
        // Vérifier que l'utilisateur est bien le propriétaire du contrat
        if ($contract->user_id !== Auth::id()) {
            return redirect()->route('home')->with('status', 'Vous n\'êtes pas autorisé à signer ce contrat.');
        }
        
        // Vérifier que le contrat est signable (status = admin_signed)
        if ($contract->status !== 'admin_signed') {
            return redirect()->route('employee.contracts.show', $contract)
                ->with('status', 'Ce contrat ne peut pas être signé pour le moment.');
        }
        
        // Traiter la signature dessinée
        if ($request->has('employee_signature') && strpos($request->employee_signature, 'data:image/png;base64,') === 0) {
            // Sauvegarder l'image de la signature
            $signatureData = $request->employee_signature;
            
            // Créer les dossiers pour les signatures si nécessaires
            $privateDir = storage_path('app/private/private/signatures');
            if (!file_exists($privateDir)) {
                mkdir($privateDir, 0755, true);
            }
            
            $publicDir = storage_path('app/public/signatures');
            if (!file_exists($publicDir)) {
                mkdir($publicDir, 0755, true);
            }
            
            // Utiliser l'ID utilisateur pour nommer le fichier de signature
            $userId = $contract->user_id;
            
            // Sauvegarder dans le dossier privé pour la sécurité
            $privateSignaturePath = 'private/private/signatures/' . $userId . '_employee.png';
            Storage::put($privateSignaturePath, base64_decode(explode(',', $signatureData)[1]));
            
            // Sauvegarder également dans le dossier public pour l'affichage
            $publicSignaturePath = 'public/signatures/' . $userId . '_employee.png';
            Storage::put($publicSignaturePath, base64_decode(explode(',', $signatureData)[1]));
            
            // S'assurer que le lien symbolique existe
            if (!file_exists(public_path('storage'))) {
                \Artisan::call('storage:link');
            }
            
            // Générer le chemin du fichier pour la signature
            $signaturePath = 'signatures/' . $userId . '_employee.png';
            
            // Mettre à jour le contrat avec le chemin du fichier (et non l'URL complète)
            $contract->update([
                'employee_signature' => $signaturePath,
                'employee_signed_at' => now(),
                'status' => 'completed' // Changer à 'completed' puisque les deux parties ont signé
            ]);
            
            // Pour les avenants, générer automatiquement le PDF final
            if ($contract->isAvenant()) {
                try {
                    // Chercher le AdminContractController pour générer le PDF
                    $adminController = app(\App\Http\Controllers\Admin\ContractController::class);
                    if (method_exists($adminController, 'generate')) {
                        $adminController->generate(new Request(), $contract);
                        \Log::info('Avenant PDF généré automatiquement via AdminContractController', ['contract_id' => $contract->id]);
                    } else {
                        // Utiliser la méthode preview du contrôleur actuel comme alternative
                        $pdfResponse = $this->preview($contract);
                        \Log::info('Avenant PDF généré via preview', ['contract_id' => $contract->id]);
                    }
                } catch (\Exception $e) {
                    \Log::error('Erreur lors de la génération automatique du PDF d\'avenant', [
                        'contract_id' => $contract->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
            
            // Notification à l'administrateur que le contrat a été signé
            // Commenté pour éviter les erreurs si la table jobs n'existe pas
            // User::where('is_admin', true)->first()->notify(new \App\Notifications\ContractSigned($contract));
            
            return redirect()->route('employee.contracts.show', $contract)
                ->with('success', $contract->isAvenant() ? 'L\'avenant a été signé avec succès.' : 'Le contrat a été signé avec succès.');
        }
        
        return redirect()->route('employee.contracts.show', $contract)
            ->with('error', 'Aucune signature n\'a été fournie.');
    }

    /**
     * Télécharge le contrat finalisé
     */
    public function download(Contract $contract)
    {
        // Vérifier que l'utilisateur est bien le propriétaire du contrat
        if ($contract->user_id !== Auth::id() && !Auth::user()->is_admin) {
            return redirect()->route('home')->with('status', 'Vous n\'êtes pas autorisé à télécharger ce contrat.');
        }
        
        try {
            // Augmenter le temps d'exécution maximum pour éviter les timeouts
            set_time_limit(300);
            ini_set('memory_limit', '512M');
            
            // Si le contrat a déjà un document final et qu'il est accessible, le télécharger directement
            if ($contract->final_document_path && Storage::exists($contract->final_document_path)) {
                $path = storage_path('app/' . $contract->final_document_path);
                $filename = 'contrat_' . $contract->id . '_' . str_replace(' ', '_', $contract->user->name) . '.pdf';
                
                return response()->download($path, $filename, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"'
                ]);
            }
            
            // Sinon, générer un nouveau PDF avec les données les plus récentes
            // Utiliser PhpWord pour une meilleure génération du document
            $phpWord = new \PhpOffice\PhpWord\PhpWord();
            
            // Définir les styles de base
            $phpWord->setDefaultFontName('Arial');
            $phpWord->setDefaultFontSize(11);

            // Ajouter une section
            $section = $phpWord->addSection([
                'marginTop' => 600,
                'marginLeft' => 600,
                'marginRight' => 600,
                'marginBottom' => 600,
                'spaceBefore' => 0,
                'spaceAfter' => 0
            ]);
            
            // Définir les styles pour tout le document - plus compacts comme dans l'exemple
            $titleStyle = ['bold' => true, 'size' => 14];
            $titleParaStyle = ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 120];
            $articleStyle = ['bold' => true];
            $articleParaStyle = ['keepNext' => true, 'spaceBefore' => 0, 'spaceAfter' => 60];
            $normalParaStyle = ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH, 'spaceBefore' => 0, 'spaceAfter' => 60];
            
            // Charger les données du contrat
            $contractData = $contract->data;
            if (!$contractData) {
                return redirect()->back()->with('error', 'Données du contrat non trouvées.');
            }
            
            // Ajouter le titre avec moins d'espacement
            $section->addText('CONTRAT DE TRAVAIL A DUREE INDETERMINEE', 
                $titleStyle, 
                $titleParaStyle
            );
            
            // Introduction avec moins d'espacement
            $section->addText('Entre Les Soussignés :', ['bold' => true], $normalParaStyle);
            
            // Informations de la société avec moins d'espacement
            $section->addText('La Société WHAT EVER SAS, société à responsabilité limitée au capital de 200 000 Euros dont le siège social est situé 54 Avenue Kléber 75016 PARIS,', null, $normalParaStyle);
            $section->addText('Immatriculée au Registre du Commerce et des Sociétés de Paris sous le n° 439 077 462 00026,', null, $normalParaStyle);
            $section->addText('Représentée par Monsieur BRIAND Grégory ayant tous pouvoirs à l\'effet des présentes,', null, $normalParaStyle);
            $section->addText('Cotisant à l\'URSSAF de Paris sous le n° 758 2320572850010116', null, $normalParaStyle);
            $section->addText('d\'une part,', null, $normalParaStyle);
            
            $section->addText('et,', null, $normalParaStyle);
            
            // Informations de l'employé avec mise en page compacte
            $gender = ($contractData->gender ?? '') == 'M' ? 'Monsieur' : 'Madame';
            $fullName = $contractData->full_name ?? ($contractData->first_name ?? '') . ' ' . ($contractData->last_name ?? '');
            $birthDate = $contractData->birth_date ? date('d/m/Y', strtotime($contractData->birth_date)) : '___________';
            $birthPlace = $contractData->birth_place ?? '___________';
            $nationality = $contractData->nationality ?? '___________';
            $ssn = $contractData->social_security_number ?? '___________';
            $address = $contractData->address ?? '___________';
            $postalCode = $contractData->postal_code ?? '___________';
            $city = $contractData->city ?? '___________';
            
            $section->addText("$gender $fullName", null, $normalParaStyle);
            $section->addText("Né" . ($gender !== 'Monsieur' ? 'e' : '') . " le $birthDate à $birthPlace,", null, $normalParaStyle);
            $section->addText("De nationalité $nationality,", null, $normalParaStyle);
            $section->addText("Numéro Sécurité Sociale : $ssn", null, $normalParaStyle);
            $section->addText("Demeurant : $address, $postalCode $city", null, $normalParaStyle);
            $section->addText("d'autre part,", null, $normalParaStyle);
            
            $section->addText("Il a été convenu ce qui suit :", null, $normalParaStyle);
            
            // Articles du contrat
            $section->addText("ARTICLE 1 - ENGAGEMENT", $articleStyle, $articleParaStyle);
            $section->addText("Le présent contrat est régi par les dispositions de la convention collective de la restauration rapide et du code du travail avec pour obligation de s'acquitter des avantages repas s'ils sont consommés, ou alors de recevoir une indemnité compensatoire, si les créneaux horaires de travail le justifient.", null, $normalParaStyle);
            
            $section->addTextBreak(1);
            
            $section->addText("ARTICLE 2 - DUREE DU CONTRAT - PÉRIODE D'ESSAI", $articleStyle, $articleParaStyle);
            $signingDate = $contractData->contract_signing_date ? date('d/m/Y', strtotime($contractData->contract_signing_date)) : '___________';
            $trialMonths = $contractData->trial_period_months ?? '___________';
            $trialEndDate = $contractData->trial_period_end_date ? date('d/m/Y', strtotime($contractData->trial_period_end_date)) : '___________';
            
            $section->addText("Le présent contrat est conclu pour une durée indéterminée à compter du $signingDate.", null, $normalParaStyle);
            $section->addText("Il ne deviendra définitif qu'à l'issue d'une période d'essai de $trialMonths, soit jusqu'au $trialEndDate, renouvelable 1 mois.", null, $normalParaStyle);
            $section->addText("Durant cette période, chacune des parties pourra, à tout moment, mettre fin au présent contrat sans qu'aucune indemnité ni préavis ne soient dus.", null, $normalParaStyle);
            $section->addText("Au-delà de la période d'essai, le présent contrat pourra être rompu à tout moment par l'une ou l'autre des parties, moyennant un préavis dont la durée, en cas de licenciement ou de démission est fixée comme suit :", null, $normalParaStyle);
            $section->addText("- pour le personnel de moins de six mois d'ancienneté dans l'entreprise : huit jours.", null, $normalParaStyle);
            $section->addText("- pour le personnel ayant de six mois à deux ans d'ancienneté : quinze jours pour démission, un mois pour licenciement.", null, $normalParaStyle);
            $section->addText("- pour le personnel ayant au moins deux ans d'ancienneté : un mois pour démission, deux mois pour licenciement.", null, $normalParaStyle);
            
            $section->addTextBreak(1);
            
            // Article 3
            $section->addText("ARTICLE 3 - FONCTIONS", $articleStyle, $articleParaStyle);
            $section->addText("$gender $fullName est employé(e) en qualité d'Employée de restauration.", null, $normalParaStyle);
            $section->addText("$gender $fullName exercera ses fonctions dans le cadre des directives écrites ou verbales qui lui seront données par M Briand ou toute personne qui pourrait lui être substituée.", null, $normalParaStyle);
            
            $section->addTextBreak(1);
            
            // Article 4
            $section->addText("ARTICLE 4 - REMUNERATION", $articleStyle, $articleParaStyle);
            $salary = $contractData->monthly_gross_salary ?? '___________';
            $monthlyHours = $contractData->monthly_hours ?? '___________';
            $section->addText("La rémunération mensuelle brute de $gender $fullName sera de $salary euros pour $monthlyHours heures mensuel.", null, $normalParaStyle);
            
            $section->addTextBreak(1);
            
            // Article 5
            $section->addText("ARTICLE 5 - HORAIRES DE TRAVAIL", $articleStyle, $articleParaStyle);
            $weeklyHours = $contractData->weekly_hours ?? '___________';
            $section->addText("La durée de travail sera de $weeklyHours heures hebdomadaires, réparties du lundi au dimanche.", null, $normalParaStyle);
            $section->addText("Les jours et horaires de travail seront indiqués à $gender $fullName, par le biais de plannings hebdomadaires, établis et affichés à l'avance, dans chaque établissement.", null, $normalParaStyle);
            $section->addText("Il est convenu que l'horaire de travail de $gender $fullName sera susceptible de modifications en fonction des nécessités d'organisation du service et des conditions particulières de travail.", null, $normalParaStyle);
            
            // Calcul des heures complémentaires (20% des heures hebdomadaires)
            $weeklyOvertimeLimit = 0;
            $monthlyOvertimeLimit = 0;
            
            // Vérifier si $weeklyHours est un nombre avant de faire les calculs
            if (is_numeric($weeklyHours)) {
                $weeklyHoursFloat = (float)$weeklyHours;
                $weeklyOvertimeLimit = $weeklyHoursFloat * 0.2;
                $monthlyOvertimeLimit = $weeklyOvertimeLimit * 4;
                $weeklyOvertimeLimit = number_format($weeklyOvertimeLimit, 2);
                $monthlyOvertimeLimit = number_format($monthlyOvertimeLimit, 2);
            }
            
            $section->addText("Par ailleurs, $gender $fullName pourra être amenée à effectuer, à titre exceptionnel, un quota d'heures complémentaires. Ce dernier ne pouvant excéder 20% du quota d'heures mensuelles de la salariée, soit par $weeklyOvertimeLimit semaine ($monthlyOvertimeLimit par mois).", null, $normalParaStyle);
            
            $section->addTextBreak(1);
            
            // Article 6
            $section->addText("ARTICLE 6 – CONFIDENTIALITE", $articleStyle, $articleParaStyle);
            $section->addText("$gender $fullName s'engage à observer la discrétion la plus stricte sur les informations se rapportant aux activités de la société et de ses clients auxquelles elle aura accès à l'occasion et dans le cadre de ses fonctions.", null, $normalParaStyle);
            
            $section->addTextBreak(1);
            
            // Article 7
            $section->addText("ARTICLE 7- LIEU DE TRAVAIL", $articleStyle, $articleParaStyle);
            $section->addText("$gender $fullName sera amenée à exercer ses fonctions dans les différents établissements de notre enseigne : 360 rue de Flins, 78410 Bouafle, 54 avenue de Kléber 75016 Paris, 4 rue de Londres 75009 Paris, 135 rue Montmartre 75002 Paris, 23 rue Taitbout 75009 Paris, 7 rue de Petites Ecuries Paris 10, 38 rue Ybry Neuilly sur Seine, 24 rue du 4 Septembre, ainsi que sur nos différents stands au cours d'événements ponctuels.", null, $normalParaStyle);
            
            $section->addTextBreak(1);
            
            // Article 8
            $section->addText("ARTICLE 8 – OBLIGATIONS de $gender $fullName", $articleStyle, $articleParaStyle);
            $section->addText("Pendant la durée de son contrat $gender $fullName s'engage à respecter les instructions qui pourront lui être données par la société et à se conformer aux règles relatives à l'organisation et au fonctionnement interne de la société.", null, $normalParaStyle);
            $section->addText("En cas d'empêchement pour lui d'effectuer son travail, $gender $fullName est tenue d'en aviser la société dans les 48 heures, en indiquant la durée prévisible de cet empêchement.", null, $normalParaStyle);
            $section->addText("Si cette absence est justifiée par la maladie ou l'accident, $gender $fullName devra en outre faire parvenir un certificat médical indiquant la durée probable du repos dans les 3 jours.", null, $normalParaStyle);
            $section->addText("La même formalité est requise en cas de prolongation de l'arrêt de travail.", null, $normalParaStyle);
            $section->addText("$gender $fullName devra informer la société de tous changements qui interviendraient dans les situations qu'elle a signalées lors de son engagement.", null, $normalParaStyle);
            $section->addText("$gender $fullName s'engage à respecter scrupuleusement les normes et directives de qualité des tâches qui lui seront imparties.", null, $normalParaStyle);
            $section->addText("Des défauts de qualité graves ou répétés pourront entraîner des sanctions disciplinaires.", null, $normalParaStyle);
            
            $section->addTextBreak(1);
            
            // Article 9
            $section->addText("ARTICLE 9 - CONDITIONS D'EXÉCUTION DU CONTRAT", $articleStyle, $articleParaStyle);
            $section->addText("$gender $fullName s'engage à se conformer aux instructions de la Direction.", null, $normalParaStyle);
            $section->addText("Compte tenu de la nature de son emploi comportant un contact permanent avec la clientèle et de la nécessité pour la société de conserver sa bonne image de marque, $gender $fullName s'engage à porter en toutes circonstances une tenue correcte et de bon aloi.", null, $normalParaStyle);
            $section->addText("Le refus de se conformer à ces prescriptions sera constitutif d'une faute susceptible d'être sanctionnée.", null, $normalParaStyle);
            $section->addText("$gender $fullName devra faire connaître à l'entreprise sans délai toute modification postérieure à son engagement qui pourrait intervenir dans son état civil, sa situation de famille, son adresse.", null, $normalParaStyle);
            
            $section->addTextBreak(1);
            
            // Article 10
            $section->addText("ARTICLE 10 - CONGÉS PAYÉS", $articleStyle, $articleParaStyle);
            $section->addText("$gender $fullName bénéficiera des congés payés légaux, soit trente jours ouvrables par période du 1er juin au 31 mai suivant.", null, $normalParaStyle);
            $section->addText("La période de congés payés sera fixée chaque année en tenant compte des nécessités du service.", null, $normalParaStyle);
            
            $section->addTextBreak(1);
            
            // Article 11
            $section->addText("ARTICLE 11 - STATUT", $articleStyle, $articleParaStyle);
            $section->addText("$gender $fullName bénéficiera des lois sociales instituées en faveur des salariés, notamment en matière de Sécurité Sociale et en ce qui concerne le régime de retraite complémentaire pour lequel elle est affiliée.", null, $normalParaStyle);
            $section->addText("$gender $fullName relève de la catégorie \"employé\" et sera affiliée dès son entrée au sein de la société au contrat retraite complémentaire Humanis.", null, $normalParaStyle);
            $section->addText("$gender $fullName relève de la catégorie \"employé\" et sera affiliée dès son entrée au sein de la société au contrat Prévoyance AG2R.", null, $normalParaStyle);
            $section->addText("$gender $fullName relève de la catégorie \"employé\" et sera affiliée dès son entrée au sein de la société à la Mutuelle APRIL ENTREPRISE.", null, $normalParaStyle);
            $section->addText("Pour toutes les dispositions non prévues par les présentes, les parties déclarent se référer à la convention collective de la restauration rapide, au code du travail ainsi qu'aux lois et règlements applicables dans la société.", null, $normalParaStyle);
            
            $section->addTextBreak(2);
            
            // Signatures - mise en page similaire à l'exemple
            $section->addText("Fait en double exemplaire originaux dont un pour chacune des parties.", null, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0]);
            $section->addTextBreak(1);
            $section->addText("A Paris, le " . date('d/m/Y'), null, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 100]);
            
            // Table des signatures plus simple comme dans l'exemple
            $table = $section->addTable(['borderSize' => 0, 'cellMargin' => 0, 'width' => 100 * 50, 'layout' => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED]);
            $table->addRow();
            
            // Cellule pour l'employeur - Signature clairement sous le nom
            $cell1 = $table->addCell(2500, ['valign' => 'top', 'borderSize' => 0]);
            $cell1->addText('M BRIAND Grégory', null, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT, 'spaceBefore' => 0, 'spaceAfter' => 60]);
            
            // Ajouter signature admin si existe ET si l'admin a signé le contrat
            $adminSignaturePath = storage_path('app/public/signatures/admin_signature.png');
            if ($contract->admin_signed_at && file_exists($adminSignaturePath)) {
                try {
                    $tempDir = sys_get_temp_dir();
                    $tempImagePath = $tempDir . '/admin_signature_' . time() . '.png';
                    copy($adminSignaturePath, $tempImagePath);
                    
                    $cell1->addImage(
                        $tempImagePath,
                        ['width' => 100, 'height' => 50, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]
                    );
                } catch (\Exception $e) {
                    // Pas de fallback visuel si l'image ne peut être chargée
                    \Log::error('Erreur lors du chargement de la signature admin: ' . $e->getMessage());
                }
            }
            
            $cell1->addText('Pour la société', null, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT, 'spaceBefore' => 30, 'spaceAfter' => 0]);
            
            // Cellule pour l'employé - Signature clairement sous le nom
            $cell2 = $table->addCell(2500, ['valign' => 'top', 'borderSize' => 0]);
            $cell2->addText("$gender $fullName", null, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT, 'spaceBefore' => 0, 'spaceAfter' => 60]);
            
            // Ajouter signature si existe
            if ($contract->employee_signed_at && $contract->user_id) {
                $employeeSignaturePath = storage_path('app/public/signatures/' . $contract->user_id . '_employee.png');
                if (file_exists($employeeSignaturePath)) {
                    try {
                        $tempDir = sys_get_temp_dir();
                        $tempImagePath = $tempDir . '/employee_signature_' . time() . '.png';
                        copy($employeeSignaturePath, $tempImagePath);
                        
                        $cell2->addImage(
                            $tempImagePath,
                            ['width' => 100, 'height' => 50, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]
                        );
                    } catch (\Exception $e) {
                        // Pas de fallback visuel si l'image ne peut être chargée
                        \Log::error('Erreur lors du chargement de la signature employé: ' . $e->getMessage());
                    }
                }
            }
            
            // Générer un nom de fichier pour le document PDF
            $filename = 'contrat_' . $contract->id . '_' . str_replace(' ', '_', $contract->user->name) . '.pdf';
            $tempWordPath = storage_path('app/temp/temp_word_' . time() . '.docx');
            $pdfPath = storage_path('app/temp/' . $filename);
            
            // Créer le répertoire si nécessaire
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }
            
            // Sauvegarder le document Word temporaire
            $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
            $objWriter->save($tempWordPath);
            
            // Configurer le moteur de rendu PDF
            Settings::setPdfRendererName(Settings::PDF_RENDERER_DOMPDF);
            Settings::setPdfRendererPath(base_path('vendor/dompdf/dompdf'));
            
            // Convertir en PDF
            $pdfWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'PDF');
            $pdfWriter->save($pdfPath);
            
            // Supprimer le fichier Word temporaire
            if (file_exists($tempWordPath)) {
                unlink($tempWordPath);
            }
            
            // Sauvegarder le chemin du document final dans la base de données si ce n'est pas déjà fait
            if (!$contract->final_document_path) {
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
            $pdfPath = 'contracts/' . $filename;
            $privatePdfPath = 'private/contracts/' . $filename;
            
                // Copier le PDF temporaire dans les emplacements permanents
                copy(storage_path('app/temp/' . $filename), storage_path('app/' . $pdfPath));
                copy(storage_path('app/temp/' . $filename), storage_path('app/' . $privatePdfPath));
                
                // Mettre à jour le chemin du document final dans la base de données
                $contract->final_document_path = $pdfPath;
                $contract->generated_at = now();
                $contract->save();
            }
            
            // Télécharger le PDF
            return response()->download($pdfPath, $filename, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            // En cas d'erreur, rediriger avec un message d'erreur
            \Log::error('Erreur lors de la génération du PDF: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors de la génération du PDF: ' . $e->getMessage());
        }
    }

    /**
     * Prévisualise le contrat avant signature
     */
    public function previewBeforeSigning(Contract $contract)
    {
        // Vérifier que l'utilisateur est bien le propriétaire du contrat
        if ($contract->user_id !== Auth::id() && !Auth::user()->is_admin) {
            return redirect()->route('home')->with('status', 'Vous n\'êtes pas autorisé à voir ce contrat.');
        }
        
        // Vérifier que le contrat est au statut admin_signed
        if ($contract->status !== 'admin_signed' && !Auth::user()->is_admin) {
            return back()->with('status', 'Ce contrat n\'est pas encore prêt pour prévisualisation.');
        }
        
        try {
            // Chemin du modèle
            $templatePath = storage_path('app/' . $contract->template->file_path);
            
            // Créer le processeur de template
            $templateProcessor = new TemplateProcessor($templatePath);
            
            // Récupérer toutes les données du contrat
            $contractData = $contract->data;
            
            // Remplacer les variables standards dans le template
            if ($contractData) {
                $properties = $contractData->getAttributes();
                foreach ($properties as $key => $value) {
                    // Ne pas inclure les clés qui ne sont pas des variables
                    if (in_array($key, ['id', 'contract_id', 'created_at', 'updated_at'])) {
                        continue;
                    }
                    
                    // Formater les dates
                    if (in_array($key, ['birth_date', 'contract_start_date', 'contract_signing_date', 'trial_period_end_date']) && $value) {
                        if ($value instanceof \Carbon\Carbon) {
                            $value = $value->format('d/m/Y');
                        } elseif (strtotime($value)) {
                            $value = date('d/m/Y', strtotime($value));
                        }
                    }
                    
                    // Formater les montants
                    if (in_array($key, ['hourly_rate', 'monthly_gross_salary', 'monthly_overtime', 'weekly_overtime']) && $value) {
                        $value = number_format((float)$value, 2, ',', ' ') . ' €';
                    }
                    
                    $templateProcessor->setValue($key, $value ?? '');
                }
            }
            
            // Ajouter les informations admin
            $templateProcessor->setValue('admin_signature', $contract->admin_signature ?? '');
            $templateProcessor->setValue('admin_signed_at', $contract->admin_signed_at ? $contract->admin_signed_at->format('d/m/Y') : '');
            
            // Ajouter les emplacements pour la signature employé
            $templateProcessor->setValue('employee_signature', '[Votre signature ici]');
            $templateProcessor->setValue('employee_signed_at', '[Date de signature]');
            
            // Générer un nom de fichier temporaire unique pour la prévisualisation
            $fileName = 'preview_' . $contract->id . '_' . time() . '.docx';
            $tempFilePath = storage_path('app/temp/' . $fileName);
            
            // Sauvegarder le document généré
            $templateProcessor->saveAs($tempFilePath);
            
            // Retourner le fichier pour prévisualisation
            return response()->file($tempFilePath)->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            return back()->with('status', 'Erreur lors de la génération de la prévisualisation: ' . $e->getMessage());
        }
    }

    /**
     * Supprime un contrat (uniquement à l'état de brouillon)
     */
    public function destroy(Contract $contract)
    {
        // Vérifier que le contrat appartient à l'employé connecté
        if ($contract->user_id !== Auth::id()) {
            abort(403, 'Non autorisé');
        }
        
        // Vérifier que le contrat est à l'état de brouillon
        if ($contract->status !== 'draft') {
            return redirect()->route('employee.contracts.show', $contract)
                ->with('error', 'Seuls les contrats à l\'état de brouillon peuvent être supprimés.');
        }
        
        // Supprimer les données associées au contrat
        if ($contract->data) {
            $contract->data->delete();
        }
        
        // Supprimer le contrat
        $contract->delete();
        
        return redirect()->route('employee.contracts.index')
            ->with('success', 'Le contrat a été supprimé avec succès.');
    }

    /**
     * Affiche la liste des avenants associés à un contrat
     */
    public function contractAvenants(Contract $contract)
    {
        // Vérifier que l'utilisateur est bien le propriétaire du contrat
        if ($contract->user_id !== Auth::id()) {
            return redirect()->route('home')->with('status', 'Vous n\'êtes pas autorisé à voir les avenants de ce contrat.');
        }
        
        // Si c'est déjà un avenant, rediriger vers le contrat parent
        if ($contract->isAvenant()) {
            return redirect()->route('employee.contracts.avenants', $contract->parentContract)
                ->with('info', 'Redirection vers le contrat principal.');
        }
        
        // Charger les avenants associés au contrat
        $contract->load(['avenants' => function($query) {
            $query->orderBy('avenant_number', 'asc');
        }]);
        
        return view('employee.contracts.avenants', compact('contract'));
    }
    
    /**
     * Affiche les détails d'un avenant spécifique
     */
    public function showAvenant(Contract $avenant)
    {
        // Vérifier que l'utilisateur est bien le propriétaire de l'avenant
        if ($avenant->user_id !== Auth::id()) {
            return redirect()->route('home')->with('status', 'Vous n\'êtes pas autorisé à voir cet avenant.');
        }
        
        // Vérifier que c'est bien un avenant
        if (!$avenant->isAvenant()) {
            return redirect()->route('employee.contracts.show', $avenant)
                ->with('error', 'Ce document n\'est pas un avenant.');
        }
        
        // Charger les relations nécessaires
        $avenant->load(['parentContract', 'data']);
        
        return view('employee.contracts.avenant', compact('avenant'));
    }
}
