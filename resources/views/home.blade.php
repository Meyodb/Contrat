@extends('layouts.app')

@php
use Illuminate\Support\Facades\Auth;
@endphp

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <h1 class="mb-4">
                @if(Auth::check() && Auth::user()->is_admin)
                    Espace Administrateur
                @else
                    Espace Employé
                @endif
            </h1>
            
            <!-- Carte de bienvenue avec résumé rapide -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4>Bienvenue, {{ Auth::check() ? Auth::user()->name : 'Utilisateur' }} !</h4>
                            <p class="text-muted mb-0">
                                @if(Auth::check() && Auth::user()->is_admin)
                                    Vous êtes connecté en tant qu'administrateur.
                                @else
                                    Vous êtes connecté en tant qu'employé.
                                @endif
                            </p>
                        </div>
                        <div class="text-end">
                            @if(Auth::check() && Auth::user()->is_admin)
                                <a href="{{ route('admin.dashboard') }}" class="btn btn-primary">
                                    <i class="bi bi-speedometer2"></i> Tableau de bord
                                </a>
                            @endif
                        </div>
                    </div>
                    
                    @if(session('status'))
                        <!-- Les notifications sont maintenant gérées dans le layout principal -->
                    @endif
                </div>
            </div>
            
            @php
                $contract = Auth::check() ? Auth::user()->contract : null; 
            @endphp
            
            <!-- Affichage du contrat avec accès rapide -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Mon contrat</h5>
                </div>
                <div class="card-body">
                    @if($contract)
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="border-bottom pb-2 mb-3">Informations du contrat</h6>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Titre:</span>
                                        <span class="fw-bold">{{ $contract->title }}</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Type:</span>
                                        <span>{{ $contract->template->name ?? 'Non spécifié' }}</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Statut:</span>
                                        @if($contract->status == 'draft')
                                            <span class="badge bg-secondary">Brouillon</span>
                                        @elseif($contract->status == 'submitted')
                                            <span class="badge bg-primary">Soumis</span>
                                        @elseif($contract->status == 'in_review')
                                            <span class="badge bg-warning">En révision</span>
                                        @elseif($contract->status == 'admin_signed')
                                            <span class="badge bg-info">À signer</span>
                                        @elseif($contract->status == 'employee_signed')
                                            <span class="badge bg-success">Signé</span>
                                        @elseif($contract->status == 'completed')
                                            <span class="badge bg-success">Complété</span>
                                        @elseif($contract->status == 'rejected')
                                            <span class="badge bg-danger">Rejeté</span>
                                        @endif
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Date de création:</span>
                                        <span>{{ $contract->created_at ? $contract->created_at->format('d/m/Y') : 'Non spécifiée' }}</span>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6 class="border-bottom pb-2 mb-3">Actions disponibles</h6>
                                <div class="d-grid gap-2">
                                    <a href="{{ route('employee.contracts.show', $contract) }}" class="btn btn-primary">
                                        <i class="bi bi-eye"></i> Voir les détails du contrat
                                    </a>
                                    
                                    @if($contract->status == 'draft')
                                        <a href="{{ route('employee.contracts.edit', $contract) }}" class="btn btn-secondary">
                                            <i class="bi bi-pencil"></i> Modifier le contrat
                                        </a>
                                        <form method="POST" action="{{ route('employee.contracts.submit', $contract) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-success w-100">
                                                <i class="bi bi-check-circle"></i> Soumettre pour validation
                                            </button>
                                        </form>
                                    @endif
                                    
                                    @if($contract->status === 'admin_signed')
                                        <button type="button" class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#signModal">
                                            <i class="bi bi-pen"></i> Signer mon contrat
                                        </button>

                                        <!-- Modal de signature -->
                                        <div class="modal fade" id="signModal" tabindex="-1" aria-labelledby="signModalLabel" aria-hidden="true">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="signModalLabel">Signer le contrat</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form action="{{ route('employee.contracts.sign', $contract) }}" method="POST" id="signatureForm">
                                                        @csrf
                                                        <div class="modal-body">
                                                            <p class="mb-3">Veuillez dessiner votre signature dans le cadre ci-dessous :</p>
                                                            
                                                            <div class="signature-pad-container">
                                                                <canvas id="signature-pad" class="signature-pad" width="600" height="200" style="touch-action: none; border: 1px solid #ccc;"></canvas>
                                                                <div class="signature-pad-actions mt-2">
                                                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="clear-button">Effacer</button>
                                                                </div>
                                                            </div>
                                                            
                                                            <input type="hidden" name="signature" id="signature-data">
                                                            
                                                            <div class="form-check mt-3">
                                                                <input class="form-check-input" type="checkbox" id="signature-confirm" required>
                                                                <label class="form-check-label" for="signature-confirm">
                                                                    Je confirme avoir lu et accepté les conditions du contrat.
                                                                </label>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                            <button type="submit" class="btn btn-success" id="submit-signature">Signer</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        @push('scripts')
                                        <script>
                                            document.addEventListener('DOMContentLoaded', function() {
                                                let signaturePad;
                                                
                                                $('#signModal').on('shown.bs.modal', function () {
                                                    const canvas = document.getElementById('signature-pad');
                                                    signaturePad = new SignaturePad(canvas, {
                                                        backgroundColor: 'rgb(255, 255, 255)',
                                                        penColor: 'rgb(0, 0, 0)'
                                                    });
                                                    
                                                    // Redimensionner le canvas
                                                    function resizeCanvas() {
                                                        const ratio = Math.max(window.devicePixelRatio || 1, 1);
                                                        canvas.width = canvas.offsetWidth * ratio;
                                                        canvas.height = canvas.offsetHeight * ratio;
                                                        canvas.getContext("2d").scale(ratio, ratio);
                                                        signaturePad.clear();
                                                    }
                                                    
                                                    window.addEventListener("resize", resizeCanvas);
                                                    resizeCanvas();
                                                });
                                                
                                                // Bouton pour effacer
                                                document.getElementById('clear-button').addEventListener('click', function() {
                                                    if (signaturePad) {
                                                        signaturePad.clear();
                                                    }
                                                });
                                                
                                                // Validation du formulaire
                                                document.getElementById('signatureForm').addEventListener('submit', function(e) {
                                                    if (!signaturePad || signaturePad.isEmpty()) {
                                                        e.preventDefault();
                                                        alert('Veuillez dessiner votre signature avant de soumettre.');
                                                        return false;
                                                    }
                                                    
                                                    const confirmCheckbox = document.getElementById('signature-confirm');
                                                    if (!confirmCheckbox.checked) {
                                                        e.preventDefault();
                                                        alert('Veuillez confirmer que vous avez lu et accepté les conditions du contrat.');
                                                        return false;
                                                    }
                                                    
                                                    document.getElementById('signature-data').value = signaturePad.toDataURL();
                                                    return true;
                                                });
                                            });
                                        </script>
                                        @endpush
                                    @endif
                                    
                                    @if($contract->status == 'completed')
                                        <!-- Le bouton de téléchargement a été retiré -->
                                    @endif
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-info mb-4 position-relative">
                            <button type="button" class="btn-close position-absolute" style="top: 10px; right: 10px; font-size: 0.8rem;" data-bs-dismiss="alert" aria-label="Fermer"></button>
                            <h6>Aucun contrat trouvé</h6>
                            <p>Vous n'avez pas encore de contrat. Vous pouvez en créer un en cliquant sur le bouton ci-dessous.</p>
                        </div>
                        <div class="text-center">
                            <a href="{{ route('employee.contracts.create') }}" class="btn btn-success btn-lg">
                                <i class="bi bi-plus-circle"></i> Créer mon contrat
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
