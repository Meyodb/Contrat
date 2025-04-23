@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Gestion des contrats</h1>
            </div>
            
            @if(session('success'))
                <div class="alert alert-success position-relative">
                    <button type="button" class="btn-close position-absolute" style="top: 10px; right: 10px; font-size: 0.8rem;" data-bs-dismiss="alert" aria-label="Fermer"></button>
                    {{ session('success') }}
                </div>
            @endif
            
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Liste des contrats</h5>
                        <div class="d-flex gap-2">
                            <a href="{{ route('admin.contracts.create') }}" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Créer un contrat
                            </a>
                            <form action="{{ route('admin.contracts.index') }}" method="GET" class="d-flex">
                                <select name="status" class="form-select me-2" onchange="this.form.submit()">
                                    <option value="">Tous les statuts</option>
                                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Brouillon</option>
                                    <option value="submitted" {{ request('status') == 'submitted' ? 'selected' : '' }}>Soumis</option>
                                    <option value="in_review" {{ request('status') == 'in_review' ? 'selected' : '' }}>En révision</option>
                                    <option value="admin_signed" {{ request('status') == 'admin_signed' ? 'selected' : '' }}>Signé admin</option>
                                    <option value="employee_signed" {{ request('status') == 'employee_signed' ? 'selected' : '' }}>Signé employé</option>
                                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Complété</option>
                                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejeté</option>
                                </select>
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control" placeholder="Rechercher..." value="{{ request('search') }}">
                                    <button class="btn btn-outline-secondary" type="submit">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if($contracts->isEmpty())
                        <div class="alert alert-info position-relative">
                            <button type="button" class="btn-close position-absolute" style="top: 10px; right: 10px; font-size: 0.8rem;" data-bs-dismiss="alert" aria-label="Fermer"></button>
                            <h5>Aucun contrat trouvé</h5>
                            <p>Il n'y a pas encore de contrats dans le système.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th style="width: 5%">ID</th>
                                        <th style="width: 20%">Employé</th>
                                        <th style="width: 15%">Statut</th>
                                        <th style="width: 15%">Heures par mois</th>
                                        <th style="width: 15%">Date de soumission</th>
                                        <th style="width: 10%" class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($contracts as $contract)
                                        <tr>
                                            <td>{{ $contract->id }}</td>
                                            <td>{{ $contract->user ? $contract->user->name : 'Utilisateur supprimé' }}</td>
                                            <td>
                                                @if($contract->status == 'draft')
                                                    <span class="badge bg-secondary">Brouillon</span>
                                                @elseif($contract->status == 'submitted')
                                                    <span class="badge bg-primary">Soumis</span>
                                                @elseif($contract->status == 'in_review')
                                                    <span class="badge bg-warning text-dark">En révision</span>
                                                @elseif($contract->status == 'admin_signed')
                                                    <span class="badge bg-info text-dark">Signé admin</span>
                                                @elseif($contract->status == 'employee_signed')
                                                    <span class="badge bg-success">Signé employé</span>
                                                @elseif($contract->status == 'completed')
                                                    <span class="badge bg-success">Complété</span>
                                                @elseif($contract->status == 'rejected')
                                                    <span class="badge bg-danger">Rejeté</span>
                                                @endif
                                            </td>
                                            <td>{{ $contract->data && $contract->data->monthly_hours ? $contract->data->monthly_hours : 'N/A' }}</td>
                                            <td>{{ $contract->submitted_at ? $contract->submitted_at->format('d/m/Y') : ($contract->created_at ? $contract->created_at->format('d/m/Y') : 'Non spécifiée') }}</td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <a href="{{ route('admin.contracts.show', $contract) }}" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    @if(in_array($contract->status, ['submitted', 'in_review']))
                                                        <a href="{{ route('admin.contracts.edit', $contract) }}" class="btn btn-sm btn-secondary">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="d-flex justify-content-center mt-4">
                            {{ $contracts->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 