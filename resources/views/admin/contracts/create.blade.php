@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Créer un nouveau contrat</h5>
                </div>
                
                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form action="{{ route('admin.contracts.store') }}" method="POST">
                        @csrf
                        
                        <!-- Sélection de l'employé -->
                        <div class="mb-4">
                            <label for="user_id" class="form-label">Sélectionner un employé</label>
                            <select name="user_id" id="user_id" class="form-select @error('user_id') is-invalid @enderror" required>
                                <option value="">Choisir un employé...</option>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}" {{ old('user_id') == $employee->id ? 'selected' : '' }}>
                                        {{ $employee->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('user_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Type de contrat -->
                        <div class="mb-4">
                            <label class="form-label">Type de contrat</label>
                            <div class="d-flex flex-column gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="contract_type" id="type_cdi" value="cdi" {{ old('contract_type') == 'cdi' ? 'checked' : '' }} required>
                                    <label class="form-check-label" for="type_cdi">
                                        <strong>CDI</strong> - Contrat à Durée Indéterminée
                                    </label>
                                </div>
                                
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="contract_type" id="type_avenant" value="avenant" {{ old('contract_type') == 'avenant' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="type_avenant">
                                        <strong>Avenant</strong> - Modification d'un contrat existant
                                    </label>
                                    <div class="form-text ms-4">
                                        Crée un avenant au dernier contrat de l'employé pour modifier ses conditions.
                                    </div>
                                </div>
                            </div>
                            @error('contract_type')
                                <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('admin.contracts.index') }}" class="btn btn-secondary">Annuler</a>
                            <button type="submit" class="btn btn-primary">Continuer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 