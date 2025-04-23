@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3>Testeur de Signature</h3>
                </div>

                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Utilisez cet outil pour tester la fonctionnalité de signature. Dessinez votre signature dans la zone ci-dessous, puis cliquez sur "Enregistrer" pour la sauvegarder.
                    </div>

                    <div class="signature-container">
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="border rounded p-3">
                                    <canvas id="signature-pad" class="signature-pad" width="100%" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-12 d-flex justify-content-between">
                                <button id="clear-button" class="btn btn-secondary">
                                    <i class="fas fa-eraser"></i> Effacer
                                </button>
                                <button id="save-button" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Enregistrer
                                </button>
                            </div>
                        </div>

                        <div id="loading-indicator" class="text-center mt-3 mb-3 d-none">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Chargement...</span>
                            </div>
                            <p class="mt-2">Enregistrement de la signature en cours...</p>
                        </div>
                    </div>

                    <!-- Formulaire caché pour soumettre la signature -->
                    <form id="signature-form" method="POST" action="{{ route('test.save.signature') }}" target="submission-iframe" style="display: none;">
                        @csrf
                        <input type="hidden" name="signature" id="signature-data">
                        <input type="hidden" name="user_id" value="10">
                    </form>
                    
                    <!-- iFrame pour soumettre sans naviguer ailleurs -->
                    <iframe name="submission-iframe" id="submission-iframe" style="display:none;"></iframe>

                    <!-- Conteneur pour les résultats -->
                    <div id="result-container" class="mt-4 d-none">
                        <div class="row">
                            <div class="col-md-12">
                                <div id="result-message" class="alert" role="alert"></div>
                            </div>
                        </div>
                        
                        <div id="signature-preview-container" class="row mt-3 d-none">
                            <div class="col-md-12">
                                <h5>Aperçu de la signature enregistrée:</h5>
                                <div class="border p-3 text-center bg-light">
                                    <img id="signature-preview" src="" alt="Signature enregistrée" class="img-fluid" style="max-height: 150px;">
                                </div>
                                <p class="mt-2 text-secondary">
                                    <small>URL: <span id="signature-url" class="text-break"></span></small>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Informations de débogage -->
                    <div class="mt-4">
                        <button class="btn btn-sm btn-outline-secondary" type="button" data-toggle="collapse" data-target="#debug-info">
                            Afficher les informations de débogage
                        </button>
                        <div class="collapse mt-2" id="debug-info">
                            <div class="card card-body bg-light">
                                <h6>Journal de débogage:</h6>
                                <pre id="debug-log" class="small" style="max-height: 200px; overflow-y: auto; background-color: #f8f9fa; padding: 10px;"></pre>
                                <hr>
                                <h6>Détails de la requête:</h6>
                                <pre id="request-details" class="small" style="max-height: 150px; overflow-y: auto; background-color: #f8f9fa; padding: 10px;">Aucune requête envoyée</pre>
                                <hr>
                                <h6>Détails de la réponse:</h6>
                                <pre id="response-details" class="small" style="max-height: 150px; overflow-y: auto; background-color: #f8f9fa; padding: 10px;">Aucune réponse reçue</pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .signature-pad {
        width: 100%;
        height: 200px;
        border: none;
        background-color: white;
    }
    #debug-log {
        white-space: pre-wrap;
        word-break: break-word;
    }
