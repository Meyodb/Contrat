@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Historique des avenants</h5>
                    <span class="badge bg-info">Contrat #{{ $contract->id }}</span>
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
                                <h6 class="mb-1">Contrat principal</h6>
                                <p class="mb-0">Signé le {{ $contract->completed_at ? $contract->completed_at->format('d/m/Y') : ($contract->employee_signed_at ? $contract->employee_signed_at->format('d/m/Y') : 'Non signé') }}</p>
                                @if($contract->data)
                                    <p class="mb-0">Heures hebdomadaires initiales: {{ $contract->data->work_hours ?? 'Non spécifié' }} h</p>
                                    <p class="mb-0">Salaire mensuel initial: {{ $contract->data->monthly_gross_salary ?? 'Non spécifié' }} €</p>
                                @endif
                            </div>
                            <a href="{{ route('employee.contracts.show', $contract) }}" class="btn btn-outline-primary">
                                <i class="bi bi-file-earmark"></i> Voir le contrat principal
                            </a>
                        </div>
                    </div>
                    
                    <h6 class="border-bottom pb-2 mb-3">Évolution de vos conditions de travail</h6>
                    
                    @if($contract->avenants && $contract->avenants->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>N° Avenant</th>
                                        <th>Date d'effet</th>
                                        <th>Horaire hebdomadaire</th>
                                        <th>Salaire mensuel</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($contract->avenants as $avenant)
                                    <tr>
                                        <td>{{ $avenant->avenant_number }}</td>
                                        <td>
                                            @if($avenant->data && $avenant->data->effective_date)
                                                {{ \Carbon\Carbon::parse($avenant->data->effective_date)->format('d/m/Y') }}
                                            @else
                                                Non spécifié
                                            @endif
                                        </td>
                                        <td>
                                            @if($avenant->data && $avenant->data->work_hours)
                                                {{ $avenant->data->work_hours }} h/semaine
                                            @else
                                                Non spécifié
                                            @endif
                                        </td>
                                        <td>
                                            @if($avenant->data && $avenant->data->monthly_gross_salary)
                                                {{ $avenant->data->monthly_gross_salary }} €
                                            @else
                                                Non spécifié
                                            @endif
                                        </td>
                                        <td>
                                            @if($avenant->status == 'draft')
                                                <span class="badge bg-secondary">Brouillon</span>
                                            @elseif($avenant->status == 'submitted')
                                                <span class="badge bg-primary">Soumis</span>
                                            @elseif($avenant->status == 'admin_signed')
                                                <span class="badge bg-info">À signer</span>
                                            @elseif($avenant->status == 'employee_signed' || $avenant->status == 'completed')
                                                <span class="badge bg-success">Signé le {{ $avenant->employee_signed_at ? $avenant->employee_signed_at->format('d/m/Y') : 'Non spécifié' }}</span>
                                            @elseif($avenant->status == 'rejected')
                                                <span class="badge bg-danger">Rejeté</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('employee.avenants.show', $avenant) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i> Détails
                                                </a>
                                                <a href="{{ route('employee.contracts.preview', $avenant) }}" class="btn btn-sm btn-outline-secondary" target="_blank">
                                                    <i class="bi bi-file-earmark-pdf"></i> PDF
                                                </a>
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
                    @else
                        <div class="alert alert-warning">
                            <p class="mb-0">Vous n'avez pas encore d'avenants à votre contrat.</p>
                        </div>
                    @endif
                </div>
                <div class="card-footer bg-white">
                    <a href="{{ route('employee.contracts.show', $contract) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Retour au contrat
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 