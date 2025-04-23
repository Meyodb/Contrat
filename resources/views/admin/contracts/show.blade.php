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
                                <p><strong>Employé:</strong> {{ $contract->user ? $contract->user->name : 'Utilisateur supprimé' }}</p>
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
                    
                    @if($contract->user && $contract->user->profile_photo_path)
                    <div class="mb-3">
                        <label class="form-label fw-bold">Photo de profil</label>
                        <div class="text-center">
                            @php
                                $photoPath = $contract->user->profile_photo_path;
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
                    @else
                    <div class="mb-3">
                        <label class="form-label fw-bold">Photo de profil</label>
                        <div class="text-center">
                            <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white" style="width: 180px; height: 180px; font-size: 4rem; margin: 0 auto;">
                                {{ strtoupper(substr($contract->user ? $contract->user->name : 'U', 0, 1)) }}
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    @if($contract->status === 'admin_signed' || $contract->status === 'employee_signed' || $contract->status === 'completed')
                    <div class="mb-4">
                        <h6 class="border-bottom pb-2 mb-3">Signatures</h6>
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
                                        @if($contract->admin_signed_at && ($contract->admin_signature || $contract->status === 'completed'))
                                            @php
                                                $adminSignatureFilename = basename($contract->admin_signature ?? 'admin_signature.png');
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
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="{{ route('admin.contracts.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Retour à la liste
                        </a>
                        <div>
                            @if($contract->status === 'submitted')
                                <a href="{{ route('admin.contracts.edit', $contract) }}" class="btn btn-primary me-2">
                                    <i class="bi bi-pencil"></i> Modifier
                                </a>
                                <a href="{{ route('admin.contracts.sign', $contract) }}" class="btn btn-success me-2" onclick="return confirm('Voulez-vous signer ce contrat avec la signature administrative enregistrée?');">
                                    <i class="bi bi-pen"></i> Signer automatiquement
                                </a>
                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                    <i class="bi bi-x-circle"></i> Rejeter
                                </button>
                            @endif
                            
                            <a href="{{ route('admin.contracts.preview', $contract) }}" class="btn btn-secondary me-2" target="_blank">
                                <i class="bi bi-eye"></i> Prévisualiser
                            </a>
                            
                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                <i class="bi bi-trash"></i> Supprimer
                            </button>
                        </div>
                    </div>
                    
                    @if($contract->avenants && $contract->avenants->count() > 0)
                    <div class="mt-5">
                        <h6 class="border-bottom pb-2 mb-3">Avenants au contrat</h6>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>N° Avenant</th>
                                        <th>Date de création</th>
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
                                            @if($avenant->status == 'draft')
                                                <span class="badge bg-secondary">Brouillon</span>
                                            @elseif($avenant->status == 'submitted')
                                                <span class="badge bg-primary">Soumis</span>
                                            @elseif($avenant->status == 'admin_signed')
                                                <span class="badge bg-info">Signé admin</span>
                                            @elseif($avenant->status == 'employee_signed')
                                                <span class="badge bg-success">Signé employé</span>
                                            @elseif($avenant->status == 'completed')
                                                <span class="badge bg-success">Complété</span>
                                            @elseif($avenant->status == 'rejected')
                                                <span class="badge bg-danger">Rejeté</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.contracts.show', $avenant) }}" class="btn btn-sm btn-primary">
                                                <i class="bi bi-eye"></i> Voir
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif
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

<!-- Modal de rejet -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rejectModalLabel">Rejeter le contrat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.contracts.reject', $contract) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="admin_notes" class="form-label">Motif du rejet</label>
                        <textarea class="form-control" id="admin_notes" name="admin_notes" rows="4" required placeholder="Veuillez expliquer la raison du rejet..."></textarea>
                        <div class="form-text">Ces informations seront communiquées à l'employé.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger">Rejeter le contrat</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection 