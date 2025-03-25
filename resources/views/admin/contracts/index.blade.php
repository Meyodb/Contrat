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
                        <h5 class="mb-0">Gestion administrative</h5>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-4">
                        <h5><i class="bi bi-info-circle me-2"></i>Information</h5>
                        <p>La liste des contrats a été désactivée dans cette interface. Veuillez accéder directement aux contrats via leur URL spécifique ou utiliser les outils d'administration avancés.</p>
                    </div>
                    
                    <div class="row justify-content-center">
                        <div class="col-md-8 mb-4">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <h5 class="card-title"><i class="bi bi-people me-2"></i>Gestion des utilisateurs</h5>
                                    <p class="card-text">Gérez les comptes utilisateurs et leurs permissions dans le système.</p>
                                    <a href="{{ route('admin.users.index') }}" class="btn btn-primary">Gérer les utilisateurs</a>
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