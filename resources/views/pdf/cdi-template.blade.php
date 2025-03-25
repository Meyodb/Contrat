<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Contrat de travail à durée indéterminée</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.6;
            color: #000;
            margin: 0;
            padding: 25px;
        }
        h1 {
            font-size: 14pt;
            text-align: center;
            margin-bottom: 30px;
            text-transform: uppercase;
            font-weight: bold;
        }
        p {
            margin-bottom: 10px;
            text-align: justify;
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        .article {
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .article-title {
            font-weight: bold;
            margin-bottom: 10px;
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
            margin: 2.5cm 2cm;
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
        <p>La durée de travail sera de {{ ($data->gender ?? '') == 'M' ? 'Monsieur' : 'Madame' }} {{ $data->last_name ?? '___________' }} {{ $data->first_name ?? '___________' }} de {{ $data->weekly_hours ?? '___________' }} heures hebdomadaires, réparties du lundi au dimanche.</p>
        
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
        
        <p>A Paris, le {{ $data->contract_start_date ? date('d/m/Y', strtotime($data->contract_start_date)) : '___________' }}</p>
        
        <table style="width: 100%; margin-top: 30px;">
            <tr>
                <td style="width: 50%; text-align: left; vertical-align: top;">
                    <p>M BRIAND Grégory</p>
                    <p>Pour la société</p>
                    @if($admin_signature)
                        <div style="height: 60px; margin-top: 20px; position: relative;">
                            <img src="{{ public_path('storage/signatures/admin_signature.png') }}" alt="Signature de l'employeur" style="max-height: 100px;">
                        </div>
                    @else
                        <div style="height: 60px; border-bottom: 1px solid #000; margin-top: 20px; position: relative;">
                            <p style="position: absolute; bottom: 0; left: 50%; transform: translateX(-50%);">Signature</p>
                        </div>
                    @endif
                </td>
                <td style="width: 50%; text-align: right; vertical-align: top;">
                    <p>{{ ($data->gender ?? '') == 'M' ? 'Monsieur' : 'Madame' }} {{ $data->last_name ?? '___________' }} {{ $data->first_name ?? '___________' }}</p>
                    @if($employee_signature)
                        <div style="height: 60px; margin-top: 20px; position: relative;">
                            <img src="{{ public_path('storage/' . $employee_signature) }}" alt="Signature de l'employé" style="max-height: 100px;">
                        </div>
                    @else
                        <div style="height: 60px; border-bottom: 1px solid #000; margin-top: 20px; position: relative;">
                            <p style="position: absolute; bottom: 0; left: 50%; transform: translateX(-50%);">Signature</p>
                        </div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <!-- Forcer un saut de page avant l'annexe -->
    <div style="page-break-before: always;"></div>

    <div class="addendum" style="border-top: none; margin-top: 50px;">
        <p>Paris le {{ $data->contract_start_date ? date('d/m/Y', strtotime($data->contract_start_date)) : '___________' }}</p>

        <p>Je soussigné, {{ ($data->gender ?? '') == 'M' ? 'Monsieur' : 'Madame' }} {{ $data->last_name ?? '___________' }} {{ $data->first_name ?? '___________' }}, né(e) le {{ $data->birth_date ? date('d/m/Y', strtotime($data->birth_date)) : '___________' }} à {{ $data->birth_place ?? '___________' }} souhaite ne solliciter un poste que de {{ $data->weekly_hours ?? '___________' }} semaine au sein de la société Whatever, pour le moment.</p>

        <p>Cordialement</p>
        
        @if($employee_signature)
            <div style="height: 60px; margin-top: 20px; position: relative;">
                <img src="{{ public_path('storage/' . $employee_signature) }}" alt="Signature de l'employé" style="max-height: 100px;">
            </div>
        @else
            <div style="height: 60px; border-bottom: 1px solid #000; margin-top: 20px; position: relative;">
                <p style="position: absolute; bottom: 0; left: 50%; transform: translateX(-50%);">Signature</p>
            </div>
        @endif
    </div>  
</body>
</html> 