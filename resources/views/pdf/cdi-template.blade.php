<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Contrat de travail à durée indéterminée</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 9pt;
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
            font-size: 9pt;
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
            font-size: 9pt;
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
            height: 100px;
            margin-bottom: 10px;
            position: relative;
        }
        .signature-image img {
            max-height: 80px;
            max-width: 100%;
            position: absolute;
            bottom: 5px;
        }
        .footer {
            margin-top: 15px;
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
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 10px;
        }
            border: 1px solid #000;
            padding: 2px;
            text-align: center;
            font-size: 7pt;
            height: 16px;
        }
            background-color: #f3f3f3;
            font-weight: bold;
            height: 18px;
            font-size: 7pt;
        }
        .signature-small {
            max-height: 60px;
            max-width: 140px;
        }
        /* Empêcher le saut de page entre les articles et les signatures */
        .contract-end {
            page-break-inside: avoid;
        }
        @media print {
            body {
                font-size: 10pt;
                line-height: 1.3;
                padding: 0;
                margin: 0;
            }
                page-break-inside: avoid;
            }
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

    <div style="page-break-inside: avoid;">
        <div class="article">
            <div class="article-title">ARTICLE 9 - CONDITIONS D'EXÉCUTION DU CONTRAT</div>
            <p style="font-size: 9pt;">{{ ($data->gender ?? '') == 'M' ? 'Monsieur' : 'Madame' }} {{ $data->last_name ?? '___________' }} {{ $data->first_name ?? '___________' }} s'engage à se conformer aux instructions de la Direction.</p>
            
            <p style="font-size: 9pt;">Compte tenu de la nature de son emploi comportant un contact permanent avec la clientèle et de la nécessité pour la société de conserver sa bonne image de marque, {{ ($data->gender ?? '') == 'M' ? 'Monsieur' : 'Madame' }} {{ $data->last_name ?? '___________' }} {{ $data->first_name ?? '___________' }} s'engage à porter en toutes circonstances une tenue correcte et de bon aloi.</p>
            
            <p style="font-size: 9pt;">Le refus de se conformer à ces prescriptions sera constitutif d'une faute susceptible d'être sanctionnée.</p>
            
            <p style="font-size: 9pt;">{{ ($data->gender ?? '') == 'M' ? 'Monsieur' : 'Madame' }} {{ $data->last_name ?? '___________' }} {{ $data->first_name ?? '___________' }} devra faire connaître à l'entreprise sans délai toute modification postérieure à son engagement qui pourrait intervenir dans son état civil, sa situation de famille, son adresse.</p>
        </div>

        <div class="article" style="margin-top: 0; page-break-after: avoid;">
            <div class="article-title">ARTICLE 10 - CONGÉS PAYÉS</div>
            <p style="font-size: 9pt;">{{ ($data->gender ?? '') == 'M' ? 'Monsieur' : 'Madame' }} {{ $data->last_name ?? '___________' }} {{ $data->first_name ?? '___________' }} bénéficiera des congés payés légaux, soit trente jours ouvrables par période du 1er juin au 31 mai suivant.</p>
            
            <p style="font-size: 9pt;">La période de congés payés sera fixée chaque année en tenant compte des nécessités du service.</p>
        </div>
    </div>

    <div class="article">
        <div class="article-title">ARTICLE 11 - STATUT</div>
        <p style="font-size: 9pt; margin-bottom: 5px;">{{ ($data->gender ?? '') == 'M' ? 'Monsieur' : 'Madame' }} {{ $data->last_name ?? '___________' }} {{ $data->first_name ?? '___________' }} bénéficiera des lois sociales instituées en faveur des salariés, notamment en matière de Sécurité Sociale et en ce qui concerne le régime de retraite complémentaire pour lequel elle est affiliée.</p>
        
        <p style="font-size: 9pt; margin-bottom: 5px;">{{ ($data->gender ?? '') == 'M' ? 'Monsieur' : 'Madame' }} {{ $data->last_name ?? '___________' }} {{ $data->first_name ?? '___________' }} relève de la catégorie "employé" et sera affiliée dès son entrée au sein de la société au contrat retraite complémentaire Humanis.</p>
        
        <p style="font-size: 9pt; margin-bottom: 5px;">{{ ($data->gender ?? '') == 'M' ? 'Monsieur' : 'Madame' }} {{ $data->last_name ?? '___________' }} {{ $data->first_name ?? '___________' }} relève de la catégorie "employé" et sera affiliée dès son entrée au sein de la société au contrat Prévoyance AG2R.</p>
        
        <p style="font-size: 9pt; margin-bottom: 5px;">{{ ($data->gender ?? '') == 'M' ? 'Monsieur' : 'Madame' }} {{ $data->last_name ?? '___________' }} {{ $data->first_name ?? '___________' }} relève de la catégorie "employé" et sera affiliée dès son entrée au sein de la société à la Mutuelle APRIL ENTREPRISE.</p>
        
        <p style="font-size: 9pt; margin-bottom: 5px;">Pour toutes les dispositions non prévues par les présentes, les parties déclarent se référer à la convention collective de la restauration rapide, au code du travail ainsi qu'aux lois et règlements applicables dans la société.</p>
    </div>

    <div class="footer" style="page-break-inside: avoid; margin-top: 5px;">
        <p style="font-size: 9pt;">Fait en double exemplaire originaux dont un pour chacune des parties.</p>
        
        <p style="font-size: 9pt;">A Paris, le {{ (is_object($data) && isset($data->contract_start_date)) ? date('d/m/Y', strtotime($data->contract_start_date)) : '___________' }}</p>
        
        <!-- Signatures -->
        <div style="margin-top: 3px; margin-bottom: 5px;">
            <table width="100%" style="border-spacing: 0;">
                <tr>
                    <td width="45%" style="text-align: center; vertical-align: top; padding-top: 2px;">
                        <p style="margin-bottom: 2px; text-align: center; font-size: 9pt;"><strong>L'employeur</strong></p>
                        <p style="text-align: center; font-size: 9pt; margin-bottom: 1px;">M BRIAND Grégory</p>
                        <p style="text-align: center; font-size: 9pt; margin-bottom: 1px;">Pour la société</p>
                        <div style="text-align: center;">
                        @php
                            // Vérifier si la signature admin doit être affichée
                            $showAdminSignature = false;
                            
                            // Toujours afficher la signature admin en prévisualisation
                            if (isset($isPreview) && $isPreview === true) {
                                $showAdminSignature = true;
                                \Log::info('Signature admin forcée en prévisualisation pour tous les cas');
                            }
                            // Utiliser la variable explicite show_admin_signature 
                            elseif (isset($show_admin_signature) && $show_admin_signature === true) {
                                $showAdminSignature = true;
                                \Log::info('Signature admin affichée via variable explicite', ['show_admin_signature' => $show_admin_signature]);
                            } 
                            // Sinon, vérifier le statut du contrat - CETTE PARTIE EST CRITIQUE
                            elseif (isset($contract) && isset($contract->status) && 
                                ($contract->status === 'admin_signed' || 
                                 $contract->status === 'employee_signed' || 
                                 $contract->status === 'completed')) {
                                $showAdminSignature = true;
                                \Log::info('Signature admin affichée via statut du contrat', ['status' => $contract->status ?? 'N/A']);
                            }
                            
                            // Forcer à partir de la propriété ajoutée au contrat
                            if (isset($contract) && isset($contract->show_admin_signature) && $contract->show_admin_signature) {
                                $showAdminSignature = true;
                                \Log::info('Signature admin forcée par la propriété show_admin_signature');
                            }
                            
                            // Forcer encore l'affichage pour la prévisualisation
                            if (isset($contract) && isset($contract->admin_signed_at) && $contract->admin_signed_at) {
                                $showAdminSignature = true;
                                \Log::info('Signature admin forcée car admin_signed_at est défini', ['admin_signed_at' => $contract->admin_signed_at]);
                            }
                            
                            // En prévisualisation, forcer l'affichage de la signature admin
                            if (isset($isPreview) && $isPreview === true) {
                                $showAdminSignature = true;
                                \Log::info('Signature admin forcée en mode prévisualisation');
                            }
                            
                            // Si disponible, utiliser la signature en base64 passée par le contrôleur
                            $adminSignatureBase64 = $adminSignatureBase64 ?? null;
                            
                            // Si l'attribut adminSignatureBase64 est défini sur le contrat, l'utiliser
                            if (!$adminSignatureBase64 && isset($contract) && isset($contract->adminSignatureBase64)) {
                                $adminSignatureBase64 = $contract->adminSignatureBase64;
                                \Log::info('Utilisation de adminSignatureBase64 depuis le contrat');
                            }
                            
                            // Si pas de base64, essayer de charger l'image directement depuis le helper
                            if (!$adminSignatureBase64 && $showAdminSignature) {
                                $signatureHelper = new \App\Temp_Fixes\SignatureHelper();
                                $adminId = isset($contract) && isset($contract->admin_id) ? $contract->admin_id : (Auth::id() ?? null);
                                $adminSignatureBase64 = $signatureHelper->prepareSignatureForPdf('admin', $adminId);
                                \Log::info('Chargement signature admin via helper', ['admin_id' => $adminId]);
                            }
                        @endphp
                        
                        @if($showAdminSignature)
                            @if($adminSignatureBase64)
                                <img src="{{ $adminSignatureBase64 }}" alt="Signature de l'employeur" style="max-height: 70px; margin: 0 auto;">
                                @php \Log::info('Affichage signature admin en base64', ['length' => strlen($adminSignatureBase64)]); @endphp
                            @elseif(isset($isPreview) && $isPreview)
                                <img src="{{ $adminSignatureAbsUrl }}" alt="Signature de l'employeur (URL absolue)" style="max-height: 70px; margin: 0 auto;">
                                @php \Log::info('Affichage signature admin URL absolue', ['url' => $adminSignatureAbsUrl]); @endphp
                            @elseif(isset($contract) && isset($contract->admin_signature) && !empty($contract->admin_signature))
                                @php 
                                    $adminSignatureUrl = Storage::url($contract->admin_signature);
                                    \Log::info('Utilisation signature admin depuis contrat', ['url' => $adminSignatureUrl]);
                                @endphp
                                <img src="{{ $adminSignatureUrl }}" alt="Signature de l'employeur" style="max-height: 70px; margin: 0 auto;">
                            @else
                                <img src="{{ $adminSignatureUrl }}" alt="Signature de l'employeur" style="max-height: 70px; margin: 0 auto;">
                                @php \Log::info('Affichage signature admin URL relative', ['url' => $adminSignatureUrl]); @endphp
                            @endif
                        @else
                            <div style="width:150px; height:70px; border-bottom: 1px solid #000; display:inline-block; text-align:center; margin: 0 auto;">
                                <span style="font-size: 8pt; color: #666;">(signature)</span>
                            </div>
                            @php \Log::warning('Signature admin non affichée', ['showAdminSignature' => $showAdminSignature]); @endphp
                        @endif
                        </div>
                    </td>
                    <td width="10%">&nbsp;</td>
                    <td width="45%" style="text-align: center; vertical-align: top; padding-top: 2px;">
                        <p style="margin-bottom: 2px; text-align: center; font-size: 9pt;"><strong>L'employé(e)</strong></p>
                        <p style="text-align: center; font-size: 9pt; margin-bottom: 2px;">{{ $data->first_name ?? '' }} {{ $data->last_name ?? '' }}</p>
                        <div style="text-align: center;">
                        @php
                            // Vérifier si la signature employé doit être affichée
                            $showEmployeeSignature = false;
                            
                            // Utiliser la variable explicite show_employee_signature
                            if (isset($show_employee_signature) && $show_employee_signature === true) {
                                $showEmployeeSignature = true;
                            }
                            // Sinon, vérifier le statut du contrat
                            elseif (isset($contract) && isset($contract->status) && 
                                   ($contract->status === 'employee_signed' || 
                                    $contract->status === 'completed')) {
                                $showEmployeeSignature = true;
                            }
                            
                            // Utiliser la signature en base64 si disponible
                            $employeeSignatureBase64Data = $employeeSignatureBase64 ?? null;
                            
                            // Chercher la signature de l'employé si ce n'est pas déjà fait
                            if (!$employeeSignatureBase64Data && isset($contract) && $showEmployeeSignature) {
                                $signatureHelper = new \App\Temp_Fixes\SignatureHelper();
                                $employeeId = $contract->user_id;
                                $employeeSignatureBase64Data = $signatureHelper->prepareSignatureForPdf('employee', $employeeId);
                                \Log::info('Chargement signature employé via helper', ['employee_id' => $employeeId]);
                            }
                        @endphp
                        
                        @if($showEmployeeSignature)
                            @if($employeeSignatureBase64Data)
                                <img src="{{ $employeeSignatureBase64Data }}" alt="Signature de l'employé" style="max-height: 70px; margin-top: 20px;">
                            @elseif($employeeSignatureUrl)
                                <img src="{{ $employeeSignatureUrl }}" alt="Signature de l'employé" style="max-height: 70px; margin-top: 20px;">
                            @else
                                <div style="width:150px; height:70px; border-bottom: 1px solid #000; display:inline-block; text-align:center; margin: 0 auto;">
                                    <span style="font-size: 8pt; color: #666;">(signature)</span>
                                </div>
                            @endif
                        @else
                            <div style="width:150px; height:70px; border-bottom: 1px solid #000; display:inline-block; text-align:center; margin: 0 auto;">
                                <span style="font-size: 8pt; color: #666;">(signature)</span>
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

    <div class="addendum" style="border-top: none; margin-top: 30px;">
        <p style="font-size: 10pt;">Paris le {{ (is_object($data) && isset($data->contract_start_date)) ? date('d/m/Y', strtotime($data->contract_start_date)) : '___________' }}</p>

        <p style="font-size: 10pt;">Je soussigné, {{ (is_object($data) && isset($data->gender) && $data->gender == 'M') ? 'Monsieur' : 'Madame' }} {{ (is_object($data) && isset($data->last_name)) ? $data->last_name : '___________' }} {{ (is_object($data) && isset($data->first_name)) ? $data->first_name : '___________' }}, né(e) le {{ (is_object($data) && isset($data->birth_date)) ? date('d/m/Y', strtotime($data->birth_date)) : '___________' }} à {{ (is_object($data) && isset($data->birth_place)) ? $data->birth_place : '___________' }} souhaite ne solliciter un poste que de {{ (is_object($data) && isset($data->weekly_hours)) ? $data->weekly_hours : '___________' }} heures par semaine au sein de la société Whatever, pour le moment.</p>

        <p style="font-size: 10pt;">Cordialement,</p>

        <div style="margin-top:40px;">
            <p style="font-size: 10pt;"><strong>Signature :</strong></p>
            @php
                // Vérifier si la signature employé doit être affichée
                $showEmployeeSignature = false;
                
                // Utiliser la variable explicite show_employee_signature
                if (isset($show_employee_signature) && $show_employee_signature === true) {
                    $showEmployeeSignature = true;
                }
                // Sinon, vérifier le statut du contrat
                elseif (isset($contract) && isset($contract->status) && 
                       ($contract->status === 'employee_signed' || 
                        $contract->status === 'completed')) {
                    $showEmployeeSignature = true;
                }
                
                // Utiliser la signature en base64 si disponible
                $employeeSignatureBase64Data = $employeeSignatureBase64 ?? null;
                
                // Chercher la signature de l'employé si ce n'est pas déjà fait
                if (!$employeeSignatureBase64Data && isset($contract) && $showEmployeeSignature) {
                    $signatureHelper = new \App\Temp_Fixes\SignatureHelper();
                    $employeeId = $contract->user_id;
                    $employeeSignatureBase64Data = $signatureHelper->prepareSignatureForPdf('employee', $employeeId);
                    \Log::info('Chargement signature employé via helper', ['employee_id' => $employeeId]);
                }
            @endphp
            
            @if($showEmployeeSignature)
                @if($employeeSignatureBase64Data)
                    <img src="{{ $employeeSignatureBase64Data }}" alt="Signature de l'employé" style="max-height: 70px; margin-top: 20px;">
                @elseif($employeeSignatureUrl)
                    <img src="{{ $employeeSignatureUrl }}" alt="Signature de l'employé" style="max-height: 70px; margin-top: 20px;">
                @else
                    <div style="width:150px; height:70px; border-bottom: 1px solid #000; display:inline-block; text-align:center; margin: 0 auto;">
                        <span style="font-size: 8pt; color: #666;">(signature)</span>
                    </div>
                @endif
            @else
                <div style="width:150px; height:70px; border-bottom: 1px solid #000; display:inline-block; text-align:center; margin: 0 auto;">
                    <span style="font-size: 8pt; color: #666;">(signature)</span>
                </div>
            @endif
        </div>
    </div>  
    
    
</body>
</html>
