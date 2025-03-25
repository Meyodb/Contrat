@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Remplir mon contrat</h5>
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
                    
                    <!-- Étapes du processus (pour guider l'utilisateur) -->
                    <div class="mb-4">
                        <div class="progress" style="height: 4px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 33%;" aria-valuenow="33" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="d-flex justify-content-between mt-2 text-muted small">
                            <div class="text-success">
                                <i class="bi bi-check-circle-fill"></i> Création
                            </div>
                            <div class="{{ $contract->status != 'draft' ? 'text-success' : 'text-muted' }}">
                                <i class="{{ $contract->status != 'draft' ? 'bi bi-check-circle-fill' : 'bi bi-circle' }}"></i> Soumission
                            </div>
                            <div class="{{ in_array($contract->status, ['admin_signed', 'employee_signed', 'completed']) ? 'text-success' : 'text-muted' }}">
                                <i class="{{ in_array($contract->status, ['admin_signed', 'employee_signed', 'completed']) ? 'bi bi-check-circle-fill' : 'bi bi-circle' }}"></i> Validation
                            </div>
                        </div>
                    </div>
                    
                    <form method="POST" action="{{ route('employee.contracts.update', $contract) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">Référence du contrat</label>
                            <input type="text" class="form-control" value="{{ $contract->title }}" readonly>
                            <div class="form-text">La référence de votre contrat.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Type de contrat</label>
                            <input type="text" class="form-control" value="CDI (Contrat à Durée Indéterminée)" readonly>
                        </div>
                        
                        <div class="mb-4">
                            <h5 class="border-bottom pb-2 mb-3">
                                <i class="bi bi-person"></i> Informations personnelles
                            </h5>
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="first_name" class="form-label">Prénom</label>
                                    <input type="text" class="form-control @error('data.first_name') is-invalid @enderror" id="first_name" name="data[first_name]" value="{{ old('data.first_name', $contract->data ? $contract->data->first_name : '') }}" required>
                                    @error('data.first_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="last_name" class="form-label">Nom</label>
                                    <input type="text" class="form-control @error('data.last_name') is-invalid @enderror" id="last_name" name="data[last_name]" value="{{ old('data.last_name', $contract->data ? $contract->data->last_name : '') }}" required>
                                    @error('data.last_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="gender" class="form-label">Civilité</label>
                                    <select class="form-select @error('data.gender') is-invalid @enderror" id="gender" name="data[gender]" required>
                                        <option value="">Sélectionnez...</option>
                                        <option value="M" {{ old('data.gender', $contract->data ? $contract->data->gender : '') == 'M' ? 'selected' : '' }}>Monsieur</option>
                                        <option value="F" {{ old('data.gender', $contract->data ? $contract->data->gender : '') == 'F' ? 'selected' : '' }}>Madame</option>
                                    </select>
                                    @error('data.gender')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="birth_date" class="form-label">Date de naissance</label>
                                    <input type="date" class="form-control @error('data.birth_date') is-invalid @enderror" id="birth_date" name="data[birth_date]" value="{{ old('data.birth_date', $contract->data && $contract->data->birth_date ? $contract->data->birth_date->format('Y-m-d') : '') }}" required>
                                    @error('data.birth_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="birth_place" class="form-label">Ville de naissance (Pays si étranger)</label>
                                    <input type="text" class="form-control @error('data.birth_place') is-invalid @enderror" id="birth_place" name="data[birth_place]" value="{{ old('data.birth_place', $contract->data ? $contract->data->birth_place : '') }}" required>
                                    @error('data.birth_place')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="nationality" class="form-label">Nationalité</label>
                                    <input type="text" class="form-control @error('data.nationality') is-invalid @enderror" id="nationality" name="data[nationality]" value="{{ old('data.nationality', $contract->data ? $contract->data->nationality : '') }}" required>
                                    @error('data.nationality')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="data[address]" class="form-label">Adresse</label>
                                    <input type="text" class="form-control @error('data.address') is-invalid @enderror" id="data[address]" name="data[address]" value="{{ old('data.address', $contract->data->address ?? '') }}" required>
                                    @error('data.address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="data[postal_code]" class="form-label">Code postal</label>
                                        <input type="text" class="form-control @error('data.postal_code') is-invalid @enderror" id="data[postal_code]" name="data[postal_code]" value="{{ old('data.postal_code', $contract->data->postal_code ?? '') }}" required>
                                        @error('data.postal_code')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label for="data[city]" class="form-label">Ville</label>
                                        <input type="text" class="form-control @error('data.city') is-invalid @enderror" id="data[city]" name="data[city]" value="{{ old('data.city', $contract->data->city ?? '') }}" required>
                                        @error('data.city')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="social_security_number" class="form-label">Numéro de sécurité sociale</label>
                                    <input type="text" class="form-control @error('data.social_security_number') is-invalid @enderror" id="social_security_number" name="data[social_security_number]" value="{{ old('data.social_security_number', $contract->data ? $contract->data->social_security_number : '') }}" required>
                                    @error('data.social_security_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control @error('data.email') is-invalid @enderror" id="email" name="data[email]" value="{{ old('data.email', $contract->data ? $contract->data->email : '') }}" required>
                                    @error('data.email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Téléphone</label>
                                    <input type="text" class="form-control @error('data.phone') is-invalid @enderror" id="phone" name="data[phone]" value="{{ old('data.phone', $contract->data ? $contract->data->phone : '') }}" required>
                                    @error('data.phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="bank_details" class="form-label">Coordonnées bancaires (RIB)</label>
                                    <textarea class="form-control @error('data.bank_details') is-invalid @enderror" id="bank_details" name="data[bank_details]" rows="2" required>{{ old('data.bank_details', $contract->data ? $contract->data->bank_details : '') }}</textarea>
                                    @error('data.bank_details')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="employee_photo" class="form-label">Photo d'identité</label>
                                    <input type="file" class="form-control @error('employee_photo') is-invalid @enderror" id="employee_photo" name="employee_photo" accept="image/jpeg,image/png,image/gif">
                                    <div class="form-text">Téléchargez une photo d'identité (format JPG, PNG ou GIF, max 2MB)</div>
                                    @error('employee_photo')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    
                                    @if($contract->data && $contract->data->photo_path)
                                        <div class="mt-2">
                                            <img src="{{ asset('storage/' . $contract->data->photo_path) }}" alt="Photo d'identité" class="img-thumbnail" style="max-height: 150px;">
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="{{ url()->previous() == url()->current() ? route('home') : url()->previous() }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Retour
                            </a>
                            <div>
                                <button type="submit" name="action" value="save" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Enregistrer
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Modal de confirmation pour la soumission -->
                    <div class="modal fade" id="submitModal" tabindex="-1" aria-labelledby="submitModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="submitModalLabel">Confirmation de soumission</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Êtes-vous sûr de vouloir soumettre ce contrat ? Vous ne pourrez plus le modifier après soumission.</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                    <form action="{{ route('employee.contracts.submit', $contract) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-success">Confirmer la soumission</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 