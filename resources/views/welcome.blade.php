@extends('layouts.app')

@section('content')
<div class="welcome-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10 text-center">
                <h1>Bienvenue sur l'Espace Soup&Juice</h1>
                <p class="lead">
                    Votre plateforme de gestion de contrats d'entreprise.
                </p>
                <div class="mt-5">
                    @auth
                        @if(Auth::check() && Auth::user()->is_admin)
                            <a href="{{ url('/admin/dashboard') }}" class="btn btn-primary px-4">Accéder à l'espace administrateur</a>
                        @else
                            <a href="{{ url('/home') }}" class="btn btn-primary px-4">Accéder à mon espace</a>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="btn btn-primary btn-lg me-3">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Connexion
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="btn btn-outline-primary btn-lg">
                                <i class="bi bi-person-plus me-2"></i>Inscription
                            </a>
                        @endif
                    @endauth
                </div>
                </div>
        </div>
    </div>
</div>
@endsection
