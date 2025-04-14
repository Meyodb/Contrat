@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Création d'un avenant</h5>
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
                        <p>Vous êtes en train de créer un avenant au contrat de <strong>{{ $contract->user->name }}</strong>.</p>
                        <p>Le contrat original a été signé le {{ $contract->completed_at ? $contract->completed_at->format('d/m/Y') : $contract->employee_signed_at->format('d/m/Y') }}.</p>
                    </div>
                    
                    <form action="{{ route('admin.contracts.store-avenant', $contract) }}" method="POST">
                        @csrf
                        
                        <div class="mb-4">
                            <h6 class="border-bottom pb-2 mb-3">Informations de l'avenant</h6>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="avenant_number" class="form-label">Numéro d'avenant</label>
                                    <input type="text" class="form-control" id="avenant_number" name="avenant_number" value="{{ $nextAvenantNumber }}" readonly>
                                    <div class="form-text">Le numéro d'avenant est automatiquement généré.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="contract_date" class="form-label">Date du contrat initial</label>
                                    <input type="date" class="form-control" id="contract_date" name="contract_date" value="{{ $contract->data && $contract->data->contract_signing_date ? $contract->data->contract_signing_date->format('Y-m-d') : '' }}" readonly>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="effective_date" class="form-label">Date d'effet de l'avenant</label>
                                    <input type="date" class="form-control @error('effective_date') is-invalid @enderror" id="effective_date" name="effective_date" value="{{ old('effective_date', now()->format('Y-m-d')) }}" required>
                                    @error('effective_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="signing_date" class="form-label">Date de signature</label>
                                    <input type="date" class="form-control @error('signing_date') is-invalid @enderror" id="signing_date" name="signing_date" value="{{ old('signing_date', now()->format('Y-m-d')) }}" required>
                                    @error('signing_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h6 class="border-bottom pb-2 mb-3">Modification des conditions de travail</h6>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="current_hours" class="form-label">Horaire hebdomadaire actuel</label>
                                    <input type="text" class="form-control" id="current_hours" value="{{ $contract->data && $contract->data->work_hours ? $contract->data->work_hours : 'Non défini' }}" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label for="new_hours" class="form-label">Nouvel horaire hebdomadaire</label>
                                    <input type="number" step="0.01" min="0" max="40" class="form-control @error('new_hours') is-invalid @enderror" id="new_hours" name="new_hours" value="{{ old('new_hours') }}" required>
                                    @error('new_hours')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="current_salary" class="form-label">Salaire mensuel brut actuel</label>
                                    <input type="text" class="form-control" id="current_salary" value="{{ $contract->data && $contract->data->monthly_gross_salary ? $contract->data->monthly_gross_salary . ' €' : 'Non défini' }}" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label for="new_salary" class="form-label">Nouveau salaire mensuel brut</label>
                                    <input type="number" step="0.01" min="0" class="form-control @error('new_salary') is-invalid @enderror" id="new_salary" name="new_salary" required>
                                    @error('new_salary')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="current_hourly_rate" class="form-label">Taux horaire actuel</label>
                                    <input type="text" class="form-control" id="current_hourly_rate" value="{{ $contract->data && $contract->data->hourly_rate ? $contract->data->hourly_rate . ' €' : 'Non défini' }}" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label for="new_hourly_rate" class="form-label">Nouveau taux horaire</label>
                                    <input type="number" step="0.01" min="0" class="form-control @error('new_hourly_rate') is-invalid @enderror" id="new_hourly_rate" name="new_hourly_rate" required>
                                    @error('new_hourly_rate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="calculate_automatically" name="calculate_automatically" checked>
                                <label class="form-check-label" for="calculate_automatically">
                                    Calculer automatiquement les valeurs en fonction du nombre d'heures
                                </label>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.contracts.show', $contract) }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Retour au contrat
                            </a>
                            <div>
                                <button type="button" id="preview-button" class="btn btn-outline-primary me-2">
                                    <i class="bi bi-eye"></i> Prévisualiser
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Créer l'avenant
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Formulaire caché pour la prévisualisation -->
                    <form id="preview-form" action="{{ route('admin.contracts.avenant.preview', $contract) }}" method="POST" target="_blank" style="display: none;">
                        @csrf
                        <input type="hidden" name="avenant_number" id="preview_avenant_number">
                        <input type="hidden" name="effective_date" id="preview_effective_date">
                        <input type="hidden" name="signing_date" id="preview_signing_date">
                        <input type="hidden" name="new_hours" id="preview_new_hours">
                        <input type="hidden" name="new_salary" id="preview_new_salary">
                        <input type="hidden" name="new_hourly_rate" id="preview_new_hourly_rate">
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Récupérer les éléments du formulaire
        const newHoursInput = document.getElementById('new_hours');
        const newSalaryInput = document.getElementById('new_salary');
        const newHourlyRateInput = document.getElementById('new_hourly_rate');
        const calculateAutomatically = document.getElementById('calculate_automatically');
        
        // Gestion de la prévisualisation
        const previewButton = document.getElementById('preview-button');
        const previewForm = document.getElementById('preview-form');
        
        previewButton.addEventListener('click', function() {
            // Récupérer toutes les valeurs du formulaire principal
            const avenantNumber = document.getElementById('avenant_number').value;
            const effectiveDate = document.getElementById('effective_date').value;
            const signingDate = document.getElementById('signing_date').value;
            const newHours = document.getElementById('new_hours').value;
            const newSalary = document.getElementById('new_salary').value;
            const newHourlyRate = document.getElementById('new_hourly_rate').value;
            
            // Mettre à jour les champs cachés du formulaire de prévisualisation
            document.getElementById('preview_avenant_number').value = avenantNumber;
            document.getElementById('preview_effective_date').value = effectiveDate;
            document.getElementById('preview_signing_date').value = signingDate;
            document.getElementById('preview_new_hours').value = newHours;
            document.getElementById('preview_new_salary').value = newSalary;
            document.getElementById('preview_new_hourly_rate').value = newHourlyRate;
            
            // Soumettre le formulaire de prévisualisation
            previewForm.submit();
        });
        
        // Valeurs initiales
        const currentHourlyRate = parseFloat('{{ $contract->data && $contract->data->hourly_rate ? $contract->data->hourly_rate : 0 }}');
        newHourlyRateInput.value = currentHourlyRate.toFixed(2);
        
        // Fonction pour calculer automatiquement
        function calculateValues() {
            if (!calculateAutomatically.checked) return;
            
            const newHours = parseFloat(newHoursInput.value) || 0;
            const hourlyRate = parseFloat(newHourlyRateInput.value) || 0;
            
            // Calculer heures mensuelles (hebdomadaire * 4.33)
            const monthlyHours = newHours * 4.33;
            
            // Calculer salaire mensuel brut
            const monthlySalary = monthlyHours * hourlyRate;
            
            // Mettre à jour le champ de salaire
            newSalaryInput.value = monthlySalary.toFixed(2);
        }
        
        // Ajouter les événements
        newHoursInput.addEventListener('input', calculateValues);
        newHourlyRateInput.addEventListener('input', calculateValues);
        calculateAutomatically.addEventListener('change', calculateValues);
        
        // Calculer les valeurs initiales
        calculateValues();
    });
</script>
@endpush
@endsection 