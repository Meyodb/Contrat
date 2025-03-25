@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h5>Modifier les informations administratives du contrat</h5>
                </div>
                <div class="card-body">
                    @if (session('status'))
                    <div class="alert alert-success position-relative">
                        <button type="button" class="btn-close position-absolute" style="top: 10px; right: 10px; font-size: 0.8rem;" data-bs-dismiss="alert" aria-label="Fermer"></button>
                        {{ session('status') }}
                    </div>
                    @endif
                    
                    <form method="POST" action="{{ route('admin.contracts.update', $contract) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">Référence du contrat</label>
                            <input type="text" class="form-control" value="{{ $contract->title }}" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label for="employee_name" class="form-label">Employé</label>
                            <input type="text" class="form-control" value="{{ $contract->user->name }}" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Statut du contrat</label>
                            <div>
                                @if($contract->status == 'draft')
                                    <span class="badge bg-secondary">Brouillon</span>
                                @elseif($contract->status == 'submitted')
                                    <span class="badge bg-info">Soumis</span>
                                @elseif($contract->status == 'in_review')
                                    <span class="badge bg-warning">En révision</span>
                                @elseif($contract->status == 'admin_signed')
                                    <span class="badge bg-primary">À signer</span>
                                @elseif($contract->status == 'employee_signed')
                                    <span class="badge bg-success">Signé</span>
                                @elseif($contract->status == 'completed')
                                    <span class="badge bg-success">Complété</span>
                                @elseif($contract->status == 'rejected')
                                    <span class="badge bg-danger">Rejeté</span>
                                @endif
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h5 class="border-bottom pb-2 mb-3">Informations du contrat</h5>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="work_hours" class="form-label">Heures de travail (hebdomadaire)</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-clock"></i></span>
                                        <input type="number" step="0.01" min="0" class="form-control @error('data.work_hours') is-invalid @enderror" id="work_hours" name="data[work_hours]" value="{{ old('data.work_hours', $contract->data ? $contract->data->work_hours : '') }}" required>
                                    </div>
                                    @error('data.work_hours')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="hourly_rate" class="form-label">Taux horaire (€)</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-currency-euro"></i></span>
                                        <input type="number" step="0.01" min="0" class="form-control @error('data.hourly_rate') is-invalid @enderror" id="hourly_rate" name="data[hourly_rate]" value="{{ old('data.hourly_rate', $contract->data ? $contract->data->hourly_rate : '') }}" required>
                                    </div>
                                    @error('data.hourly_rate')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="contract_start_date" class="form-label">Date de début du contrat</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                                        <input type="date" class="form-control @error('data.contract_start_date') is-invalid @enderror" id="contract_start_date" name="data[contract_start_date]" value="{{ old('data.contract_start_date', $contract->data && $contract->data->contract_start_date ? $contract->data->contract_start_date->format('Y-m-d') : '') }}" required>
                                    </div>
                                    @error('data.contract_start_date')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="contract_signing_date" class="form-label">Date de signature du contrat</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-pen"></i></span>
                                        <input type="date" class="form-control @error('data.contract_signing_date') is-invalid @enderror" id="contract_signing_date" name="data[contract_signing_date]" value="{{ old('data.contract_signing_date', $contract->data && $contract->data->contract_signing_date ? $contract->data->contract_signing_date->format('Y-m-d') : '') }}" required>
                                    </div>
                                    @error('data.contract_signing_date')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="trial_period_months" class="form-label">Période d'essai (mois)</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-calendar-check"></i></span>
                                        <input type="number" class="form-control @error('data.trial_period_months') is-invalid @enderror" id="trial_period_months" name="data[trial_period_months]" value="{{ old('data.trial_period_months', $contract->data->trial_period_months ?? 1) }}" min="0" max="12" required>
                                    </div>
                                    @error('data.trial_period_months')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Durée de la période d'essai en mois (0-12).</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h5 class="border-bottom pb-2 mb-3">Informations personnelles de l'employé</h5>
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="data[gender]" class="form-label">Genre</label>
                                    <select class="form-select @error('data.gender') is-invalid @enderror" id="gender" name="data[gender]">
                                        <option value="M" {{ old('data.gender', $contract->data && $contract->data->gender == 'M' ? 'selected' : '') }}>Homme</option>
                                        <option value="F" {{ old('data.gender', $contract->data && $contract->data->gender == 'F' ? 'selected' : '') }}>Femme</option>
                                    </select>
                                    @error('data.gender')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="first_name" class="form-label">Prénom</label>
                                    <input type="text" class="form-control @error('data.first_name') is-invalid @enderror" id="first_name" name="data[first_name]" value="{{ old('data.first_name', $contract->data ? $contract->data->first_name : '') }}">
                                    @error('data.first_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="last_name" class="form-label">Nom</label>
                                    <input type="text" class="form-control @error('data.last_name') is-invalid @enderror" id="last_name" name="data[last_name]" value="{{ old('data.last_name', $contract->data ? $contract->data->last_name : '') }}">
                                    @error('data.last_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="employee_photo" class="form-label">Photo de l'employé</label>
                                    <input type="file" class="form-control @error('employee_photo') is-invalid @enderror" id="employee_photo" name="employee_photo" accept="image/*">
                                    <div class="form-text">Téléchargez une photo d'identité de l'employé (format JPG, PNG ou GIF, max 2MB)</div>
                                    @error('employee_photo')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    @if(isset($contract->data) && isset($contract->data->photo_path))
                                        <div class="mt-2">
                                            <img src="{{ asset('storage/' . $contract->data->photo_path) }}" alt="Photo de l'employé" class="img-thumbnail" style="max-height: 100px;">
                                        </div>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="address" class="form-label">Adresse</label>
                                    <input type="text" class="form-control @error('data.address') is-invalid @enderror" id="address" name="data[address]" value="{{ old('data.address', $contract->data ? $contract->data->address : '') }}">
                                    @error('data.address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="postal_code" class="form-label">Code postal</label>
                                    <input type="text" class="form-control @error('data.postal_code') is-invalid @enderror" id="postal_code" name="data[postal_code]" value="{{ old('data.postal_code', $contract->data ? $contract->data->postal_code : '') }}">
                                    @error('data.postal_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-8">
                                    <label for="city" class="form-label">Ville</label>
                                    <input type="text" class="form-control @error('data.city') is-invalid @enderror" id="city" name="data[city]" value="{{ old('data.city', $contract->data ? $contract->data->city : '') }}">
                                    @error('data.city')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control @error('data.email') is-invalid @enderror" id="email" name="data[email]" value="{{ old('data.email', $contract->data ? $contract->data->email : $contract->user->email) }}">
                                    @error('data.email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Téléphone</label>
                                    <input type="text" class="form-control @error('data.phone') is-invalid @enderror" id="phone" name="data[phone]" value="{{ old('data.phone', $contract->data ? $contract->data->phone : '') }}">
                                    @error('data.phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="nationality" class="form-label">Nationalité</label>
                                    <input type="text" class="form-control @error('data.nationality') is-invalid @enderror" id="nationality" name="data[nationality]" value="{{ old('data.nationality', $contract->data ? $contract->data->nationality : '') }}">
                                    @error('data.nationality')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="data[birth_date]" class="form-label">Date de naissance</label>
                                <input type="date" class="form-control @error('data.birth_date') is-invalid @enderror" id="birth_date" name="data[birth_date]" value="{{ old('data.birth_date', $contract->data && $contract->data->birth_date ? $contract->data->birth_date->format('Y-m-d') : '') }}">
                                @error('data.birth_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="data[birth_place]" class="form-label">Lieu de naissance</label>
                                <input type="text" class="form-control @error('data.birth_place') is-invalid @enderror" id="birth_place" name="data[birth_place]" value="{{ old('data.birth_place', $contract->data && $contract->data->birth_place ? $contract->data->birth_place : '') }}">
                                @error('data.birth_place')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="data[social_security_number]" class="form-label">Numéro de sécurité sociale</label>
                                <input type="text" class="form-control @error('data.social_security_number') is-invalid @enderror" id="social_security_number" name="data[social_security_number]" value="{{ old('data.social_security_number', $contract->data && $contract->data->social_security_number ? $contract->data->social_security_number : '') }}">
                                @error('data.social_security_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.contracts.show', $contract) }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Retour au contrat
                            </a>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Enregistrer
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 