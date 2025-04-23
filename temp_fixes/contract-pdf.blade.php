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

    <!-- [Autres articles du contrat...] -->
    
    <div class="footer">
        <p>Fait en double exemplaire originaux dont un pour chacune des parties.</p>
        
        <p>A Paris, le {{ (is_object($data) && isset($data->contract_start_date)) ? date('d/m/Y', strtotime($data->contract_start_date)) : '___________' }}</p>
        
        <!-- Signatures -->
        <div style="margin-top: 80px; margin-bottom: 20px;">
            <table width="100%" style="border-spacing: 0;">
                <tr>
                    <td width="45%" style="text-align: center; vertical-align: top; padding-top: 10px;">
                        <p style="margin-bottom: 10px; text-align: center;"><strong>L'employeur</strong></p>
                        <p style="text-align: center;">M BRIAND Grégory</p>
                        <p style="text-align: center;">Pour la société</p>
                        <p style="text-align: center;">{{ $admin->name ?? 'L\'administrateur' }}</p>
                        <p>&nbsp;</p>
                        <div style="text-align: center;">
                        @if(!empty($adminSignatureBase64))
                            <img src="data:image/png;base64,{{ $adminSignatureBase64 }}" alt="Signature de l'employeur" style="max-height: 70px; margin: 0 auto;">
                        @else
                            <div style="width:150px; height:70px; border-bottom: 1px solid #000; display:inline-block; text-align:center; margin: 0 auto;">
                                Signature de l'employeur
                            </div>
                        @endif
                        </div>
                    </td>
                    <td width="10%">&nbsp;</td>
                    <td width="45%" style="text-align: center; vertical-align: top; padding-top: 10px;">
                        <p style="margin-bottom: 10px; text-align: center;"><strong>L'employé(e)</strong></p>
                        <p style="text-align: center;">{{ $data->first_name ?? '' }} {{ $data->last_name ?? '' }}</p>
                        <p>&nbsp;</p>
                        <div style="text-align: center;">
                        @if(!empty($employeeSignatureBase64))
                            <img src="data:image/png;base64,{{ $employeeSignatureBase64 }}" alt="Signature de l'employé" style="max-height: 70px; margin: 0 auto;">
                        @else
                            <div style="width:150px; height:70px; border-bottom: 1px solid #000; display:inline-block; text-align:center; margin: 0 auto;">
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

    <div class="addendum" style="border-top: none; margin-top: 50px;">
        <p>Paris le {{ (is_object($data) && isset($data->contract_start_date)) ? date('d/m/Y', strtotime($data->contract_start_date)) : '___________' }}</p>

        <p>Je soussigné, {{ (is_object($data) && isset($data->gender) && $data->gender == 'M') ? 'Monsieur' : 'Madame' }} {{ (is_object($data) && isset($data->last_name)) ? $data->last_name : '___________' }} {{ (is_object($data) && isset($data->first_name)) ? $data->first_name : '___________' }}, né(e) le {{ (is_object($data) && isset($data->birth_date)) ? date('d/m/Y', strtotime($data->birth_date)) : '___________' }} à {{ (is_object($data) && isset($data->birth_place)) ? $data->birth_place : '___________' }} souhaite ne solliciter un poste que de {{ (is_object($data) && isset($data->weekly_hours)) ? $data->weekly_hours : '___________' }} heures par semaine au sein de la société Whatever, pour le moment.</p>

        <p>Cordialement,</p>

        <div style="margin-top:60px;">
            <p><strong>Signature :</strong></p>
            @if(!empty($employeeSignatureBase64))
                <img src="data:image/png;base64,{{ $employeeSignatureBase64 }}" alt="Signature de l'employé" style="max-height: 70px; margin-top: 20px;">
            @else
                <div style="width:150px; height:70px; border-bottom: 1px solid #000; margin-top: 20px;">
                </div>
            @endif
        </div>
    </div>
    
    <!-- Pied de page avec informations de génération -->
    <div style="position: fixed; bottom: 10px; left: 10px; font-size: 8pt; color: #999;">
        Document généré le {{ $generatedAt }}
    </div>
</body>
</html> 