<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avenant au contrat de travail</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12pt;
            line-height: 1.6;
            color: #000;
            margin: 0;
            padding: 20px;
        }
        .text-center {
            text-align: center;
        }
        .mt-4 {
            margin-top: 40px;
        }
        .mb-4 {
            margin-bottom: 40px;
        }
        .signature-container {
            margin-top: 60px;
            display: flex;
            justify-content: space-between;
        }
        .signature-block {
            width: 45%;
        }
        h1 {
            font-size: 16pt;
            font-weight: bold;
            text-align: center;
            margin-bottom: 30px;
        }
        .company-info {
            text-align: center;
            margin-bottom: 30px;
        }
        .employee-info {
            text-align: center;
            margin-bottom: 30px;
        }
        .content {
            margin-bottom: 40px;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>AVENANT n°{{ $avenant_number ?? '1' }}</h1>
        
        <div class="company-info">
            <p>
                au contrat de travail à durée indéterminée conclu le {{ isset($contract_date) ? \Carbon\Carbon::parse($contract_date)->format('d F Y') : '14 février 2025' }}<br>
                entre La {{ $company_name ?? 'S.A.R.L WHAT EVER' }}<br>
                {{ $company_address ?? '54 avenue De Kléber' }}<br>
                {{ $company_postal_code ?? '75016' }} {{ $company_city ?? 'PARIS' }}<br>
                Siret {{ $company_siret ?? '439 077 462 00026' }}<br>
                et
            </p>
        </div>
        
        <div class="employee-info">
            <p>{{ isset($employee_gender) && $employee_gender == 'F' ? 'Madame' : 'Monsieur' }} {{ $employee_name ?? 'Sarah Hersom' }}</p>
        </div>
        
        <div class="content">
            <p>Il a été convenu ce qui suit :</p>
            
            <p><strong>Motif de l'avenant :</strong> {{ $motif ?? 'Modification de la durée du travail et de la rémunération' }}</p>
            
            <p>D'un commun accord, nous avons décidé de modifier la durée hebdomadaire de 
            {{ isset($employee_gender) && $employee_gender == 'F' ? 'Madame' : 'Monsieur' }} {{ $employee_name ?? 'Sarah Hersom' }} 
            à {{ $new_hours ?? '20' }} heures hebdomadaire à compter du {{ isset($effective_date) ? \Carbon\Carbon::parse($effective_date)->format('d F Y') : '1er mars 2025' }}.</p>
            
            <p>Son salaire mensuel brut sera de {{ $new_salary ?? '1024.43' }} euros pour un horaire mensuel de {{ $monthly_hours ?? '86.67' }}H.</p>
            
            <p>Les autres termes du contrat signé le {{ isset($contract_date) ? \Carbon\Carbon::parse($contract_date)->format('d F Y') : '14 février 2025' }} restent inchangés.</p>
        </div>
        
        <div class="footer">
            <p>Fait en double exemplaire</p>
            <p>A {{ $signing_location ?? 'Paris' }}, le {{ isset($signing_date) ? \Carbon\Carbon::parse($signing_date)->format('d F Y') : '1er mars 2025' }}</p>
        </div>
        
        <div class="signature-container">
            <div class="signature-block">
                <p>Signature précédée<br> 
                de la mention « lu et approuvé »<br>
                {{ isset($employee_gender) && $employee_gender == 'F' ? 'Madame' : 'Monsieur' }} {{ $employee_name ?? 'Sarah Hersom' }}</p>
                
                @if(isset($employeeSignatureBase64) && !empty($employeeSignatureBase64))
                <div class="signature">
                    <img src="data:image/png;base64,{{ $employeeSignatureBase64 }}" alt="Signature de l'employé" style="max-height: 100px;">
                </div>
                @endif
            </div>
            
            <div class="signature-block">
                <p>Pour {{ $company_name ?? 'What Ever' }}<br>
                Le Président {{ $admin_name ?? 'M Briand Grégory' }}</p>
                
                @if(isset($adminSignatureBase64) && !empty($adminSignatureBase64))
                <div class="signature">
                    <img src="data:image/png;base64,{{ $adminSignatureBase64 }}" alt="Signature de l'employeur" style="max-height: 100px;">
                </div>
                @endif
            </div>
        </div>
    </div>
</body>
</html> 