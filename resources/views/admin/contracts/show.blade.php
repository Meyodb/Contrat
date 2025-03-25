@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Détails du contrat</h5>
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
                        <div class="alert alert-success position-relative">
                            <button type="button" class="btn-close position-absolute" style="top: 10px; right: 10px; font-size: 0.8rem;" data-bs-dismiss="alert" aria-label="Fermer"></button>
                            {{ session('status') }}
                        </div>
                    @endif
                    
                    @if($contract->status === 'rejected')
                        <div class="alert alert-danger position-relative">
                            <button type="button" class="btn-close position-absolute" style="top: 10px; right: 10px; font-size: 0.8rem;" data-bs-dismiss="alert" aria-label="Fermer"></button>
                            <h5><i class="bi bi-exclamation-triangle-fill"></i> Contrat rejeté</h5>
                            <p>{{ $contract->admin_notes ?? 'Aucune note fournie.' }}</p>
                        </div>
                    @endif
                    
                    <div class="mb-4">
                        <h6 class="border-bottom pb-2 mb-3">Informations du contrat</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Référence:</strong> {{ $contract->title }}</p>
                                <p><strong>Type de contrat:</strong> {{ $contract->template->name ?? 'Non spécifié' }}</p>
                                <p><strong>Employé:</strong> {{ $contract->user->name }}</p>
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
                                    <p><strong>Signé par l'employé le:</strong> {{ $contract->employee_signed_at->format('d/m/Y') }}</p>
                                @endif
                                @if($contract->completed_at)
                                    <p><strong>Complété le:</strong> {{ $contract->completed_at->format('d/m/Y') }}</p>
                                @endif
                            </div>
                        </div>
                    
                    <div class="mb-4">
                        <h6 class="border-bottom pb-2 mb-3">Informations personnelles</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Prénom:</strong> {{ $contract->data ? $contract->data->first_name : 'Non renseigné' }}</p>
                                <p><strong>Nom:</strong> {{ $contract->data ? $contract->data->last_name : 'Non renseigné' }}</p>
                                <p><strong>Civilité:</strong> {{ $contract->data && $contract->data->gender ? ($contract->data->gender == 'M' ? 'Monsieur' : 'Madame') : 'Non renseignée' }}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Date de naissance:</strong> {{ $contract->data && $contract->data->birth_date ? $contract->data->birth_date->format('d/m/Y') : 'Non renseignée' }}</p>
                                <p><strong>Lieu de naissance:</strong> {{ $contract->data ? $contract->data->birth_place : 'Non renseigné' }}</p>
                                <p><strong>Nationalité:</strong> {{ $contract->data ? $contract->data->nationality : 'Non renseignée' }}</p>
                                <p><strong>N° de sécurité sociale:</strong> {{ $contract->data ? $contract->data->social_security_number : 'Non renseigné' }}</p>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <p><strong>Adresse:</strong> {{ $contract->data ? $contract->data->address : 'Non renseignée' }}</p>
                                <p><strong>Email:</strong> {{ $contract->data ? $contract->data->email : 'Non renseigné' }}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Téléphone:</strong> {{ $contract->data ? $contract->data->phone : 'Non renseigné' }}</p>
                                <p>
                                    <strong>Coordonnées bancaires:</strong> 
                                    @if($contract->data && $contract->data->bank_details)
                                        <span>{{ $contract->data->bank_details }}</span>
                                        <form action="{{ route('admin.contracts.delete-bank-details', $contract) }}" method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer définitivement les coordonnées bancaires ? Cette action est irréversible.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger ms-2">
                                                <i class="bi bi-trash"></i> Supprimer
                                            </button>
                                        </form>
                                    @else
                                        <span>Non renseignées</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h6 class="border-bottom pb-2 mb-3">Informations du contrat (admin)</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Heures de travail (hebdomadaire):</strong> {{ $contract->data && $contract->data->work_hours ? $contract->data->work_hours : 'Non renseignées' }}</p>
                                <p><strong>Taux horaire:</strong> {{ $contract->data && $contract->data->hourly_rate ? $contract->data->hourly_rate . ' €' : 'Non renseigné' }}</p>
                                <p><strong>Date de début du contrat:</strong> {{ $contract->data && $contract->data->contract_start_date ? $contract->data->contract_start_date->format('d/m/Y') : 'Non renseignée' }}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Date de signature du contrat:</strong> {{ $contract->data && $contract->data->contract_signing_date ? $contract->data->contract_signing_date->format('d/m/Y') : 'Non renseignée' }}</p>
                                <p><strong>Période d'essai (mois):</strong> {{ $contract->data && $contract->data->trial_period_months ? $contract->data->trial_period_months : 'Non renseignée' }}</p>
                            </div>
                        </div>
                    </div>
                    
                    @if($contract->data && $contract->data->photo_path)
                    <div class="mb-3">
                        <label class="form-label fw-bold">Photo d'identité</label>
                        <div>
                            @php
                                $photoFilename = basename($contract->data->photo_path);
                            @endphp
                            <img src="{{ route('employee.photo', ['filename' => $photoFilename]) }}" alt="Photo d'identité" class="img-thumbnail" style="max-height: 200px;">
                        </div>
                    </div>
                    @endif
                    
                    <div class="d-flex justify-content-between">
                        <a href="{{ url()->previous() == url()->current() ? route('admin.dashboard') : url()->previous() }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Retour
                        </a>
                        <div>
                            @if(in_array($contract->status, ['in_review', 'submitted']))
                                <a href="{{ route('admin.contracts.edit', $contract) }}" class="btn btn-primary">
                                    <i class="bi bi-pencil"></i> Modifier les informations
                                </a>
                                @if($contract->status == 'submitted')
                                    <a href="{{ route('admin.contracts.preview', $contract) }}" class="btn btn-secondary" target="_blank">
                                        <i class="bi bi-eye"></i> Prévisualiser le contrat
                                    </a>
                                    <form action="{{ route('admin.contracts.sign', $contract) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-success">
                                            <i class="bi bi-check-circle"></i> Approuver et signer
                                        </button>
                                    </form>
                                @endif
                            @endif
                            
                            @if($contract->status == 'completed' || $contract->status == 'employee_signed')
                                <a href="{{ route('admin.contracts.download', $contract) }}" class="btn btn-primary ms-2">
                                    <i class="bi bi-download"></i> Télécharger
                                </a>
                            @endif
                            
                            <button type="button" class="btn btn-danger ms-2" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                <i class="bi bi-trash"></i> Supprimer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirmation de suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill"></i> Attention
                </div>
                <p>Êtes-vous sûr de vouloir supprimer ce contrat ?</p>
                <p><strong>Cette action est irréversible.</strong> Toutes les données associées à ce contrat seront définitivement supprimées.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form action="{{ route('admin.contracts.destroy', $contract) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Supprimer définitivement</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection 