@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Profil Administrateur</h5>
                </div>
                <div class="card-body">
                    @if (session('status'))
                    <div class="alert alert-success position-relative">
                        <button type="button" class="btn-close position-absolute" style="top: 10px; right: 10px; font-size: 0.8rem;" data-bs-dismiss="alert" aria-label="Fermer"></button>
                        {{ session('status') }}
                    </div>
                    @endif

                    @if ($errors->any())
                    <div class="alert alert-danger position-relative">
                        <button type="button" class="btn-close position-absolute" style="top: 10px; right: 10px; font-size: 0.8rem;" data-bs-dismiss="alert" aria-label="Fermer"></button>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                    
                    <form method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Nom</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                id="name" name="name" value="{{ old('name', $user->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                id="email" name="email" value="{{ old('email', $user->email) }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-4">
                            <label for="signature" class="form-label">Signature (Image)</label>
                            <div class="input-group">
                                <input type="file" class="form-control @error('signature_image') is-invalid @enderror" 
                                    id="signature_image" name="signature_image" accept="image/*">
                                <label class="input-group-text" for="signature_image">Choisir</label>
                            </div>
                            @error('signature_image')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Téléchargez une image de votre signature. Format recommandé: PNG avec fond transparent.</div>
                            
                            @if($user->signature_image)
                                <div class="mt-2">
                                    <p>Signature actuelle:</p>
                                    <img src="{{ asset('storage/'.$user->signature_image) }}" alt="Signature" class="img-fluid border p-2" style="max-height: 100px;">
                                </div>
                            @endif
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" 
                                    id="change_password" name="change_password">
                                <label class="form-check-label" for="change_password">
                                    Changer le mot de passe
                                </label>
                            </div>
                        </div>
                        
                        <div id="password-fields" class="d-none">
                            <div class="mb-3">
                                <label for="password" class="form-label">Nouveau mot de passe</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                    id="password" name="password">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="mb-3">
                                <label for="password_confirmation" class="form-label">Confirmer le mot de passe</label>
                                <input type="password" class="form-control" 
                                    id="password_confirmation" name="password_confirmation">
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const changePasswordCheckbox = document.getElementById('change_password');
        const passwordFields = document.getElementById('password-fields');
        
        changePasswordCheckbox.addEventListener('change', function() {
            if (this.checked) {
                passwordFields.classList.remove('d-none');
            } else {
                passwordFields.classList.add('d-none');
                document.getElementById('password').value = '';
                document.getElementById('password_confirmation').value = '';
            }
        });
    });
</script>
@endsection
@endsection 