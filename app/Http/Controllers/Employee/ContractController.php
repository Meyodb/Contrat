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
     * Prévisualise le contrat
     */
    public function preview(Contract $contract)
    {
        // Vérifier que l'utilisateur est bien le propriétaire du contrat
        if ($contract->user_id !== Auth::id() && !Auth::user()->is_admin) {
            return redirect()->route('home')->with('status', 'Vous n\'êtes pas autorisé à voir ce contrat.');
        }
        
        try {
            // Charger les données du contrat
            $contractData = ContractData::where('contract_id', $contract->id)->first();
            if (!$contractData) {
                return redirect()->back()->with('error', 'Données du contrat non trouvées.');
            }
            
            // Configuration optimisée de DomPDF
            $options = new \Dompdf\Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $options->set('isPhpEnabled', true);
            $options->set('defaultFont', 'Arial');
            $options->set('isFontSubsettingEnabled', true);
            $options->set('dpi', 96);
            
            // Préparer les chemins des signatures sans utiliser GD
            $adminSignatureExists = file_exists(public_path('storage/signatures/admin_signature.png'));
            $employeeSignatureExists = $contract->employee_signature ? file_exists(public_path('storage/' . $contract->employee_signature)) : false;
            
            // Générer le HTML du contrat
            $html = view('pdf.cdi-template', [
                'data' => $contractData, 
                'contract' => $contract,
                'admin_signature' => $adminSignatureExists,
                'employee_signature' => $employeeSignatureExists ? $contract->employee_signature : null
            ])->render();
            
            // Charger le HTML dans DomPDF
            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($html);
            
            // Rendre le PDF
            $dompdf->render();
            
            // Générer le PDF en mémoire
            $output = $dompdf->output();
            
            // Retourner le PDF pour affichage dans le navigateur
            return response($output)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="contrat_preview.pdf"');
                
        } catch (\Exception $e) {
            // En cas d'erreur, rediriger avec un message d'erreur
            return back()->with('error', 'Erreur lors de la génération du PDF: ' . $e->getMessage());
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
            $privateDir = storage_path('app/private/signatures');
            if (!file_exists($privateDir)) {
                mkdir($privateDir, 0755, true);
            }
            
            $publicDir = storage_path('app/public/signatures');
            if (!file_exists($publicDir)) {
                mkdir($publicDir, 0755, true);
            }
            
            // Sauvegarder dans le dossier privé pour la sécurité
            $privateSignaturePath = 'private/signatures/' . $contract->id . '_employee.png';
            Storage::put($privateSignaturePath, base64_decode(explode(',', $signatureData)[1]));
            
            // Sauvegarder également dans le dossier public pour l'affichage
            $publicSignaturePath = 'public/signatures/' . $contract->id . '_employee.png';
            Storage::put($publicSignaturePath, base64_decode(explode(',', $signatureData)[1]));
            
            // S'assurer que le lien symbolique existe
            if (!file_exists(public_path('storage'))) {
                \Artisan::call('storage:link');
            }
            
            // Générer le chemin du fichier pour la signature
            $signaturePath = 'signatures/' . $contract->id . '_employee.png';
            
            // Mettre à jour le contrat avec le chemin du fichier (et non l'URL complète)
            $contract->update([
                'employee_signature' => $signaturePath,
                'employee_signed_at' => now(),
                'status' => 'completed' // Changer à 'completed' puisque les deux parties ont signé
            ]);
            
            // Notification à l'administrateur que le contrat a été signé
            // Commenté pour éviter les erreurs si la table jobs n'existe pas
            // User::where('is_admin', true)->first()->notify(new \App\Notifications\ContractSigned($contract));
            
            return redirect()->route('employee.contracts.show', $contract)
                ->with('status', 'Contrat signé avec succès');
        }
        
        return back()->with('status', 'Signature invalide. Veuillez réessayer.');
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
            
            // Toujours générer un nouveau PDF avec les données les plus récentes
            // Charger les données du contrat
            $contractData = ContractData::where('contract_id', $contract->id)->first();
            if (!$contractData) {
                return redirect()->back()->with('error', 'Données du contrat non trouvées.');
            }
            
            // Configuration optimisée de DomPDF
            $options = new \Dompdf\Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $options->set('isPhpEnabled', true);
            $options->set('defaultFont', 'Arial');
            $options->set('isFontSubsettingEnabled', true);
            $options->set('dpi', 96);
            
            // Préparer les chemins des signatures sans utiliser GD
            $adminSignatureExists = file_exists(public_path('storage/signatures/admin_signature.png'));
            $employeeSignatureExists = $contract->employee_signature ? file_exists(public_path('storage/' . $contract->employee_signature)) : false;
            
            // Générer le HTML du contrat
            $html = view('pdf.cdi-template', [
                'data' => $contractData, 
                'contract' => $contract,
                'admin_signature' => $adminSignatureExists,
                'employee_signature' => $employeeSignatureExists ? $contract->employee_signature : null
            ])->render();
            
            // Charger le HTML dans DomPDF
            $dompdf = new \Dompdf\Dompdf($options);
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
            
            // Mettre à jour le chemin du document final dans la base de données si ce n'est pas déjà fait
            if (!$contract->final_document_path) {
                $contract->final_document_path = $pdfPath;
                $contract->generated_at = now();
                $contract->save();
            }
            
            // Télécharger le PDF directement depuis la mémoire
            return response($output)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        } catch (\Exception $e) {
            // En cas d'erreur, rediriger avec un message d'erreur
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
}
