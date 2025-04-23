<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Contrat de travail à durée indéterminée</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #000;
            margin: 0;
            padding: 20px;
        }
        h1 {
            font-size: 12pt;
            text-align: center;
            margin-bottom: 20px;
            text-transform: uppercase;
            font-weight: bold;
        }
        p {
            margin-bottom: 8px;
            text-align: justify;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .article {
            margin-top: 15px;
            margin-bottom: 15px;
        }
        .article-title {
            font-weight: bold;
            margin-bottom: 8px;
        }
        .signature-block {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
            page-break-inside: avoid;
        }
        .signature {
            width: 45%;
        }
        .signature p {
            margin-bottom: 5px;
            text-align: center;
        }
        .signature-image {
            height: 70px;
            margin-bottom: 10px;
            position: relative;
        }
        .signature-image img {
            max-height: 60px;
            max-width: 100%;
            position: absolute;
            bottom: 5px;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
        }
        .addendum {
            margin-top: 100px;
            border-top: 1px solid #000;
            padding-top: 20px;
            text-align: left;
        }
        @page {
            margin: 2cm 1.5cm;
        }
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            margin-bottom: 15px;
        }
        .attendance-table td, .attendance-table th {
            border: 1px solid #000;
            padding: 3px;
            text-align: center;
            font-size: 9pt;
            height: 22px;
        }
        .attendance-table th {
            background-color: #f3f3f3;
            font-weight: bold;
            height: 25px;
        }
        .signature-small {
            max-height: 60px;
            max-width: 140px;
        }
        @media print {
            body {
                font-size: 10pt;
                line-height: 1.3;
                padding: 0;
                margin: 0;
            }
            .attendance-table {
                page-break-inside: avoid;
            }
            .attendance-table td, .attendance-table th {
                border: 1px solid #000 !important;
                padding: 2px;
            }
            .footer {
                page-break-before: avoid;
            }
            .signature-small {
                max-height: 50px;
                max-width: 120px;
            }
            img {
                max-width: 100% !important;
            }
            #pied-page {
                position: fixed;
                bottom: 5px;
                left: 5px;
                font-size: 7pt;
                color: #666;
            }
        }
    </style>
