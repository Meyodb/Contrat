@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Avenant n°{{ $avenant->avenant_number }}</h5>
                    <span class="badge bg-info">{{ $avenant->title }}</span>
                </div>
                <div class="card-body">
                    @if(session('status'))
                        <div class="alert alert-success position-relative">
                            <button type="button" class="btn-close position-absolute" style="top: 10px; right: 10px; font-size: 0.8rem;" data-bs-dismiss="alert" aria-label="Fermer"></button>
                            {{ session('status') }}
                        </div>
                    @endif
                    
                    @if(session('error'))
                        <div class="alert alert-danger position-relative">
                            <button type="button" class="btn-close position-absolute" style="top: 10px; right: 10px; font-size: 0.8rem;" data-bs-dismiss="alert" aria-label="Fermer"></button>
                            {{ session('error') }}
                        </div>
                    @endif
                    
                    <div class="alert alert-info">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Lié au contrat principal</h6>
                                <p class="mb-0">
                                    <strong>Créé le :</strong> {{ $avenant->created_at->format('d/m/Y') }}
                                </p>
                                <p class="mb-0">
                                    <strong>Date d'effet :</strong> 
                                    @if($avenant->data && $avenant->data->effective_date)
                                        {{ \Carbon\Carbon::parse($avenant->data->effective_date)->format('d/m/Y') }}
                                    @else
                                        Non spécifiée
                                    @endif
                                </p>
                            </div>
                            <a href="{{ route('employee.contracts.avenants', $avenant->parentContract) }}" class="btn btn-outline-primary">
                                <i class="bi bi-file-earmark"></i> Voir l'historique complet
                            </a>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-light">Conditions initiales</div>
                                <div class="card-body">
                                    @if($avenant->parentContract && $avenant->parentContract->data)
                                        <table class="table table-borderless">
                                            <tr>
                                                <td class="fw-bold">Horaire hebdomadaire</td>
                                                <td>{{ $avenant->parentContract->data->work_hours ?? 'Non spécifié' }} h</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Salaire mensuel brut</td>
                                                <td>{{ $avenant->parentContract->data->monthly_gross_salary ?? 'Non spécifié' }} €</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Taux horaire</td>
                                                <td>{{ $avenant->parentContract->data->hourly_rate ?? 'Non spécifié' }} €</td>
                                            </tr>
                                        </table>
                                    @else
                                        <p>Informations non disponibles</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-light">Nouvelles conditions</div>
                                <div class="card-body">
                                    @if($avenant->data)
                                        <table class="table table-borderless">
                                            <tr>
                                                <td class="fw-bold">Horaire hebdomadaire</td>
                                                <td>{{ $avenant->data->work_hours ?? 'Non spécifié' }} h</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Salaire mensuel brut</td>
                                                <td>{{ $avenant->data->monthly_gross_salary ?? 'Non spécifié' }} €</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Taux horaire</td>
                                                <td>{{ $avenant->data->hourly_rate ?? 'Non spécifié' }} €</td>
                                            </tr>
                                        </table>
                                    @else
                                        <p>Informations non disponibles</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mt-3">
                        <div class="card-header bg-light">Statut de l'avenant</div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    @if($avenant->status == 'draft')
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-secondary me-2">Brouillon</span>
                                            <p class="mb-0">Cet avenant n'a pas encore été soumis pour signature.</p>
                                        </div>
                                    @elseif($avenant->status == 'submitted')
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-primary me-2">Soumis</span>
                                            <p class="mb-0">Cet avenant a été soumis et est en attente de signature par l'administrateur.</p>
                                        </div>
                                    @elseif($avenant->status == 'admin_signed')
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-info me-2">À signer</span>
                                            <p class="mb-0">Cet avenant a été signé par l'administrateur et attend votre signature.</p>
                                        </div>
                                    @elseif($avenant->status == 'employee_signed' || $avenant->status == 'completed')
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-success me-2">Complété</span>
                                            <p class="mb-0">Cet avenant a été signé par les deux parties le {{ $avenant->employee_signed_at ? $avenant->employee_signed_at->format('d/m/Y') : 'date inconnue' }}.</p>
                                        </div>
                                    @elseif($avenant->status == 'rejected')
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-danger me-2">Rejeté</span>
                                            <p class="mb-0">Cet avenant a été rejeté.</p>
                                        </div>
                                    @endif
                                </div>
                                
                                <div>
                                    @if($avenant->status == 'admin_signed')
                                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#signAvenantModal">
                                            <i class="bi bi-pen"></i> Signer l'avenant
                                        </button>
                                    @endif
                                    
                                    @if($avenant->status == 'completed' || $avenant->status == 'employee_signed')
                                        <a href="{{ route('employee.contracts.download', $avenant) }}" class="btn btn-outline-success">
                                            <i class="bi bi-download"></i> Télécharger
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Signatures -->
                    @if($avenant->status == 'admin_signed' || $avenant->status == 'employee_signed' || $avenant->status == 'completed')
                    <div class="card mt-4">
                        <div class="card-header bg-light">Signatures</div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="text-center mb-2">
                                        <h6 class="fw-bold">Signature de l'administrateur</h6>
                                    </div>
                                    <div class="d-flex flex-column align-items-center" style="min-height: 150px;">
                                        <div class="signature-container text-center" style="height: 100px; width: 100%; display: flex; align-items: center; justify-content: center;">
                                            @if($avenant->admin_signature)
                                                @php
                                                    $adminSignatureFilename = basename($avenant->admin_signature);
                                                @endphp
                                                <img src="{{ route('signature.admin', ['filename' => 'admin_signature.png']) }}" 
                                                     alt="Signature de l'administrateur" 
                                                     class="img-fluid" 
                                                     style="max-height: 100px;">
                                            @else
                                                <p class="text-muted">Non signé</p>
                                            @endif
                                        </div>
                                        @if($avenant->admin_signed_at)
                                            <p class="small text-muted mt-2 text-center">Signé le {{ $avenant->admin_signed_at->format('d/m/Y') }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-center mb-2">
                                        <h6 class="fw-bold">Signature de l'employé</h6>
                                    </div>
                                    <div class="d-flex flex-column align-items-center" style="min-height: 150px;">
                                        <div class="signature-container text-center" style="height: 100px; width: 100%; display: flex; align-items: center; justify-content: center;">
                                            @if($avenant->employee_signature)
                                                @php
                                                    $employeeSignatureFilename = basename($avenant->employee_signature);
                                                @endphp
                                                <img src="{{ route('signature', ['filename' => $employeeSignatureFilename]) }}" 
                                                     alt="Signature de l'employé" 
                                                     class="img-fluid" 
                                                     style="max-height: 100px;">
                                            @else
                                                <p class="text-muted">Non signé</p>
                                            @endif
                                        </div>
                                        @if($avenant->employee_signed_at)
                                            <p class="small text-muted mt-2 text-center">Signé le {{ $avenant->employee_signed_at->format('d/m/Y') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
                <div class="card-footer bg-white d-flex justify-content-between">
                    <a href="{{ route('employee.contracts.avenants', $avenant->parentContract) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Retour à l'historique
                    </a>
                    <a href="{{ route('employee.contracts.preview', $avenant) }}" class="btn btn-outline-primary" target="_blank">
                        <i class="bi bi-eye"></i> Prévisualiser en PDF
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de signature pour l'avenant -->
@if($avenant->status == 'admin_signed')
<div class="modal fade" id="signAvenantModal" tabindex="-1" aria-labelledby="signAvenantModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="signAvenantModalLabel">Signer l'avenant n°{{ $avenant->avenant_number }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <p><strong>Important :</strong> En signant cet avenant, vous acceptez les modifications apportées à vos conditions de travail.</p>
                    <ul>
                        <li>Nouvel horaire hebdomadaire : {{ $avenant->data ? $avenant->data->work_hours : '' }} heures</li>
                        <li>Nouveau salaire mensuel brut : {{ $avenant->data ? $avenant->data->monthly_gross_salary : '' }} €</li>
                        <li>Date d'effet : {{ $avenant->data && $avenant->data->effective_date ? \Carbon\Carbon::parse($avenant->data->effective_date)->format('d/m/Y') : '' }}</li>
                    </ul>
                </div>
                
                <form action="{{ route('employee.contracts.sign', $avenant) }}" method="POST" id="signatureForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Votre signature :</label>
                        <div id="signatureCanvas" class="border rounded" style="width: 100%; height: 200px;"></div>
                        <input type="hidden" name="employee_signature" id="signatureInput">
                        <div class="mt-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="clearSignature">
                                <i class="bi bi-eraser"></i> Effacer
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-success" id="submitSignature">
                    <i class="bi bi-pen"></i> Signer l'avenant
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@push('scripts')
@if($avenant->status == 'admin_signed')
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var canvas = document.getElementById('signatureCanvas');
        var signaturePad = new SignaturePad(canvas, {
            backgroundColor: 'rgb(255, 255, 255)',
            penColor: 'rgb(0, 0, 0)'
        });
        
        // Adapter la taille du canvas
        function resizeCanvas() {
            var ratio = Math.max(window.devicePixelRatio || 1, 1);
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext("2d").scale(ratio, ratio);
            signaturePad.clear(); // Effacer le contenu après redimensionnement
        }
        
        window.addEventListener("resize", resizeCanvas);
        resizeCanvas();
        
        // Effacer la signature
        document.getElementById('clearSignature').addEventListener('click', function() {
            signaturePad.clear();
        });
        
        // Soumettre le formulaire avec la signature
        document.getElementById('submitSignature').addEventListener('click', function() {
            if (signaturePad.isEmpty()) {
                alert('Veuillez signer le document avant de soumettre.');
                return;
            }
            
            var data = signaturePad.toDataURL('image/png');
            document.getElementById('signatureInput').value = data;
            document.getElementById('signatureForm').submit();
        });
    });
</script>
@endif
@endpush
@endsection 