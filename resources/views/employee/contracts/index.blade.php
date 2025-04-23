@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Mes contrats</h1>
            </div>
            
            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif
            
            @if(session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif
            
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <a class="nav-link active" href="#contracts" data-bs-toggle="tab">Contrats principaux</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#avenants" data-bs-toggle="tab">Avenants</a>
                </li>
            </ul>
            
            <div class="tab-content">
                <!-- Onglet Contrats principaux -->
                <div class="tab-pane fade show active" id="contracts">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Mes contrats principaux</h5>
                        </div>
                        <div class="card-body">
                            @if($contracts->where('is_avenant', false)->isEmpty())
                                <div class="alert alert-info">
                                    <p>Vous n'avez pas encore de contrat principal.</p>
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Titre</th>
                                                <th>Type</th>
                                                <th>Statut</th>
                                                <th>Date de création</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($contracts->where('is_avenant', false) as $contract)
                                                <tr>
                                                    <td>{{ $contract->title }}</td>
                                                    <td>{{ $contract->contract_type == 'cdi' ? 'CDI' : $contract->contract_type }}</td>
                                                    <td>
                                                        @if($contract->status == 'draft')
                                                            <span class="badge bg-secondary">Brouillon</span>
                                                        @elseif($contract->status == 'submitted')
                                                            <span class="badge bg-primary">Soumis</span>
                                                        @elseif($contract->status == 'admin_signed')
                                                            <span class="badge bg-info">À signer</span>
                                                        @elseif($contract->status == 'employee_signed')
                                                            <span class="badge bg-success">Signé</span>
                                                        @elseif($contract->status == 'completed')
                                                            <span class="badge bg-success">Complété</span>
                                                        @elseif($contract->status == 'rejected')
                                                            <span class="badge bg-danger">Rejeté</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $contract->created_at->format('d/m/Y') }}</td>
                                                    <td>
                                                        <a href="{{ route('employee.contracts.show', $contract) }}" class="btn btn-sm btn-primary">
                                                            <i class="bi bi-eye"></i> Voir
                                                        </a>
                                                        @if($contract->status == 'admin_signed')
                                                            <a href="{{ route('employee.contracts.sign', $contract) }}" class="btn btn-sm btn-success">
                                                                <i class="bi bi-pen"></i> Signer
                                                            </a>
                                                        @endif
                                                        <a href="{{ route('employee.contracts.preview', $contract) }}" class="btn btn-sm btn-secondary" target="_blank">
                                                            <i class="bi bi-file-earmark-pdf"></i> PDF
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                
                <!-- Onglet Avenants -->
                <div class="tab-pane fade" id="avenants">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Mes avenants</h5>
                        </div>
                        <div class="card-body">
                            @if($contracts->where('is_avenant', true)->isEmpty())
                                <div class="alert alert-info">
                                    <p>Vous n'avez pas encore d'avenant à vos contrats.</p>
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>N° Avenant</th>
                                                <th>Contrat parent</th>
                                                <th>Statut</th>
                                                <th>Date d'effet</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($contracts->where('is_avenant', true) as $avenant)
                                                <tr>
                                                    <td>Avenant n°{{ $avenant->avenant_number }}</td>
                                                    <td>
                                                        @if($avenant->parentContract)
                                                            <a href="{{ route('employee.contracts.show', $avenant->parentContract) }}">
                                                                {{ $avenant->parentContract->title }}
                                                            </a>
                                                        @else
                                                            Non défini
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
                                                    <td>{{ $avenant->effective_date ? $avenant->effective_date->format('d/m/Y') : 'Non définie' }}</td>
                                                    <td>
                                                        <a href="{{ route('employee.contracts.show', $avenant) }}" class="btn btn-sm btn-primary">
                                                            <i class="bi bi-eye"></i> Voir
                                                        </a>
                                                        @if($avenant->status == 'admin_signed')
                                                            <a href="{{ route('employee.contracts.sign', $avenant) }}" class="btn btn-sm btn-success">
                                                                <i class="bi bi-pen"></i> Signer
                                                            </a>
                                                        @endif
                                                        <a href="{{ route('employee.contracts.preview', $avenant) }}" class="btn btn-sm btn-secondary" target="_blank">
                                                            <i class="bi bi-file-earmark-pdf"></i> PDF
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 