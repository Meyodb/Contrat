@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Détails du modèle de contrat</h5>
                    <div>
                        <a href="{{ route('admin.templates.edit', $template) }}" class="btn btn-sm btn-primary me-2">
                            <i class="bi bi-pencil"></i> Modifier
                        </a>
                        <a href="{{ route('admin.templates.index') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Retour
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if (session('status'))
                    <div class="alert alert-success position-relative">
                        <button type="button" class="btn-close position-absolute" style="top: 10px; right: 10px; font-size: 0.8rem;" data-bs-dismiss="alert" aria-label="Fermer"></button>
                        {{ session('status') }}
                    </div>
                    @endif
                    
                    <div class="mb-4">
                        <h6 class="fw-bold">Nom</h6>
                        <p>{{ $template->name }}</p>
                    </div>
                    
                    <div class="mb-4">
                        <h6 class="fw-bold">Description</h6>
                        <p>{{ $template->description ?: 'Aucune description' }}</p>
                    </div>
                    
                    <div class="mb-4">
                        <h6 class="fw-bold">Fichier</h6>
                        @if($template->file_path)
                            <a href="{{ route('admin.templates.download', $template) }}" class="btn btn-outline-primary">
                                <i class="bi bi-download"></i> Télécharger le modèle
                            </a>
                        @else
                            <p class="text-muted">Aucun fichier associé à ce modèle.</p>
                        @endif
                    </div>
                    
                    <div class="mb-4">
                        <h6 class="fw-bold">Informations</h6>
                        <ul class="list-group">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Date de création
                                <span>{{ $template->created_at ? $template->created_at->format('d/m/Y H:i') : 'Non spécifiée' }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Dernière modification
                                <span>{{ $template->updated_at ? $template->updated_at->format('d/m/Y H:i') : 'Non spécifiée' }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Nombre de contrats utilisant ce modèle
                                <span class="badge bg-primary rounded-pill">{{ $template->contracts()->count() }}</span>
                            </li>
                        </ul>
                    </div>
                    
                    @if($template->contracts()->count() > 0)
                    <div class="mb-4">
                        <h6 class="fw-bold">Contrats utilisant ce modèle</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Titre</th>
                                        <th>Employé</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($template->contracts()->limit(5)->get() as $contract)
                                    <tr>
                                        <td>{{ $contract->id }}</td>
                                        <td>{{ $contract->title }}</td>
                                        <td>{{ $contract->user->name }}</td>
                                        <td><span class="badge bg-{{ $contract->status == 'signed' ? 'success' : ($contract->status == 'pending' ? 'warning' : 'secondary') }}">{{ $contract->status }}</span></td>
                                        <td>
                                            <a href="{{ route('admin.contracts.show', $contract) }}" class="btn btn-sm btn-outline-info">Voir</a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if($template->contracts()->count() > 5)
                            <div class="text-center mt-2">
                                <span class="text-muted">{{ $template->contracts()->count() - 5 }} contrats supplémentaires utilisent ce modèle</span>
                            </div>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 