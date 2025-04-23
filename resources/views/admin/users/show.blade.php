@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">Détails de l'utilisateur</h5>
                        <span class="badge {{ $user->is_admin ? 'bg-primary' : 'bg-secondary' }}">
                            {{ $user->is_admin ? 'Administrateur' : 'Employé' }}
                        </span>
                    </div>
                    @if($user->profile_photo_path)
                    <div>
                        <img src="{{ asset('storage/' . $user->profile_photo_path) }}" alt="Photo de profil" class="img-thumbnail" style="max-height: 120px;">
                    </div>
                    @endif
                </div>
                <div class="card-body">
                    @if (session('status'))
                    <div class="alert alert-success position-relative">
                        <button type="button" class="btn-close position-absolute" style="top: 10px; right: 10px; font-size: 0.8rem;" data-bs-dismiss="alert" aria-label="Fermer"></button>
                        {{ session('status') }}
                    </div>
                    @endif
                    
                    @if ($user->is_admin)
                    <div class="alert alert-info position-relative">
                        <button type="button" class="btn-close position-absolute" style="top: 10px; right: 10px; font-size: 0.8rem;" data-bs-dismiss="alert" aria-label="Fermer"></button>
                        <i class="bi bi-shield-check"></i> Cet utilisateur a des droits d'administrateur.
                    </div>
                    @endif
                    
                    <div class="mb-4">
                        <h6 class="border-bottom pb-2 mb-3">Informations de l'utilisateur</h6>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <div class="row">
                                    <div class="col-md-4 fw-bold">Nom :</div>
                                    <div class="col-md-8">{{ $user->name }}</div>
                                </div>
                            </li>
                            <li class="list-group-item">
                                <div class="row">
                                    <div class="col-md-4 fw-bold">Email :</div>
                                    <div class="col-md-8">{{ $user->email }}</div>
                                </div>
                            </li>
                            <li class="list-group-item">
                                <div class="row">
                                    <div class="col-md-4 fw-bold">Date d'inscription :</div>
                                    <div class="col-md-8">{{ $user->created_at ? $user->created_at->format('d/m/Y') : 'Non spécifiée' }}</div>
                                </div>
                            </li>
                        </ul>
                    </div>
                    
                    @if($user->contracts->isNotEmpty() && $user->contracts->first()->data)
                    <div class="mb-4">
                        <h6 class="border-bottom pb-2 mb-3">Informations de contact</h6>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <div class="row">
                                    <div class="col-md-4 fw-bold">Prénom :</div>
                                    <div class="col-md-8">{{ $user->contracts->first()->data->first_name ?? 'Non renseigné' }}</div>
                                </div>
                            </li>
                            <li class="list-group-item">
                                <div class="row">
                                    <div class="col-md-4 fw-bold">Nom :</div>
                                    <div class="col-md-8">{{ $user->contracts->first()->data->last_name ?? 'Non renseigné' }}</div>
                                </div>
                            </li>
                            <li class="list-group-item">
                                <div class="row">
                                    <div class="col-md-4 fw-bold">Téléphone :</div>
                                    <div class="col-md-8">{{ $user->contracts->first()->data->phone ?? 'Non renseigné' }}</div>
                                </div>
                            </li>
                            <li class="list-group-item">
                                <div class="row">
                                    <div class="col-md-4 fw-bold">Adresse :</div>
                                    <div class="col-md-8">{{ $user->contracts->first()->data->address ?? 'Non renseignée' }}</div>
                                </div>
                            </li>
                            <li class="list-group-item">
                                <div class="row">
                                    <div class="col-md-4 fw-bold">Code postal :</div>
                                    <div class="col-md-8">{{ $user->contracts->first()->data->postal_code ?? 'Non renseigné' }}</div>
                                </div>
                            </li>
                            <li class="list-group-item">
                                <div class="row">
                                    <div class="col-md-4 fw-bold">Ville :</div>
                                    <div class="col-md-8">{{ $user->contracts->first()->data->city ?? 'Non renseignée' }}</div>
                                </div>
                            </li>
                            <li class="list-group-item">
                                <div class="row">
                                    <div class="col-md-4 fw-bold">Ville de naissance :</div>
                                    <div class="col-md-8">{{ $user->contracts->first()->data->birth_place ?? 'Non renseignée' }}</div>
                                </div>
                            </li>
                            <li class="list-group-item">
                                <div class="row">
                                    <div class="col-md-4 fw-bold">Date de naissance :</div>
                                    <div class="col-md-8">{{ $user->contracts->first()->data->birth_date ? $user->contracts->first()->data->birth_date->format('d/m/Y') : 'Non renseignée' }}</div>
                                </div>
                            </li>
                            <li class="list-group-item">
                                <div class="row">
                                    <div class="col-md-4 fw-bold">Nationalité :</div>
                                    <div class="col-md-8">{{ $user->contracts->first()->data->nationality ?? 'Non renseignée' }}</div>
                                </div>
                            </li>
                            <li class="list-group-item">
                                <div class="row">
                                    <div class="col-md-4 fw-bold">N° de sécurité sociale :</div>
                                    <div class="col-md-8">{{ $user->contracts->first()->data->social_security_number ?? 'Non renseigné' }}</div>
                                </div>
                            </li>
                        </ul>
                    </div>
                    @endif
                    
                    @if($user->contract)
                        <div class="mb-4">
                            <h6 class="border-bottom pb-2 mb-3">Contrat associé</h6>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">
                                    <div class="row">
                                        <div class="col-md-4 fw-bold">Référence :</div>
                                        <div class="col-md-8">{{ $user->contract->title }}</div>
                                    </div>
                                </li>
                                <li class="list-group-item">
                                    <div class="row">
                                        <div class="col-md-4 fw-bold">Type :</div>
                                        <div class="col-md-8">{{ $user->contract->template->name ?? 'Non spécifié' }}</div>
                                    </div>
                                </li>
                                <li class="list-group-item">
                                    <div class="row">
                                        <div class="col-md-4 fw-bold">Statut :</div>
                                        <div class="col-md-8">
                                            @if($user->contract->status == 'draft')
                                                <span class="badge bg-secondary">Brouillon</span>
                                            @elseif($user->contract->status == 'submitted')
                                                <span class="badge bg-primary">Soumis</span>
                                            @elseif($user->contract->status == 'in_review')
                                                <span class="badge bg-warning">En révision</span>
                                            @elseif($user->contract->status == 'admin_signed')
                                                <span class="badge bg-info">À signer</span>
                                            @elseif($user->contract->status == 'employee_signed')
                                                <span class="badge bg-success">Signé</span>
                                            @elseif($user->contract->status == 'completed')
                                                <span class="badge bg-success">Complété</span>
                                            @elseif($user->contract->status == 'rejected')
                                                <span class="badge bg-danger">Rejeté</span>
                                            @endif
                                        </div>
                                    </div>
                                </li>
                                <li class="list-group-item">
                                    <div class="row">
                                        <div class="col-md-4 fw-bold">Date de création :</div>
                                        <div class="col-md-8">{{ $user->contract->created_at ? $user->contract->created_at->format('d/m/Y') : 'Non spécifiée' }}</div>
                                    </div>
                                </li>
                            </ul>
                            <div class="mt-3">
                                <a href="{{ route('admin.contracts.show', $user->contract) }}" class="btn btn-outline-primary">
                                    <i class="bi bi-eye"></i> Voir le contrat
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-info">
                            Cet utilisateur n'a pas encore de contrat.
                        </div>
                    @endif
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Retour à la liste
                        </a>
                        <div>
                            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary">
                                <i class="bi bi-pencil"></i> Modifier
                            </a>
                            @if($user->id !== auth()->id())
                                <button type="button" class="btn btn-danger ms-2" 
                                        onclick="event.preventDefault(); 
                                        if(confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.')) 
                                        document.getElementById('delete-form').submit();">
                                    <i class="bi bi-trash"></i> Supprimer
                                </button>
                                <form id="delete-form" action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-none">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 