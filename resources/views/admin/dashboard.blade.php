@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Tableau de bord administrateur</h1>
                <div>
                    <a href="{{ route('admin.contracts.index') }}" class="btn btn-outline-primary me-2">
                        <i class="bi bi-file-earmark-text"></i> Tous les contrats
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-primary me-2">
                        <i class="bi bi-people"></i> Tous les utilisateurs
                    </a>
                    <a href="{{ route('admin.employees.finalized') }}" class="btn btn-outline-primary">
                        <i class="bi bi-table"></i> Tableau d'émargement
                    </a>
                </div>
            </div>
            
            <!-- Contrats nécessitant une action -->
            <div class="card mb-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Contrats nécessitant une action</h5>
                        <a href="{{ route('admin.contracts.index', ['status' => 'submitted']) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-list"></i> Voir tous
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(count($pendingContracts) > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Employé</th>
                                        <th>Statut</th>
                                        <th>Heures menssuelles</th>
                                        <th>Date de soumission</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pendingContracts as $contract)
                                        <tr>
                                            <td>{{ $contract->user->name }}</td>
                                            <td>
                                                @if($contract->status == 'submitted')
                                                    <span class="badge bg-primary">Soumis</span>
                                                @elseif($contract->status == 'employee_signed')
                                                    <span class="badge bg-success">Signé par employé</span>
                                                @endif
                                            </td>
                                            <td>{{ $contract->data->monthly_hours ? : 'N/A'}}</td>
                                            <td>{{ $contract->submitted_at ? $contract->submitted_at->format('d/m/Y') : 'Non spécifiée' }}</td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="{{ route('admin.contracts.show', $contract) }}" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="{{ route('admin.contracts.edit', $contract) }}" class="btn btn-sm btn-secondary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p>Aucun contrat en attente d'action.</p>
                    @endif
                </div>
            </div>
            
            <!-- Contrats récents avec actions simplifiées -->
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Contrats récents</h5>
                        <a href="{{ route('admin.contracts.index') }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-list"></i> Voir tous
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(count($recentContracts) > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Employé</th>
                                        <th>Statut</th>
                                        <th>Heures mensuelles</th>
                                        <th>Date de création</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentContracts as $contract)
                                        <tr>
                                            <td>{{ $contract->user->name }}</td>
                                            <td>
                                                @if($contract->status == 'draft')
                                                    <span class="badge bg-secondary">Brouillon</span>
                                                @elseif($contract->status == 'submitted')
                                                    <span class="badge bg-primary">Soumis</span>
                                                @elseif($contract->status == 'in_review')
                                                    <span class="badge bg-warning">En révision</span>
                                                @elseif($contract->status == 'admin_signed')
                                                    <span class="badge bg-info">Signé admin</span>
                                                @elseif($contract->status == 'employee_signed')
                                                    <span class="badge bg-success">Signé employé</span>
                                                @elseif($contract->status == 'completed')
                                                    <span class="badge bg-success">Complété</span>
                                                @elseif($contract->status == 'rejected')
                                                    <span class="badge bg-danger">Rejeté</span>
                                                @endif
                                            </td>
                                            <td>{{ $contract->data->monthly_hours ? : 'N/A' }}</td>
                                            <td>{{ $contract->created_at ? $contract->created_at->format('d/m/Y') : 'Non spécifiée' }}</td>
                                            <td>
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
                    @else
                        <p>Aucun contrat récent.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 