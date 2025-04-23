@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Accès refusé</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size: 4rem;"></i>
                    </div>
                    <h2 class="text-center mb-4">Vous n'avez pas accès à cette page</h2>
                    <p class="text-center mb-4">
                        Cette section est réservée aux administrateurs. Vous n'avez pas les droits nécessaires pour y accéder.
                    </p>
                    <div class="text-center">
                        <a href="{{ route('home') }}" class="btn btn-primary">
                            Retourner à l'accueil
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 