</style>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script>
    // Initialisation des variables
    let debugLog = [];
    
    // Fonction pour ajouter des messages de débogage
    function log(message, data = null) {
        const timestamp = new Date().toISOString().substr(11, 8);
        const logEntry = `[${timestamp}] ${message}`;
        
        // Ajouter au journal
        debugLog.push(data ? `${logEntry}\n${JSON.stringify(data, null, 2)}` : logEntry);
        
        // Limiter la taille du journal à 100 entrées
        if (debugLog.length > 100) {
            debugLog.shift();
        }
        
        // Mettre à jour l'affichage
        document.getElementById('debug-log').textContent = debugLog.join('\n');
        
        // Également journaliser dans la console
        if (data) {
            console.log(message, data);
        } else {
            console.log(message);
        }
    }
    
    // Initialisation au chargement du document
    document.addEventListener('DOMContentLoaded', function() {
        log('Initialisation du testeur de signature');
        
        // Initialisation du pad de signature
        const canvas = document.getElementById('signature-pad');
        const signaturePad = new SignaturePad(canvas, {
            backgroundColor: 'rgb(255, 255, 255)',
            penColor: 'rgb(0, 0, 0)'
        });
        
        // Ajuster la taille du canvas
        function resizeCanvas() {
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext("2d").scale(ratio, ratio);
            signaturePad.clear(); // Effacer le contenu après redimensionnement
            log('Canvas redimensionné', { width: canvas.width, height: canvas.height, ratio: ratio });
        }
        
        // Redimensionner au chargement et lors des changements de taille
        window.onresize = resizeCanvas;
        resizeCanvas();
        
        // Gérer le bouton Effacer
        document.getElementById('clear-button').addEventListener('click', function() {
            signaturePad.clear();
            log('Signature effacée');
        });
        
        // Gérer le bouton Enregistrer
        document.getElementById('save-button').addEventListener('click', function() {
            if (signaturePad.isEmpty()) {
                log('Aucune signature à enregistrer');
                alert('Veuillez signer avant d\'enregistrer');
                return;
            }
            
            try {
                log('Préparation de l\'enregistrement de la signature');
                
                // Afficher l'indicateur de chargement
                document.getElementById('loading-indicator').classList.remove('d-none');
                
                // Récupérer les données de signature
                const signatureData = signaturePad.toDataURL('image/png');
                log('Données de signature générées', { 
                    length: signatureData.length,
                    preview: signatureData.substring(0, 50) + '...' 
                });
                
                // Définir les données dans le formulaire
                document.getElementById('signature-data').value = signatureData;
                
                // Configurer la détection de la réponse depuis l'iframe
                const iframe = document.getElementById('submission-iframe');
                
                // Surveiller les changements dans l'iframe
                iframe.onload = function() {
                    log('iframe.onload déclenché');
                    
                    try {
                        // Essayer d'accéder au contenu de l'iframe (peut échouer en raison de restrictions CORS)
                        let iframeContent = null;
                        try {
                            iframeContent = iframe.contentDocument || iframe.contentWindow.document;
                            log('Contenu iframe accessible', { 
                                hasBody: !!iframeContent.body,
                                textContent: iframeContent.body ? (iframeContent.body.textContent || '').substring(0, 100) : 'non disponible'
                            });
                        } catch (e) {
                            log('Erreur d\'accès au contenu iframe', { message: e.message });
                        }
                        
                        // Essayer d'extraire la réponse JSON
                        let responseData = null;
                        if (iframeContent && iframeContent.body) {
                            const text = iframeContent.body.textContent;
                            try {
                                if (text && text.trim()) {
                                    responseData = JSON.parse(text);
                                    log('Réponse JSON analysée', responseData);
                                    document.getElementById('response-details').textContent = JSON.stringify(responseData, null, 2);
                                }
                            } catch (e) {
                                log('Erreur d\'analyse de la réponse JSON', { 
                                    message: e.message,
                                    responseText: text ? text.substring(0, 100) + '...' : 'vide' 
                                });
                                document.getElementById('response-details').textContent = 
                                    'Erreur d\'analyse: ' + e.message + '\n\nTexte brut:\n' + 
                                    (text ? text.substring(0, 500) + '...' : 'Aucun contenu');
                            }
                        }
                        
                        // Mettre à jour l'interface en fonction de la réponse
                        handleResponse(responseData);
                    } catch (e) {
                        log('Erreur lors du traitement de la réponse', { message: e.message, stack: e.stack });
                        handleError(e);
                    } finally {
                        // Cacher l'indicateur de chargement
                        document.getElementById('loading-indicator').classList.add('d-none');
                    }
                };
                
                // Enregistrer les détails de la requête
                document.getElementById('request-details').textContent = JSON.stringify({
                    url: document.getElementById('signature-form').action,
                    method: 'POST',
                    timestamp: new Date().toISOString(),
                    signatureLength: signatureData.length,
                    signaturePreview: signatureData.substring(0, 50) + '...'
                }, null, 2);
                
                // Soumettre le formulaire via l'iframe
                log('Soumission du formulaire via iframe');
                document.getElementById('signature-form').submit();
                
                // Configuration d'un timeout de secours pour éviter un chargement infini
                setTimeout(function() {
                    if (!document.getElementById('loading-indicator').classList.contains('d-none')) {
                        log('TIMEOUT: Aucune réponse reçue après 10 secondes');
                        document.getElementById('loading-indicator').classList.add('d-none');
                        
                        // Afficher un message d'erreur
                        const resultContainer = document.getElementById('result-container');
                        const resultMessage = document.getElementById('result-message');
                        resultContainer.classList.remove('d-none');
                        resultMessage.classList.remove('alert-success', 'alert-warning');
                        resultMessage.classList.add('alert-danger');
                        resultMessage.innerHTML = '<strong>Erreur:</strong> Aucune réponse reçue du serveur après 10 secondes. Vérifiez la console pour plus de détails.';
                    }
                }, 10000);
                
            } catch (e) {
                log('Erreur lors de la préparation de l\'enregistrement', { message: e.message, stack: e.stack });
                handleError(e);
            }
        });
        
        // Fonction pour gérer la réponse
        function handleResponse(data) {
            log('Traitement de la réponse');
            
            const resultContainer = document.getElementById('result-container');
            const resultMessage = document.getElementById('result-message');
            const previewContainer = document.getElementById('signature-preview-container');
            
            resultContainer.classList.remove('d-none');
            
            if (data && data.success) {
                log('Réponse de succès reçue', data);
                
                // Afficher le message de succès
                resultMessage.classList.remove('alert-danger', 'alert-warning');
                resultMessage.classList.add('alert-success');
                resultMessage.innerHTML = '<strong>Succès!</strong> ' + data.message;
                
                // Afficher la prévisualisation
                if (data.url) {
                    document.getElementById('signature-preview').src = data.url;
                    document.getElementById('signature-url').textContent = data.url;
                    previewContainer.classList.remove('d-none');
                    
                    log('Prévisualisation affichée', { url: data.url });
                } else {
                    previewContainer.classList.add('d-none');
                    log('Aucune URL de prévisualisation disponible');
                }
            } else if (data) {
                log('Réponse d\'erreur reçue', data);
                
                // Afficher le message d'erreur
                resultMessage.classList.remove('alert-success', 'alert-warning');
                resultMessage.classList.add('alert-danger');
                resultMessage.innerHTML = '<strong>Erreur:</strong> ' + (data.message || 'Une erreur inconnue est survenue');
                
                // Cacher la prévisualisation
                previewContainer.classList.add('d-none');
            } else {
                log('Aucune donnée de réponse valide');
                
                // Afficher un message d'avertissement
                resultMessage.classList.remove('alert-success', 'alert-danger');
                resultMessage.classList.add('alert-warning');
                resultMessage.innerHTML = '<strong>Avertissement:</strong> Aucune réponse valide reçue du serveur';
                
                // Cacher la prévisualisation
                previewContainer.classList.add('d-none');
            }
        }
        
        // Fonction pour gérer les erreurs
        function handleError(error) {
            log('Gestion d\'erreur', { message: error.message, stack: error.stack });
            
            document.getElementById('loading-indicator').classList.add('d-none');
            
            const resultContainer = document.getElementById('result-container');
            const resultMessage = document.getElementById('result-message');
            
            resultContainer.classList.remove('d-none');
            resultMessage.classList.remove('alert-success', 'alert-warning');
            resultMessage.classList.add('alert-danger');
            resultMessage.innerHTML = '<strong>Erreur:</strong> ' + error.message;
            
            document.getElementById('signature-preview-container').classList.add('d-none');
        }
    });
</script>
@endsection 