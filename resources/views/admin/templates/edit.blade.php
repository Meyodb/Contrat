@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Modifier le modèle de contrat</h5>
                </div>
                <div class="card-body">
                    @if (session('status'))
                    <div class="alert alert-success position-relative">
                        <button type="button" class="btn-close position-absolute" style="top: 10px; right: 10px; font-size: 0.8rem;" data-bs-dismiss="alert" aria-label="Fermer"></button>
                        {{ session('status') }}
                    </div>
                    @endif
                    
                    <form method="POST" action="{{ route('admin.templates.update', $template) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Nom</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                id="name" name="name" value="{{ old('name', $template->name) }}" required autofocus>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                id="description" name="description" rows="3">{{ old('description', $template->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-4">
                            <label for="template_file" class="form-label">Fichier de modèle (docx, doc)</label>
                            <input type="file" class="form-control @error('template_file') is-invalid @enderror" 
                                id="template_file" name="template_file">
                            @error('template_file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            
                            @if($template->file_path)
                            <div class="mt-2">
                                <span class="badge bg-info">Fichier actuel :</span> 
                                <a href="{{ route('admin.templates.download', $template) }}" class="ms-2">Télécharger</a>
                            </div>
                            <div class="form-text">Téléchargez un nouveau fichier seulement si vous souhaitez remplacer l'existant.</div>
                            @endif
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.templates.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Annuler
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-save"></i> Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 