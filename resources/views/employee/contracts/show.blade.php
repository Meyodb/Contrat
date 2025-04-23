@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Détails de mon contrat</h5>
                    <span class="badge 
                        @if($contract->status == 'draft') bg-secondary
                        @elseif($contract->status == 'submitted') bg-primary
                        @elseif($contract->status == 'in_review') bg-warning
                        @elseif($contract->status == 'admin_signed') bg-info
                        @elseif($contract->status == 'employee_signed') bg-success
                        @elseif($contract->status == 'completed') bg-success
                        @elseif($contract->status == 'rejected') bg-danger
                        @endif
                    ">
                        @if($contract->status == 'draft') Brouillon
                        @elseif($contract->status == 'submitted') Soumis
                        @elseif($contract->status == 'in_review') En révision
                        @elseif($contract->status == 'admin_signed') À signer
                        @elseif($contract->status == 'employee_signed') Signé
                        @elseif($contract->status == 'completed') Complété
                        @elseif($contract->status == 'rejected') Rejeté
                        @endif
                    </span>
                </div>
                <div class="card-body">
                    @if(session('status'))
                        <!-- Les notifications sont maintenant gérées dans le layout principal -->
                    @endif
                    
                    @if($contract->status === 'completed' && $contract->final_document_path)
                        <div class="alert alert-success mb-4 position-relative">
                            <button type="button" class="btn-close position-absolute" style="top: 10px; right: 10px; font-size: 0.8rem;" data-bs-dismiss="alert" aria-label="Fermer"></button>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-1"><i class="bi bi-file-earmark-check"></i> Contrat finalisé et signé !</h5>
                                    <p class="mb-0">Votre contrat a été signé par toutes les parties et est maintenant disponible.</p>
                                </div>
                                <a href="{{ route('employee.contracts.download', $contract) }}" class="btn btn-success">
                                    <i class="bi bi-download"></i> Télécharger le contrat
                                </a>
                            </div>
                        </div>
                    @elseif($contract->status === 'employee_signed')
                        <div class="alert alert-info mb-4 position-relative">
                            <button type="button" class="btn-close position-absolute" style="top: 10px; right: 10px; font-size: 0.8rem;" data-bs-dismiss="alert" aria-label="Fermer"></button>
                            <div>
                                <h5 class="mb-1"><i class="bi bi-file-earmark-check"></i> Contrat signé !</h5>
                                <p class="mb-0">Vous avez signé votre contrat. Il est en cours de finalisation par l'administration.</p>
                            </div>
                        </div>
                    @endif
                    
                    <!-- Notification spéciale pour les avenants -->
                    @if($contract->isAvenant())
                        <div class="alert alert-warning mb-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-1"><i class="bi bi-file-earmark-text"></i> Avenant au contrat</h5>
                                    <p class="mb-0">Ceci est l'avenant n°{{ $contract->avenant_number }} à votre contrat principal.</p>
                                    <p class="mb-0">
                                        <strong>Date d'effet :</strong> 
                                        {{ $contract->data && $contract->data->effective_date ? \Carbon\Carbon::parse($contract->data->effective_date)->format('d/m/Y') : 'Non spécifiée' }}
                                    </p>
                                </div>
                                <a href="{{ route('employee.contracts.show', $contract->parentContract) }}" class="btn btn-outline-primary">
                                    <i class="bi bi-file-earmark"></i> Voir le contrat principal
                                </a>
                            </div>
                        </div>
                    @endif
                    
                    <!-- Étapes du processus -->
                    <div class="mb-4">
                        <div class="progress" style="height: 4px;">
                            <div class="progress-bar bg-success" role="progressbar" 
                                style="width: {{ $contract->status == 'draft' ? '33%' : ($contract->status == 'submitted' || $contract->status == 'in_review' ? '66%' : '100%') }};" 
                                aria-valuenow="{{ $contract->status == 'draft' ? '33' : ($contract->status == 'submitted' || $contract->status == 'in_review' ? '66' : '100') }}" 
                                aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="d-flex justify-content-between mt-2 text-muted small">
                            <div class="text-success">
                                <i class="bi bi-check-circle-fill"></i> Création
                            </div>
                            <div class="{{ $contract->status != 'draft' ? 'text-success' : 'text-muted' }}">
                                <i class="{{ $contract->status != 'draft' ? 'bi bi-check-circle-fill' : 'bi bi-circle' }}"></i> Soumission
                            </div>
                            <div class="{{ in_array($contract->status, ['admin_signed', 'employee_signed', 'completed']) ? 'text-success' : 'text-muted' }}">
                                <i class="{{ in_array($contract->status, ['admin_signed', 'employee_signed', 'completed']) ? 'bi bi-check-circle-fill' : 'bi bi-circle' }}"></i> Validation
                            </div>
                        </div>
                    </div>
                    
                    <!-- Navigation par onglets pour une meilleure organisation -->
                    <ul class="nav nav-tabs mb-4" id="contractTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab" aria-controls="info" aria-selected="true">
                                <i class="bi bi-info-circle"></i> Informations générales
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal" type="button" role="tab" aria-controls="personal" aria-selected="false">
                                <i class="bi bi-person"></i> Informations personnelles
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="contract-tab" data-bs-toggle="tab" data-bs-target="#contract" type="button" role="tab" aria-controls="contract" aria-selected="false">
                                <i class="bi bi-file-earmark-text"></i> Détails du contrat
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="preview-tab" data-bs-toggle="tab" data-bs-target="#preview" type="button" role="tab" aria-controls="preview" aria-selected="false">
                                <i class="bi bi-eye"></i> Prévisualisation
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content" id="contractTabContent">
                        <!-- Onglet Informations générales -->
                        <div class="tab-pane fade show active" id="info" role="tabpanel" aria-labelledby="info-tab">
                            <div class="mb-4">
                                <h6 class="border-bottom pb-2 mb-3">Informations du contrat</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Référence:</strong> {{ $contract->title }}</p>
                                        <p><strong>Type de contrat:</strong> {{ $contract->template->name ?? 'Non spécifié' }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Créé le:</strong> {{ $contract->created_at ? $contract->created_at->format('d/m/Y') : 'Non spécifié' }}</p>
                                        @if($contract->submitted_at)
                                            <p><strong>Soumis le:</strong> {{ $contract->submitted_at->format('d/m/Y') }}</p>
                                        @endif
                                        @if($contract->admin_signed_at)
                                            <p><strong>Signé par l'administrateur le:</strong> {{ $contract->admin_signed_at->format('d/m/Y') }}</p>
                                        @endif
                                        @if($contract->employee_signed_at)
                                            <p><strong>Signé par vous le:</strong> {{ $contract->employee_signed_at->format('d/m/Y') }}</p>
                                        @endif
                                        @if($contract->completed_at)
                                            <p><strong>Complété le:</strong> {{ $contract->completed_at->format('d/m/Y') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            @if(Auth::user()->profile_photo_path)
                            <div class="mb-3">
                                <h6 class="border-bottom pb-2 mb-3">Photo de profil</h6>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="text-center">
                                            @php
                                                $photoPath = Auth::user()->profile_photo_path;
                                                // Vérifier si le chemin commence par 'photos/'
                                                if (strpos($photoPath, 'photos/') === 0) {
                                                    $photoUrl = asset($photoPath);
                                                } else {
                                                    $photoUrl = Storage::url($photoPath);
                                                }
                                            @endphp
                                            <img src="{{ $photoUrl }}" alt="Photo de profil" class="img-thumbnail rounded-circle" style="width: 180px; height: 180px; object-fit: cover;"
                                                onerror="this.onerror=null; this.src='{{ asset('img/default-profile.png') }}'; console.error('Image non trouvée: {{ $photoUrl }}');">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @else
                            <div class="mb-3">
                                <h6 class="border-bottom pb-2 mb-3">Photo de profil</h6>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="text-center">
                                            <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white" style="width: 180px; height: 180px; font-size: 4rem; margin: 0 auto;">
                                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif
                            
                            @if($contract->status === 'rejected')
                                <div class="alert alert-danger position-relative">
                                    <button type="button" class="btn-close position-absolute" style="top: 10px; right: 10px; font-size: 0.8rem;" data-bs-dismiss="alert" aria-label="Fermer"></button>
                                    <h6><i class="bi bi-exclamation-triangle-fill"></i> Contrat rejeté</h6>
                                    <p>{{ $contract->admin_notes ?? 'Aucune note fournie par l\'administrateur.' }}</p>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Onglet Informations personnelles -->
                        <div class="tab-pane fade" id="personal" role="tabpanel" aria-labelledby="personal-tab">
                            <div class="mb-4">
                                <h6 class="border-bottom pb-2 mb-3">Informations personnelles</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Prénom:</strong> {{ $contract->data ? $contract->data->first_name : 'Non renseigné' }}</p>
                                        <p><strong>Nom:</strong> {{ $contract->data ? $contract->data->last_name : 'Non renseigné' }}</p>
                                        <p><strong>Date de naissance:</strong> {{ $contract->data && $contract->data->birth_date ? $contract->data->birth_date->format('d/m/Y') : 'Non renseignée' }}</p>
                                        <p><strong>Lieu de naissance:</strong> {{ $contract->data ? $contract->data->birth_place : 'Non renseigné' }}</p>
                                        <p><strong>Nationalité:</strong> {{ $contract->data ? $contract->data->nationality : 'Non renseignée' }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Adresse:</strong> {{ $contract->data ? $contract->data->address : 'Non renseignée' }}</p>
                                        <p><strong>Email:</strong> {{ $contract->data ? $contract->data->email : 'Non renseigné' }}</p>
                                        <p><strong>Téléphone:</strong> {{ $contract->data ? $contract->data->phone : 'Non renseigné' }}</p>
                                        <p><strong>N° Sécurité sociale:</strong> {{ $contract->data ? $contract->data->social_security_number : 'Non renseigné' }}</p>
                                        <p><strong>Coordonnées bancaires:</strong> {{ $contract->data ? $contract->data->bank_details : 'Non renseignées' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Onglet Détails du contrat -->
                        <div class="tab-pane fade" id="contract" role="tabpanel" aria-labelledby="contract-tab">
                            <div class="mb-4">
                                <h6 class="border-bottom pb-2 mb-3">Informations du contrat</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Heures de travail:</strong> {{ $contract->data && $contract->data->work_hours ? $contract->data->work_hours : 'Non renseignées' }}</p>
                                        <p><strong>Taux horaire:</strong> {{ $contract->data && $contract->data->hourly_rate ? $contract->data->hourly_rate . ' €' : 'Non renseigné' }}</p>
                                        <p><strong>Date de début du contrat:</strong> {{ $contract->data && $contract->data->contract_start_date ? $contract->data->contract_start_date->format('d/m/Y') : 'Non renseignée' }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Date de signature du contrat:</strong> {{ $contract->data && $contract->data->contract_signing_date ? $contract->data->contract_signing_date->format('d/m/Y') : 'Non renseignée' }}</p>
                                        <p><strong>Période d'essai (mois):</strong> {{ $contract->data && $contract->data->trial_period_months ? $contract->data->trial_period_months : 'Non renseignée' }}</p>
                                        <p><strong>Date de fin de période d'essai:</strong> {{ $contract->data && $contract->data->trial_period_end_date ? $contract->data->trial_period_end_date->format('d/m/Y') : 'Non calculée' }}</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Heures mensuelles:</strong> {{ $contract->data && $contract->data->monthly_hours ? $contract->data->monthly_hours : 'Non calculées' }}</p>
                                        <p><strong>Salaire brut mensuel:</strong> {{ $contract->data && $contract->data->monthly_gross_salary ? $contract->data->monthly_gross_salary . ' €' : 'Non calculé' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Onglet Prévisualisation du contrat -->
                        <div class="tab-pane fade" id="preview" role="tabpanel" aria-labelledby="preview-tab">
                            <div class="card-body">
                                <h5 class="card-title">Prévisualisation du contrat</h5>
                                
                                <div class="contract-preview-container mt-3 p-3 border rounded bg-white">
                                    @php
                                        // Inclure directement le contenu du contrat
                                        $contractData = $contract->data ?? new stdClass();
                                        $template = $contract->template;
                                        $content = $template ? $template->content : 'Contenu du contrat non disponible';
                                        
                                        // Remplacer les variables dans le contenu du template
                                        $replacements = [
                                            '{{full_name}}' => $contractData->full_name ?? 'Non renseigné',
                                            '{{first_name}}' => $contractData->first_name ?? 'Non renseigné',
                                            '{{last_name}}' => $contractData->last_name ?? 'Non renseigné',
                                            '{{birth_date}}' => isset($contractData->birth_date) ? $contractData->birth_date->format('d/m/Y') : 'Non renseignée',
                                            '{{birth_place}}' => $contractData->birth_place ?? 'Non renseigné',
                                            '{{nationality}}' => $contractData->nationality ?? 'Non renseignée',
                                            '{{address}}' => $contractData->address ?? 'Non renseignée',
                                            '{{email}}' => $contractData->email ?? 'Non renseigné',
                                            '{{phone}}' => $contractData->phone ?? 'Non renseigné',
                                            '{{social_security_number}}' => $contractData->social_security_number ?? 'Non renseigné',
                                            '{{bank_details}}' => $contractData->bank_details ?? 'Non renseignées',
                                            '{{work_hours}}' => $contractData->work_hours ?? 'Non renseignées',
                                            '{{hourly_rate}}' => isset($contractData->hourly_rate) ? $contractData->hourly_rate . ' €' : 'Non renseigné',
                                            '{{contract_start_date}}' => isset($contractData->contract_start_date) ? $contractData->contract_start_date->format('d/m/Y') : 'Non renseignée',
                                            '{{contract_signing_date}}' => isset($contractData->contract_signing_date) ? $contractData->contract_signing_date->format('d/m/Y') : 'Non renseignée',
                                            '{{trial_period_months}}' => $contractData->trial_period_months ?? 'Non renseignée',
                                            '{{monthly_hours}}' => $contractData->monthly_hours ?? 'Non calculées',
                                            '{{monthly_gross_salary}}' => isset($contractData->monthly_gross_salary) ? $contractData->monthly_gross_salary . ' €' : 'Non calculé',
                                            '{{trial_period_end_date}}' => isset($contractData->trial_period_end_date) ? $contractData->trial_period_end_date->format('d/m/Y') : 'Non calculée',
                                        ];
                                        
                                        foreach ($replacements as $key => $value) {
                                            $content = str_replace($key, $value, $content);
                                        }
                                    @endphp
                                    
                                    <div class="contract-content">
                                        {!! nl2br(e($content)) !!}
                                    </div>
                                    
                                    @if($contract->status === 'admin_signed' || $contract->status === 'employee_signed' || $contract->status === 'completed')
                                    <div class="signatures mt-5">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="text-center mb-2">
                                                    <h6 class="fw-bold">Signature de l'employeur</h6>
                                                </div>
                                                <div class="d-flex flex-column align-items-center" style="min-height: 200px;">
                                                    <div class="mb-3">
                                                        <p class="fw-bold text-center">M BRIAND Grégory</p>
                                                    </div>
                                                    <div class="signature-container text-center" style="height: 150px; width: 100%; display: flex; align-items: center; justify-content: center;">
                                                        @if($contract->admin_signed_at && $contract->admin_signature)
                                                            @php
                                                                $adminSignatureFilename = basename($contract->admin_signature);
                                                                $adminSignaturePath = 'signatures/' . $adminSignatureFilename;
                                                                \Log::info('Affichage signature admin', [
                                                                    'filename' => $adminSignatureFilename,
                                                                    'path' => $adminSignaturePath,
                                                                    'exists' => \Storage::exists('public/' . $adminSignaturePath)
                                                                ]);
                                                            @endphp
                                                            <img src="{{ route('signature.admin', ['filename' => 'admin_signature.png']) }}" 
                                                                 alt="Signature de l'employeur" 
                                                                 class="img-fluid" 
                                                                 style="max-height: 150px;">
                                                        @else
                                                            <p class="text-muted">Non signé</p>
                                                            @if(!$contract->admin_signed_at)
                                                                <small class="text-danger">L'administrateur n'a pas encore signé</small>
                                                            @endif
                                                        @endif
                                                    </div>
                                                    @if($contract->admin_signed_at)
                                                        <p class="small text-muted mt-2 text-center">Signé le {{ $contract->admin_signed_at ? $contract->admin_signed_at->format('d/m/Y') : 'Date inconnue' }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="text-center mb-2">
                                                    <h6 class="fw-bold">Signature de l'employé</h6>
                                                </div>
                                                <div class="d-flex flex-column align-items-center" style="min-height: 200px;">
                                                    <div class="mb-3">
                                                        <p class="fw-bold text-center">
                                                            {{ $contract->data && $contract->data->full_name ? $contract->data->full_name : ($contract->user ? $contract->user->name : 'Employé') }}
                                                        </p>
                                                    </div>
                                                    <div class="signature-container text-center" style="height: 150px; width: 100%; display: flex; align-items: center; justify-content: center;">
                                                        @if($contract->employee_signature)
                                                            @php
                                                                $employeeSignatureFilename = basename($contract->employee_signature);
                                                            @endphp
                                                            <img src="{{ route('signature', ['filename' => $employeeSignatureFilename]) }}" 
                                                                 alt="Signature de l'employé" 
                                                                 class="img-fluid" 
                                                                 style="max-height: 150px;">
                                                        @else
                                                            <p class="text-muted">Non signé</p>
                                                        @endif
                                                    </div>
                                                    @if($contract->employee_signed_at)
                                                        <p class="small text-muted mt-2 text-center">Signé le {{ $contract->employee_signed_at ? $contract->employee_signed_at->format('d/m/Y') : 'Date inconnue' }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                                
                                <div class="mt-3">
                                    <a href="{{ route('employee.contracts.preview', $contract) }}" class="btn btn-outline-primary" onclick="window.open(this.href, '_blank'); return false;">
                                        <i class="bi bi-eye"></i> Prévisualiser en PDF
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section des avenants -->
                    @if($contract->avenants && $contract->avenants->count() > 0)
                    <div class="mt-5">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="border-bottom pb-2 mb-0">Avenants à votre contrat</h6>
                            <a href="{{ route('employee.contracts.avenants', $contract) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-clock-history"></i> Voir l'historique complet
                            </a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>N° Avenant</th>
                                        <th>Date de création</th>
                                        <th>Modifications</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($contract->avenants as $avenant)
                                    <tr>
                                        <td>{{ $avenant->avenant_number }}</td>
                                        <td>{{ $avenant->created_at->format('d/m/Y') }}</td>
                                        <td>
                                            @if($avenant->data)
                                                <ul class="mb-0 ps-3">
                                                    <li>Horaire: {{ $avenant->data->work_hours }} h/semaine</li>
                                                    <li>Salaire: {{ $avenant->data->monthly_gross_salary }} €/mois</li>
                                                </ul>
                                            @endif
                                        </td>
                                        <td>
                                            @if($avenant->status == 'draft')
                                                <span class="badge bg-secondary">Brouillon</span>
                                            @elseif($avenant->status == 'submitted')
                                                <span class="badge bg-primary">Soumis</span>
                                            @elseif($avenant->status == 'admin_signed')
                                                <span class="badge bg-info">À signer</span>
                                            @elseif($avenant->status == 'employee_signed')
                                                <span class="badge bg-success">Signé</span>
                                            @elseif($avenant->status == 'completed')
                                                <span class="badge bg-success">Complété</span>
                                            @elseif($avenant->status == 'rejected')
                                                <span class="badge bg-danger">Rejeté</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('employee.contracts.show', $avenant) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i> Voir
                                                </a>
                                                @if($avenant->status == 'admin_signed')
                                                    <a href="{{ route('employee.contracts.preview', $avenant) }}" class="btn btn-sm btn-outline-secondary" target="_blank">
                                                        <i class="bi bi-file-earmark-pdf"></i> Prévisualiser
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#signAvenantModal{{ $avenant->id }}">
                                                        <i class="bi bi-pen"></i> Signer
                                                    </button>
                                                    
                                                    <!-- Modal de signature pour cet avenant -->
                                                    <div class="modal fade" id="signAvenantModal{{ $avenant->id }}" tabindex="-1" aria-labelledby="signAvenantModalLabel{{ $avenant->id }}" aria-hidden="true">
                                                        <div class="modal-dialog modal-lg">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="signAvenantModalLabel{{ $avenant->id }}">Signer l'avenant n°{{ $avenant->avenant_number }}</h5>
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
                                                                    
                                                                    <form action="{{ route('employee.contracts.sign', $avenant) }}" method="POST" id="signatureForm{{ $avenant->id }}">
                                                                        @csrf
                                                                        <div class="mb-3">
                                                                            <label class="form-label">Votre signature :</label>
                                                                            <div id="signatureCanvas{{ $avenant->id }}" class="border rounded" style="width: 100%; height: 200px;"></div>
                                                                            <input type="hidden" name="employee_signature" id="signatureInput{{ $avenant->id }}">
                                                                            <div class="mt-2">
                                                                                <button type="button" class="btn btn-outline-secondary btn-sm" id="clearSignature{{ $avenant->id }}">
                                                                                    <i class="bi bi-eraser"></i> Effacer
                                                                                </button>
                                                                            </div>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                                    <button type="button" class="btn btn-success" id="submitSignature{{ $avenant->id }}">
                                                                        <i class="bi bi-pen"></i> Signer l'avenant
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                                
                                                @if($avenant->status == 'completed' || $avenant->status == 'employee_signed')
                                                    <a href="{{ route('employee.contracts.download', $avenant) }}" class="btn btn-sm btn-outline-success">
                                                        <i class="bi bi-download"></i> Télécharger
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                </div>

                <!-- Boutons d'action -->
                <div class="card-footer d-flex justify-content-between bg-white pt-0 border-top-0">
                    <a href="{{ route('employee.contracts.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Retour
                        </a>
                        <div>
                            @if($contract->status === 'draft')
                                <a href="{{ route('employee.contracts.edit', $contract) }}" class="btn btn-primary">
                                    <i class="bi bi-pencil"></i> Modifier
                                </a>
                                <a href="{{ route('employee.contracts.submit', $contract) }}" 
                                   onclick="event.preventDefault(); if(confirm('Êtes-vous sûr de vouloir soumettre ce contrat ? Vous ne pourrez plus le modifier après soumission.')) document.getElementById('submit-form').submit();" 
                                   class="btn btn-success ms-2">
                                    <i class="bi bi-check-circle"></i> Soumettre pour validation
                                </a>
                                <form id="submit-form" action="{{ route('employee.contracts.submit', $contract) }}" method="POST" class="d-none">
                                    @csrf
                                </form>
                            @endif
                            
                            @if($contract->status === 'admin_signed')
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#signModal">
                                    <i class="bi bi-pen"></i> Signer le contrat
                                </button>
                            @endif
                            
                            @if($contract->status === 'completed')
                                <a href="{{ route('employee.contracts.download', $contract) }}" class="btn btn-success">
                                    <i class="bi bi-download"></i> Télécharger le contrat
                                </a>
                            @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de signature avec zone de dessin -->
@if($contract->status === 'admin_signed')
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
                        <canvas id="signature-pad" class="signature-pad" width="600" height="200" style="touch-action: none; border: 1px solid #ddd; background-color: white;"></canvas>
                        <div class="signature-pad-actions">
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
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Vérifier si nous sommes sur une page avec un pad de signature principal
        if (document.getElementById('signature-pad')) {
        let signaturePad = null;
        
        // Initialiser le pad de signature quand la modal est affichée
        $('#signModal').on('shown.bs.modal', function () {
            const canvas = document.getElementById('signature-pad');
            
            // Réinitialiser le canvas
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            // Créer une nouvelle instance de SignaturePad avec des paramètres améliorés
            signaturePad = new SignaturePad(canvas, {
                backgroundColor: 'rgb(255, 255, 255)',
                penColor: 'rgb(0, 0, 0)',
                minWidth: 1.5,
                maxWidth: 3.5,
                throttle: 16, // Plus fluide
                velocityFilterWeight: 0.5 // Pour des lignes plus naturelles
            });
            
            // Redimensionner le canvas pour qu'il corresponde à la taille du conteneur
            function resizeCanvas() {
                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                const width = canvas.offsetWidth;
                const height = canvas.offsetHeight;
                
                // Si le canvas est déjà à la bonne taille, ne rien faire
                if (canvas.width === width * ratio && canvas.height === height * ratio) {
                    return;
                }
                
                canvas.width = width * ratio;
                canvas.height = height * ratio;
                canvas.getContext("2d").scale(ratio, ratio);
                
                // Effacer le contenu après redimensionnement
                if (signaturePad) {
                    signaturePad.clear();
                }
            }
            
            // Appliquer le redimensionnement initial
            setTimeout(resizeCanvas, 300);
            
            // Redimensionner lors du changement de taille de la fenêtre
            window.addEventListener("resize", resizeCanvas);
            
            // Bouton pour effacer la signature
            document.getElementById('clear-button').addEventListener('click', function() {
                if (signaturePad) {
                    signaturePad.clear();
                }
            });
        });
        
        // Formulaire de soumission principal
        document.getElementById('signatureForm').addEventListener('submit', function(e) {
            e.preventDefault(); // Empêcher la soumission par défaut
            
            if (!signaturePad || signaturePad.isEmpty()) {
                alert('Veuillez dessiner votre signature avant de soumettre.');
                return false;
            }
            
            // Vérifier que la case de confirmation est cochée
            const confirmCheckbox = document.getElementById('signature-confirm');
            if (!confirmCheckbox.checked) {
                alert('Veuillez confirmer que vous avez lu et accepté les conditions du contrat.');
                return false;
            }
            
            try {
                // Ajouter un indicateur de chargement
                const submitBtn = document.getElementById('submit-signature');
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enregistrement...';
                submitBtn.disabled = true;
                
                // Ajouter une confirmation avant l'envoi
                if (confirm('Êtes-vous sûr de vouloir signer ce contrat ? Cette action est définitive.')) {
                    // Capturer l'image de la signature avec la meilleure qualité possible
                    const signatureData = signaturePad.toDataURL('image/png', 1.0);
                    
                    // Vérifier que la signature n'est pas trop petite
                    if (signatureData.length < 1000) {
                        alert('La signature semble trop petite ou vide. Veuillez signer à nouveau.');
                        submitBtn.innerHTML = 'Signer';
                        submitBtn.disabled = false;
                        return false;
                    }
                    
                    console.log('Signature capturée avec succès, longueur des données:', signatureData.length);
                    
                    // Définir la valeur du champ caché
                    document.getElementById('signature-data').value = signatureData;
                    
                    // Débug visuel - afficher la signature dans la console (optionnel)
                    const img = new Image();
                    img.src = signatureData;
                    console.log('Aperçu de la signature:', img);
                    
                    // Soumettre le formulaire
                    this.submit();
                } else {
                    // L'utilisateur a annulé
                    submitBtn.innerHTML = 'Signer';
                    submitBtn.disabled = false;
                }
            } catch (error) {
                console.error('Erreur lors de la capture de la signature:', error);
                alert('Une erreur est survenue lors de la capture de votre signature. Veuillez réessayer.');
                const submitBtn = document.getElementById('submit-signature');
                submitBtn.innerHTML = 'Signer';
                submitBtn.disabled = false;
            }
        });
        }

        // Initialiser les canvas de signature pour les avenants
        @if($contract->avenants && $contract->avenants->count() > 0)
            @foreach($contract->avenants as $avenant)
                @if($avenant->status == 'admin_signed')
                    // Initialiser le pad de signature pour l'avenant {{ $avenant->id }}
                    const signatureCanvas{{ $avenant->id }} = document.getElementById('signatureCanvas{{ $avenant->id }}');
                    if (signatureCanvas{{ $avenant->id }}) {
                        const signaturePad{{ $avenant->id }} = new SignaturePad(signatureCanvas{{ $avenant->id }}, {
                            backgroundColor: 'rgb(255, 255, 255)',
                            penColor: 'rgb(0, 0, 0)'
                        });
                        
                        // Bouton pour effacer la signature
                        document.getElementById('clearSignature{{ $avenant->id }}').addEventListener('click', function() {
                            signaturePad{{ $avenant->id }}.clear();
                        });
                        
                        // Soumettre la signature
                        document.getElementById('submitSignature{{ $avenant->id }}').addEventListener('click', function() {
                            if (signaturePad{{ $avenant->id }}.isEmpty()) {
                                alert('Veuillez signer le document avant de continuer.');
                                return;
                            }
                            
                            // Récupérer la signature sous forme d'image base64
                            const signatureData = signaturePad{{ $avenant->id }}.toDataURL();
                            document.getElementById('signatureInput{{ $avenant->id }}').value = signatureData;
                            
                            // Soumettre le formulaire
                            document.getElementById('signatureForm{{ $avenant->id }}').submit();
                        });
                        
                        // Ajuster la taille du canvas en fonction de la taille de son conteneur
                        function resizeCanvas{{ $avenant->id }}() {
                            const ratio = Math.max(window.devicePixelRatio || 1, 1);
                            signatureCanvas{{ $avenant->id }}.width = signatureCanvas{{ $avenant->id }}.offsetWidth * ratio;
                            signatureCanvas{{ $avenant->id }}.height = signatureCanvas{{ $avenant->id }}.offsetHeight * ratio;
                            signatureCanvas{{ $avenant->id }}.getContext("2d").scale(ratio, ratio);
                            signaturePad{{ $avenant->id }}.clear(); // Nécessaire après le redimensionnement
                        }
                        
                        // Redimensionner au chargement
                        resizeCanvas{{ $avenant->id }}();
                        
                        // Redimensionner lorsque la fenêtre change de taille
                        window.addEventListener('resize', resizeCanvas{{ $avenant->id }});
                        
                        // Redimensionner lorsque le modal est affiché
                        document.getElementById('signAvenantModal{{ $avenant->id }}').addEventListener('shown.bs.modal', function() {
                            resizeCanvas{{ $avenant->id }}();
                        });
                    }
                @endif
            @endforeach
        @endif
    });
</script>
@endpush
@endif
@endsection 