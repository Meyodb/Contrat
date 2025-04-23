@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card login-card">
                <div class="card-header">
                    <h4>{{ __('Connexion') }}</h4>
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="row mb-2">
                            <div class="col-12">
                                <label for="email" class="form-label small">{{ __('Adresse email') }}</label>
                                <div class="input-group input-group-sm has-validation">
                                    <span class="input-group-text py-0"><i class="bi bi-envelope"></i></span>
                                    <input id="email" type="email" class="form-control form-control-sm @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus style="height: 31px; padding: 0.25rem 0.5rem;">
                                </div>
                                @error('email')
                                    <span class="invalid-feedback d-block small" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-2">
                            <div class="col-12">
                                <label for="password" class="form-label small">{{ __('Mot de passe') }}</label>
                                <div class="input-group input-group-sm has-validation">
                                    <span class="input-group-text py-0"><i class="bi bi-lock"></i></span>
                                    <input id="password" type="password" class="form-control form-control-sm @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" style="height: 31px; padding: 0.25rem 0.5rem;">
                                </div>
                                @error('password')
                                    <span class="invalid-feedback d-block small" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-2">
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="remember">
                                        {{ __('Se souvenir de moi') }}
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12 d-flex justify-content-between align-items-center">
                                @if (Route::has('password.request'))
                                    <a class="btn-link small" href="{{ route('password.request') }}">
                                        {{ __('Mot de passe oublié?') }}
                                    </a>
                                @endif
                                <button type="submit" class="btn btn-primary btn-sm login-btn">
                                    <i class="bi bi-box-arrow-in-right me-1"></i>{{ __('Connexion') }}
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
