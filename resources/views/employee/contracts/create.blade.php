@extends('layouts.app')

@php
use Illuminate\Support\Facades\Auth;
// Préparer le titre du contrat au format NOM_PRENOM
$user = Auth::user();
$userName = strtoupper($user->name);
$contractTitle = $userName . "_CDI";
@endphp

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>Création de mon contrat</h5>
                </div>
                <div class="card-body">
                    @if(session('status'))
                        <div class="alert alert-success position-relative">
                            <button type="button" class="btn-close position-absolute" style="top: 10px; right: 10px; font-size: 0.8rem;" data-bs-dismiss="alert" aria-label="Fermer"></button>
                            {{ session('status') }}
                        </div>
                    @endif
                    
                    <form method="POST" action="{{ route('employee.contracts.store') }}">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">Titre du contrat</label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $contractTitle) }}" readonly>
                            <div class="form-text">Le titre du contrat est automatiquement généré avec votre nom.</div>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-4">
                            <label for="contract_template_id" class="form-label">Type de contrat</label>
                            <input type="text" class="form-control" value="CDI (Contrat à Durée Indéterminée)" readonly>
                            <input type="hidden" name="contract_template_id" value="{{ $templates->where('name', 'CDI')->first()->id ?? $templates->first()->id }}">
                            <div class="form-text">Tous les employés sont embauchés en CDI.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Adresse</label>
                            <input type="text" class="form-control" id="address" name="address" value="{{ old('address') }}" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="postal_code" class="form-label">Code postal</label>
                                    <input type="text" class="form-control" id="postal_code" name="postal_code" value="{{ old('postal_code') }}" required>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="city" class="form-label">Ville</label>
                                    <input type="text" class="form-control" id="city" name="city" value="{{ old('city') }}" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('home') }}" class="btn btn-outline-secondary">Annuler</a>
                            <button type="submit" class="btn btn-primary">Créer mon contrat</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 