</head>
<body>
    <h1>CONTRAT DE TRAVAIL A DUREE INDETERMINEE</h1>

    <p>Entre Les Soussignés :</p>

    <p>La Société WHAT EVER SAS, société à responsabilité limitée au capital de 200 000 Euros dont le siège social est situé 54 Avenue Kléber 75016 PARIS,<br>
    Immatriculée au Registre du Commerce et des Sociétés de Paris sous le n° 439 077 462 00026,<br>
    Représentée par Monsieur BRIAND Grégory ayant tous pouvoirs à l'effet des présentes,<br>
    Cotisant à l'URSSAF de Paris sous le n° 758 2320572850010116<br>
    d'une part,</p>
    
    <p>et,</p>

    <p>{{ ($data->gender ?? '') == 'M' ? 'Monsieur' : 'Madame' }} {{ $data->full_name ?? $data->first_name.' '.$data->last_name }}<br>
    Né{{ ($data->gender ?? '') != 'M' ? 'e' : '' }} le {{ $data->birth_date ? date('d/m/Y', strtotime($data->birth_date)) : '___________' }} à {{ $data->birth_place ?? '___________' }},<br>
    De nationalité {{ $data->nationality ?? '___________' }},<br>
    Numéro Sécurité Sociale : {{ $data->social_security_number ?? '___________' }}<br>
    Demeurant : {{ $data->address ?? '___________' }}, {{ $data->postal_code ?? '___________' }} {{ $data->city ?? '___________' }}<br>
    d'autre part,</p>
    
    <p>Il a été convenu ce qui suit :</p>

    <div class="article">
        <div class="article-title">ARTICLE 1 - ENGAGEMENT</div>
        <p>Le présent contrat est régi par les dispositions de la convention collective de la restauration rapide et du code du travail avec pour obligation de s'acquitter des avantages repas s'ils sont consommés, ou alors de recevoir une indemnité compensatoire, si les créneaux horaires de travail le justifient.</p>
    </div>

    <div class="article">
        <div class="article-title">ARTICLE 2 - DUREE DU CONTRAT - PÉRIODE D'ESSAI</div>
        <p>Le présent contrat est conclu pour une durée indéterminée à compter du {{ $data->contract_signing_date ? date('d/m/Y', strtotime($data->contract_signing_date)) : '___________' }}.</p>
        
        <p>Il ne deviendra définitif qu'à l'issue d'une période d'essai de {{ $data->trial_period_months ?? '___________' }}, soit jusqu'au {{ $data->trial_period_end_date ? date('d/m/Y', strtotime($data->trial_period_end_date)) : '___________' }}, renouvelable 1 mois.</p>
        
        <p>Durant cette période, chacune des parties pourra, à tout moment, mettre fin au présent contrat sans qu'aucune indemnité ni préavis ne soient dus.</p>
        
        <p>Au-delà de la période d'essai, le présent contrat pourra être rompu à tout moment par l'une ou l'autre des parties, moyennant un préavis dont la durée, en cas de licenciement ou de démission est fixée comme suit :</p>
        <p>- pour le personnel de moins de six mois d'ancienneté dans l'entreprise : huit jours.<br>
        - pour le personnel ayant de six mois à deux ans d'ancienneté : quinze jours pour démission, un mois pour licenciement.<br>
        - pour le personnel ayant au moins deux ans d'ancienneté : un mois pour démission, deux mois pour licenciement.</p>
    </div>

    <div class="article">
        <div class="article-title">ARTICLE 3 - FONCTIONS</div>
        <p>{{ ($data->gender ?? '') == 'M' ? 'Monsieur' : 'Madame' }} {{ $data->last_name ?? '___________' }} {{ $data->first_name ?? '___________' }} est employé(e) en qualité d'Employée de restauration.</p>
        <p>{{ ($data->gender ?? '') == 'M' ? 'Monsieur' : 'Madame' }} {{ $data->last_name ?? '___________' }} {{ $data->first_name ?? '___________' }} exercera ses fonctions dans le cadre des directives écrites ou verbales qui lui seront données par M Briand ou toute personne qui pourrait lui être substituée.</p>
    </div>

    <div class="article">
        <div class="article-title">ARTICLE 4 - REMUNERATION</div>
        <p>La rémunération mensuelle brute de {{ ($data->gender ?? '') == 'M' ? 'Monsieur' : 'Madame' }} {{ $data->last_name ?? '___________' }} {{ $data->first_name ?? '___________' }} sera de {{ $data->monthly_gross_salary ?? '___________' }} euros pour {{ $data->monthly_hours ?? '___________' }} heures mensuel.</p>
    </div>

    <div class="article">
        <div class="article-title">ARTICLE 5 - HORAIRES DE TRAVAIL</div>
        <p>La durée de travail sera de {{ $data->weekly_hours ?? '___________' }} heures hebdomadaires, réparties du lundi au dimanche.</p>
        
        <p>Les jours et horaires de travail seront indiqués à {{ ($data->gender ?? '') == 'M' ? 'Monsieur' : 'Madame' }} {{ $data->last_name ?? '___________' }} {{ $data->first_name ?? '___________' }}, par le biais de plannings hebdomadaires, établis et affichés à l'avance, dans chaque établissement.</p>
        
        <p>Il est convenu que l'horaire de travail de {{ ($data->gender ?? '') == 'M' ? 'Monsieur' : 'Madame' }} {{ $data->last_name ?? '___________' }} {{ $data->first_name ?? '___________' }} sera susceptible de modifications en fonction des nécessités d'organisation du service et des conditions particulières de travail.</p>
        
        <p>Par ailleurs, {{ ($data->gender ?? '') == 'M' ? 'Monsieur' : 'Madame' }} {{ $data->last_name ?? '___________' }} {{ $data->first_name ?? '___________' }} pourra être amenée à effectuer, à titre exceptionnel, un quota d'heures complémentaires. Ce dernier ne pouvant excéder 20% du quota d'heures mensuelles de la salariée, soit par {{ $data->weekly_overtime ?? '___________' }} semaine ({{ $data->monthly_overtime ?? '___________' }} par mois).</p>
    </div>

    <div class="article">
        <div class="article-title">ARTICLE 6 – CONFIDENTIALITE</div>
        <p>{{ ($data->gender ?? '') == 'M' ? 'Monsieur' : 'Madame' }} {{ $data->last_name ?? '___________' }} {{ $data->first_name ?? '___________' }} s'engage à observer la discrétion la plus stricte sur les informations se rapportant aux activités de la société et de ses clients auxquelles elle aura accès à l'occasion et dans le cadre de ses fonctions.</p>
    </div>

    <div class="article">
        <div class="article-title">ARTICLE 7- LIEU DE TRAVAIL</div>
        <p>{{ ($data->gender ?? '') == 'M' ? 'Monsieur' : 'Madame' }} {{ $data->last_name ?? '___________' }} {{ $data->first_name ?? '___________' }} sera amenée à exercer ses fonctions dans les différents établissements de notre enseigne : 360 rue de Flins, 78410 Bouafle, 54 avenue de Kléber 75016 Paris, 4 rue de Londres 75009 Paris, 135 rue Montmartre 75002 Paris, 23 rue Taitbout 75009 Paris, 7 rue de Petites Ecuries Paris 10, 38 rue Ybry Neuilly sur Seine, 24 rue du 4 Septembre, ainsi que sur nos différents stands au cours d'événements ponctuels.</p>
    </div>

    <div class="article">
        <div class="article-title">ARTICLE 8 – OBLIGATIONS de {{ ($data->gender ?? '') == 'M' ? 'Monsieur' : 'Madame' }} {{ $data->last_name ?? '___________' }} {{ $data->first_name ?? '___________' }}</div>
        <p>Pendant la durée de son contrat {{ ($data->gender ?? '') == 'M' ? 'Monsieur' : 'Madame' }} {{ $data->last_name ?? '___________' }} {{ $data->first_name ?? '___________' }} s'engage à respecter les instructions qui pourront lui être données par la société et à se conformer aux règles relatives à l'organisation et au fonctionnement interne de la société.</p>
         
        <p>En cas d'empêchement pour lui d'effectuer son travail, {{ ($data->gender ?? '') == 'M' ? 'Monsieur' : 'Madame' }} {{ $data->last_name ?? '___________' }} {{ $data->first_name ?? '___________' }} est tenue d'en aviser la société dans les 48 heures, en indiquant la durée prévisible de cet empêchement.</p>
        
        <p>Si cette absence est justifiée par la maladie ou l'accident, {{ ($data->gender ?? '') == 'M' ? 'Monsieur' : 'Madame' }} {{ $data->last_name ?? '___________' }} {{ $data->first_name ?? '___________' }} devra en outre faire parvenir un certificat médical indiquant la durée probable du repos dans les 3 jours.</p>
        
        <p>La même formalité est requise en cas de prolongation de l'arrêt de travail.</p>
        
        <p>{{ ($data->gender ?? '') == 'M' ? 'Monsieur' : 'Madame' }} {{ $data->last_name ?? '___________' }} {{ $data->first_name ?? '___________' }} devra informer la société de tous changements qui interviendraient dans les situations qu'elle a signalées lors de son engagement.</p>
        
        <p>{{ ($data->gender ?? '') == 'M' ? 'Monsieur' : 'Madame' }} {{ $data->last_name ?? '___________' }} {{ $data->first_name ?? '___________' }} s'engage à respecter scrupuleusement les normes et directives de qualité des tâches qui lui seront imparties.</p>
        <p>Des défauts de qualité graves ou répétés pourront entraîner des sanctions disciplinaires.</p>
    </div>

    <div class="article">
        <div class="article-title">ARTICLE 9 - CONDITIONS D'EXÉCUTION DU CONTRAT</div>
        <p>{{ ($data->gender ?? '') == 'M' ? 'Monsieur' : 'Madame' }} {{ $data->last_name ?? '___________' }} {{ $data->first_name ?? '___________' }} s'engage à se conformer aux instructions de la Direction.</p>
        
        <p>Compte tenu de la nature de son emploi comportant un contact permanent avec la clientèle et de la nécessité pour la société de conserver sa bonne image de marque, {{ ($data->gender ?? '') == 'M' ? 'Monsieur' : 'Madame' }} {{ $data->last_name ?? '___________' }} {{ $data->first_name ?? '___________' }} s'engage à porter en toutes circonstances une tenue correcte et de bon aloi.</p>
        
        <p>Le refus de se conformer à ces prescriptions sera constitutif d'une faute susceptible d'être sanctionnée.</p>
        
        <p>{{ ($data->gender ?? '') == 'M' ? 'Monsieur' : 'Madame' }} {{ $data->last_name ?? '___________' }} {{ $data->first_name ?? '___________' }} devra faire connaître à l'entreprise sans délai toute modification postérieure à son engagement qui pourrait intervenir dans son état civil, sa situation de famille, son adresse.</p>
    </div>

    <div class="article">
        <div class="article-title">ARTICLE 10 - CONGÉS PAYÉS</div>
        <p>{{ ($data->gender ?? '') == 'M' ? 'Monsieur' : 'Madame' }} {{ $data->last_name ?? '___________' }} {{ $data->first_name ?? '___________' }} bénéficiera des congés payés légaux, soit trente jours ouvrables par période du 1er juin au 31 mai suivant.</p>
        
        <p>La période de congés payés sera fixée chaque année en tenant compte des nécessités du service.</p>
    </div>

    <div class="article">
        <div class="article-title">ARTICLE 11 - STATUT</div>
        <p>{{ ($data->gender ?? '') == 'M' ? 'Monsieur' : 'Madame' }} {{ $data->last_name ?? '___________' }} {{ $data->first_name ?? '___________' }} bénéficiera des lois sociales instituées en faveur des salariés, notamment en matière de Sécurité Sociale et en ce qui concerne le régime de retraite complémentaire pour lequel elle est affiliée.</p>
        
        <p>{{ ($data->gender ?? '') == 'M' ? 'Monsieur' : 'Madame' }} {{ $data->last_name ?? '___________' }} {{ $data->first_name ?? '___________' }} relève de la catégorie "employé" et sera affiliée dès son entrée au sein de la société au contrat retraite complémentaire Humanis.</p>
        
        <p>{{ ($data->gender ?? '') == 'M' ? 'Monsieur' : 'Madame' }} {{ $data->last_name ?? '___________' }} {{ $data->first_name ?? '___________' }} relève de la catégorie "employé" et sera affiliée dès son entrée au sein de la société au contrat Prévoyance AG2R.</p>
        
        <p>{{ ($data->gender ?? '') == 'M' ? 'Monsieur' : 'Madame' }} {{ $data->last_name ?? '___________' }} {{ $data->first_name ?? '___________' }} relève de la catégorie "employé" et sera affiliée dès son entrée au sein de la société à la Mutuelle APRIL ENTREPRISE.</p>
        
        <p>Pour toutes les dispositions non prévues par les présentes, les parties déclarent se référer à la convention collective de la restauration rapide, au code du travail ainsi qu'aux lois et règlements applicables dans la société.</p>
    </div>

    <div class="footer">
        <p>Fait en double exemplaire originaux dont un pour chacune des parties.</p>
        
        <p>A Paris, le {{ (is_object($data) && isset($data->contract_start_date)) ? date('d/m/Y', strtotime($data->contract_start_date)) : '___________' }}</p>
        
        <!-- Signatures -->
        <div style="margin-top: 50px; margin-bottom: 15px;">
            <table width="100%" style="border-spacing: 0;">
                <tr>
                    <td width="45%" style="text-align: center; vertical-align: top; padding-top: 5px;">
                        <p style="margin-bottom: 5px; text-align: center; font-size: 10pt;"><strong>L'employeur</strong></p>
                        <p style="text-align: center; font-size: 9pt; margin-bottom: 2px;">M BRIAND Grégory</p>
                        <p style="text-align: center; font-size: 9pt; margin-bottom: 2px;">Pour la société</p>
                        <p style="text-align: center; font-size: 9pt; margin-bottom: 5px;">{{ $admin->name ?? 'L\'administrateur' }}</p>
                        <div style="text-align: center;">
                        @php
                            // Si la signature de l'admin est déjà passée par le controller, on l'utilise directement
                            if (isset($adminSignatureBase64) && !empty($adminSignatureBase64)) {
                                // On utilise directement la variable existante
                                \Log::info('Signature admin déjà présente, longueur: ' . strlen($adminSignatureBase64));
                            } else {
                                // On efface cette partie pour les prévisualisations non signées
                                $adminSignatureBase64 = '';
                                
                                // Vérifier si on doit afficher la signature (uniquement si le contrat est explicitement signé par l'admin)
                                if (isset($contract) && in_array($contract->status ?? '', ['admin_signed', 'employee_signed', 'completed'])) {
                                    // Tenter de charger la signature de l'administrateur
                                    if (isset($admin_signature)) {
                                        // Si on a un chemin de signature admin, essayer de charger le fichier
                                        $adminSignaturePath = storage_path('app/public/signatures/' . $admin_signature);
                                        
                                        if (file_exists($adminSignaturePath)) {
                                            try {
                                                $adminSignatureContent = file_get_contents($adminSignaturePath);
                                                $adminSignatureBase64 = base64_encode($adminSignatureContent);
                                                
                                                \Log::info('Chargement signature admin depuis $admin_signature', [
                                                    'chemin' => $adminSignaturePath,
                                                    'taille_base64' => strlen($adminSignatureBase64)
                                                ]);
                                            } catch (Exception $e) {
                                                \Log::error('Erreur lors du chargement de la signature admin: ' . $e->getMessage());
                                            }
                                        }
                                    }
                                    
                                    // Si toujours pas de signature, essayer le chemin par défaut
                                    if (empty($adminSignatureBase64) && \Storage::exists('public/signatures/admin_signature.png')) {
                                        try {
                                            $adminSignatureContent = \Storage::get('public/signatures/admin_signature.png');
                                            $adminSignatureBase64 = base64_encode($adminSignatureContent);
                                            
                                            \Log::info('Chargement signature admin depuis chemin par défaut', [
                                                'taille_base64' => strlen($adminSignatureBase64)
                                            ]);
                                        } catch (Exception $e) {
                                            \Log::error('Erreur lors du chargement de la signature admin: ' . $e->getMessage());
                                        }
                                    }
                                    
                                    // Si toujours rien, générer une signature par défaut
                                    if (empty($adminSignatureBase64)) {
                                        try {
                                            // Créer une image simple
                                            $img = imagecreatetruecolor(300, 100);
                                            $bg = imagecolorallocate($img, 255, 255, 255);
                                            $textcolor = imagecolorallocate($img, 0, 0, 0);
                                            
                                            // Dessiner un fond blanc
                                            imagefilledrectangle($img, 0, 0, 300, 100, $bg);
                                            
                                            // Ajouter un texte
                                            imagestring($img, 5, 70, 40, "Signature Administrateur", $textcolor);
                                            
                                            // Capturer l'image en mémoire
                                            ob_start();
                                            imagepng($img);
                                            $adminSignatureContent = ob_get_clean();
                                            imagedestroy($img);
                                            
                                            $adminSignatureBase64 = base64_encode($adminSignatureContent);
                                        } catch (Exception $e) {
                                            \Log::error('Erreur lors de la génération de la signature admin: ' . $e->getMessage());
                                        }
                                    }
                                }
                            }
                        @endphp
                        
                        @if(!empty($adminSignatureBase64))
                            <img src="{{ strpos($adminSignatureBase64, 'data:') === 0 ? $adminSignatureBase64 : 'data:image/png;base64,' . $adminSignatureBase64 }}" alt="Signature de l'employeur" style="max-height: 100px; margin: 0 auto;">
                        @else
                            <div style="width:200px; height:100px; border-bottom: 1px solid #000; display:inline-block; text-align:center; margin: 0 auto;">
                                Signature de l'employeur
                            </div>
                        @endif
                        </div>
                    </td>
                    <td width="10%">&nbsp;</td>
                    <td width="45%" style="text-align: center; vertical-align: top; padding-top: 5px;">
                        <p style="margin-bottom: 5px; text-align: center; font-size: 10pt;"><strong>L'employé(e)</strong></p>
                        <p style="text-align: center; font-size: 9pt; margin-bottom: 10px;">{{ $data->first_name ?? '' }} {{ $data->last_name ?? '' }}</p>
                        <div style="text-align: center;">
                        @php
                            // Si la signature de l'employé est déjà passée par le controller, on l'utilise directement
                            if (isset($employeeSignatureBase64) && !empty($employeeSignatureBase64)) {
                                // On utilise directement la variable existante
                                \Log::info('Signature employé déjà présente, longueur: ' . strlen($employeeSignatureBase64));
                            } else {
                                // On efface cette partie pour les prévisualisations non signées
                                $employeeSignatureBase64 = '';
                                
                                // Vérifier si on doit afficher la signature (uniquement si ce n'est pas une prévisualisation ou si le contrat est signé par l'employé)
                                if (!(isset($is_preview) && $is_preview === true) || (isset($contract) && in_array($contract->status ?? '', ['employee_signed', 'completed']))) {
                                    // 1. Essayer avec le chemin spécifique à l'employé si disponible
                                    if (isset($data) && isset($data->user_id)) {
                                        $employeeSignaturePath = storage_path('app/public/signatures/' . $data->user_id . '_employee.png');
                                        
                                        if (file_exists($employeeSignaturePath)) {
                                            try {
                                                $employeeSignatureContent = file_get_contents($employeeSignaturePath);
                                                $employeeSignatureBase64 = base64_encode($employeeSignatureContent);
                                                
                                                \Log::info('Chargement signature employé depuis user_id', [
                                                    'chemin' => $employeeSignaturePath,
                                                    'taille_base64' => strlen($employeeSignatureBase64)
                                                ]);
                                            } catch (Exception $e) {
                                                \Log::error('Erreur lors du chargement de la signature employé: ' . $e->getMessage());
                                            }
                                        }
                                    }
                                    
                                    // 2. Si aucune signature trouvée et qu'on a employee_signature, l'utiliser
                                    if (empty($employeeSignatureBase64) && isset($employee_signature)) {
                                        $employeeSignaturePath = storage_path('app/public/signatures/' . $employee_signature);
                                        
                                        if (file_exists($employeeSignaturePath)) {
                                            try {
                                                $employeeSignatureContent = file_get_contents($employeeSignaturePath);
                                                $employeeSignatureBase64 = base64_encode($employeeSignatureContent);
                                                
                                                \Log::info('Chargement signature employé depuis $employee_signature', [
                                                    'chemin' => $employeeSignaturePath,
                                                    'taille_base64' => strlen($employeeSignatureBase64)
                                                ]);
                                            } catch (Exception $e) {
                                                \Log::error('Erreur lors du chargement de la signature employé: ' . $e->getMessage());
                                            }
                                        }
                                    }
                                    
                                    // 3. Si aucune signature trouvée et qu'on a contract->user_id, essayer avec
                                    if (empty($employeeSignatureBase64) && isset($contract) && isset($contract->user_id)) {
                                        $employeeSignaturePath = storage_path('app/public/signatures/' . $contract->user_id . '_employee.png');
                                        
                                        if (file_exists($employeeSignaturePath)) {
                                            try {
                                                $employeeSignatureContent = file_get_contents($employeeSignaturePath);
                                                $employeeSignatureBase64 = base64_encode($employeeSignatureContent);
                                                
                                                \Log::info('Chargement signature employé depuis contract->user_id', [
                                                    'chemin' => $employeeSignaturePath,
                                                    'taille_base64' => strlen($employeeSignatureBase64)
                                                ]);
                                            } catch (Exception $e) {
                                                \Log::error('Erreur lors du chargement de la signature employé: ' . $e->getMessage());
                                            }
                                        }
                                    }
                                }
                            }
                        @endphp
                        
                        @if(!empty($employeeSignatureBase64))
                            <img src="{{ strpos($employeeSignatureBase64, 'data:') === 0 ? $employeeSignatureBase64 : 'data:image/png;base64,' . $employeeSignatureBase64 }}" alt="Signature de l'employé" style="max-height: 100px; margin: 0 auto;">
                        @else
                            <div style="width:200px; height:100px; border-bottom: 1px solid #000; display:inline-block; text-align:center; margin: 0 auto;">
                                Signature de l'employé
                            </div>
                        @endif
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Forcer un saut de page avant l'annexe -->
    <div style="page-break-before: always;"></div>

    <!-- Tableau d'émargement -->
    <div style="margin-top: 20px; margin-bottom: 30px;">
        <h3 style="font-size: 11pt; text-align: center; margin-bottom: 10px;">FEUILLE D'ÉMARGEMENT - {{ date('Y') }}</h3>
        <p style="text-align: center; font-size: 9pt; margin-bottom: 10px;">Formation/Réunion du {{ date('d/m/Y') }} - Paris</p>
        
        <table class="attendance-table">
            <thead>
                <tr>
                    <th width="25%">Nom</th>
                    <th width="25%">Prénom</th>
                    <th width="25%">Date</th>
                    <th width="25%">Signature</th>
                </tr>
            </thead>
            <tbody>
                @for($i = 1; $i <= 8; $i++)
                <tr style="height: 25px;">
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
                @endfor
            </tbody>
        </table>
    </div>
    
    <div class="addendum" style="border-top: none; margin-top: 30px;">
        <p style="font-size: 10pt;">Paris le {{ (is_object($data) && isset($data->contract_start_date)) ? date('d/m/Y', strtotime($data->contract_start_date)) : '___________' }}</p>

        <p style="font-size: 10pt;">Je soussigné, {{ (is_object($data) && isset($data->gender) && $data->gender == 'M') ? 'Monsieur' : 'Madame' }} {{ (is_object($data) && isset($data->last_name)) ? $data->last_name : '___________' }} {{ (is_object($data) && isset($data->first_name)) ? $data->first_name : '___________' }}, né(e) le {{ (is_object($data) && isset($data->birth_date)) ? date('d/m/Y', strtotime($data->birth_date)) : '___________' }} à {{ (is_object($data) && isset($data->birth_place)) ? $data->birth_place : '___________' }} souhaite ne solliciter un poste que de {{ (is_object($data) && isset($data->weekly_hours)) ? $data->weekly_hours : '___________' }} heures par semaine au sein de la société Whatever, pour le moment.</p>

        <p style="font-size: 10pt;">Cordialement,</p>

        <div style="margin-top:40px;">
            <p style="font-size: 10pt;"><strong>Signature :</strong></p>
            @if(!empty($employeeSignatureBase64))
                <img src="{{ strpos($employeeSignatureBase64, 'data:') === 0 ? $employeeSignatureBase64 : 'data:image/png;base64,' . $employeeSignatureBase64 }}" alt="Signature de l'employé" style="max-height: 100px; margin-top: 20px;">
            @else
                <div style="width:200px; height:100px; border-bottom: 1px solid #000; margin-top: 20px;">
                </div>
            @endif
        </div>
    </div>  

    <!-- Pied de page avec informations de génération -->
    <div style="position: fixed; bottom: 10px; left: 10px; font-size: 8pt; color: #999;">
        Document généré le {{ $generatedAt }}
    </div>
    
    <!-- Information d'impression -->
    <div id="pied-page">
        Document imprimé le <?php echo date('d/m/Y à H:i'); ?> - Whatever SAS - Tous droits réservés
    </div>
</body>
</html>
