<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Certificat de signature électronique</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            color: #333;
            line-height: 1.6;
        }
        .container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .title {
            font-size: 24pt;
            font-weight: bold;
            color: #336699;
            margin-bottom: 10px;
        }
        .subtitle {
            font-size: 14pt;
            color: #666;
            margin-bottom: 30px;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 14pt;
            font-weight: bold;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }
        .info-block {
            margin-bottom: 15px;
        }
        .info-label {
            font-weight: bold;
            margin-right: 10px;
        }
        .info-value {
            font-family: 'Courier New', monospace;
        }
        .warning {
            border: 1px solid #f0ad4e;
            background-color: #fcf8e3;
            padding: 10px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10pt;
            color: #777;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
        .qr-code {
            text-align: center;
            margin: 20px 0;
        }
        .seal {
            border: 2px solid #336699;
            padding: 10px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: center;
        }
        .valid {
            color: #5cb85c;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="title">Certificat de Signature Électronique</div>
            <div class="subtitle">Attestation de signature électronique certifiée</div>
        </div>
        
        <div class="section">
            <div class="section-title">Informations sur le document</div>
            <div class="info-block">
                <span class="info-label">Document :</span>
                <span class="info-value">Contrat {{ $verification['contract']['title'] }}</span>
            </div>
            <div class="info-block">
                <span class="info-label">Statut du document :</span>
                <span class="info-value">{{ $verification['contract']['status'] }}</span>
            </div>
            <div class="info-block">
                <span class="info-label">Identifiant unique de signature :</span>
                <span class="info-value">{{ $signature_id }}</span>
            </div>
            <div class="info-block">
                <span class="info-label">Empreinte numérique du document :</span>
                <span class="info-value">{{ $verification['metadata']['document_hash'] }}</span>
            </div>
        </div>
        
        <div class="section">
            <div class="section-title">Informations sur le signataire</div>
            <div class="info-block">
                <span class="info-label">Nom du signataire :</span>
                <span class="info-value">{{ $verification['metadata']['user_name'] }}</span>
            </div>
            <div class="info-block">
                <span class="info-label">Email du signataire :</span>
                <span class="info-value">{{ $verification['metadata']['user_email'] }}</span>
            </div>
            <div class="info-block">
                <span class="info-label">Date et heure de signature :</span>
                <span class="info-value">{{ $verification['metadata']['timestamp'] }}</span>
            </div>
            <div class="info-block">
                <span class="info-label">Adresse IP :</span>
                <span class="info-value">{{ $verification['metadata']['ip_address'] }}</span>
            </div>
        </div>
        
        <div class="seal">
            <div><strong>STATUT DE LA SIGNATURE</strong></div>
            <div class="valid">✓ SIGNATURE VALIDE ET CERTIFIÉE</div>
            <div>Cette signature électronique est valide et n'a pas été modifiée depuis sa création</div>
        </div>
        
        <div class="qr-code">
            <div style="margin-bottom: 10px;"><strong>Code QR de vérification</strong></div>
            <img src="{{ $qr_code }}" alt="QR Code de vérification" width="150" height="150">
            <div style="margin-top: 10px;">Scannez ce code pour vérifier l'authenticité de cette signature</div>
        </div>
        
        <div class="warning">
            <strong>Note importante :</strong> Ce certificat atteste que le document a été signé électroniquement à la date et l'heure indiquées. 
            La signature électronique est uniquement valide si elle est vérifiée via le système de vérification en ligne ou le code QR ci-dessus.
        </div>
        
        <div class="footer">
            <p>Ce certificat a été généré le {{ $timestamp }}.</p>
            <p>Système de signature électronique - {{ config('app.name') }}</p>
        </div>
    </div>
</body>
</html> 