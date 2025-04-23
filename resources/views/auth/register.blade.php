@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card login-card">
                <div class="card-header">
                    <h4>{{ __('Inscription') }}</h4>
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('register') }}">
                        @csrf

                        <div class="row mb-2">
                            <!-- Prénom et Nom sur la même ligne -->
                            <div class="col-6">
                                <label for="first_name" class="form-label small">{{ __('Prénom') }}</label>
                                <div class="input-group input-group-sm has-validation">
                                    <span class="input-group-text py-0"><i class="bi bi-person"></i></span>
                                    <input id="first_name" type="text" class="form-control form-control-sm @error('first_name') is-invalid @enderror" name="first_name" value="{{ old('first_name') }}" required autocomplete="given-name" autofocus style="height: 31px; padding: 0.25rem 0.5rem;">
                                </div>
                                @error('first_name')
                                    <span class="invalid-feedback d-block small" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                            
                            <div class="col-6">
                                <label for="last_name" class="form-label small">{{ __('Nom') }}</label>
                                <div class="input-group input-group-sm has-validation">
                                    <span class="input-group-text py-0"><i class="bi bi-person"></i></span>
                                    <input id="last_name" type="text" class="form-control form-control-sm @error('last_name') is-invalid @enderror" name="last_name" value="{{ old('last_name') }}" required autocomplete="family-name" style="height: 31px; padding: 0.25rem 0.5rem;">
                                </div>
                                @error('last_name')
                                    <span class="invalid-feedback d-block small" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-2">
                            <!-- Email sur toute la largeur -->
                            <div class="col-12">
                                <label for="email" class="form-label small">{{ __('Adresse email') }}</label>
                                <div class="input-group input-group-sm has-validation">
                                    <span class="input-group-text py-0"><i class="bi bi-envelope"></i></span>
                                    <input id="email" type="email" class="form-control form-control-sm @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" style="height: 31px; padding: 0.25rem 0.5rem;">
                                </div>
                                @error('email')
                                    <span class="invalid-feedback d-block small" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-2">
                            <!-- Confirmation d'email sur toute la largeur -->
                            <div class="col-12">
                                <label for="email_confirmation" class="form-label small">{{ __('Confirmer l\'email') }}</label>
                                <div class="input-group input-group-sm has-validation">
                                    <span class="input-group-text py-0"><i class="bi bi-envelope-check"></i></span>
                                    <input id="email_confirmation" type="email" class="form-control form-control-sm @error('email_confirmation') is-invalid @enderror" name="email_confirmation" value="{{ old('email_confirmation') }}" required autocomplete="email" style="height: 31px; padding: 0.25rem 0.5rem;">
                                </div>
                                @error('email_confirmation')
                                    <span class="invalid-feedback d-block small" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-2">
                            <!-- Mot de passe et confirmation sur la même ligne -->
                            <div class="col-6">
                                <label for="password" class="form-label small">{{ __('Mot de passe') }}</label>
                                <div class="input-group input-group-sm has-validation">
                                    <span class="input-group-text py-0"><i class="bi bi-lock"></i></span>
                                    <input id="password" type="password" class="form-control form-control-sm @error('password') is-invalid @enderror" name="password" required autocomplete="new-password" style="height: 31px; padding: 0.25rem 0.5rem;">
                                </div>
                                @error('password')
                                    <span class="invalid-feedback d-block small" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                            
                            <div class="col-6">
                                <label for="password-confirm" class="form-label small">{{ __('Confirmer') }}</label>
                                <div class="input-group input-group-sm has-validation">
                                    <span class="input-group-text py-0"><i class="bi bi-shield-lock"></i></span>
                                    <input id="password-confirm" type="password" class="form-control form-control-sm" name="password_confirmation" required autocomplete="new-password" style="height: 31px; padding: 0.25rem 0.5rem;">
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12 d-flex justify-content-between align-items-center">
                                <div>
                                    <small>Déjà inscrit ? <a href="{{ route('login') }}" class="btn-link">{{ __('Connectez-vous ici') }}</a></small>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm login-btn">
                                    <i class="bi bi-person-plus me-1"></i>{{ __('S\'inscrire') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.input-group-text {
    border-radius: 0.2rem 0 0 0.2rem;
    padding: 0.25rem 0.5rem;
}

.form-control {
    border-radius: 0 0.2rem 0.2rem 0;
}

.login-card {
    border-radius: 0.5rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
    padding: 0.75rem 1rem;
}

.card-body {
    padding: 1.25rem;
}
</style>
@endsection
