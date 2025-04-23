@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Modèles de contrats</h1>
                <a href="{{ route('admin.templates.create') }}" class="btn btn-primary">Ajouter un modèle</a>
            </div>
            
            @if (session('status'))
            <div class="alert alert-success position-relative">
                <button type="button" class="btn-close position-absolute" style="top: 10px; right: 10px; font-size: 0.8rem;" data-bs-dismiss="alert" aria-label="Fermer"></button>
                {{ session('status') }}
            </div>
            @endif

            @if (session('error'))
            <div class="alert alert-danger position-relative">
                <button type="button" class="btn-close position-absolute" style="top: 10px; right: 10px; font-size: 0.8rem;" data-bs-dismiss="alert" aria-label="Fermer"></button>
                {{ session('error') }}
            </div>
            @endif
            
            <div class="card">
                <div class="card-body">
                    @if(count($templates) > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Description</th>
                                        <th>Fichier</th>
                                        <th>Date de création</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($templates as $template)
                                        <tr>
                                            <td>{{ $template->name }}</td>
                                            <td>{{ $template->description }}</td>
                                            <td>
                                                @if($template->file_path)
                                                    <a href="{{ route('admin.templates.download', $template) }}" class="btn btn-sm btn-secondary">
                                                        Télécharger
                                                    </a>
                                                @else
                                                    <span class="text-muted">Aucun fichier</span>
                                                @endif
                                            </td>
                                            <td>{{ $template->created_at ? $template->created_at->format('d/m/Y H:i') : 'Non spécifiée' }}</td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="{{ route('admin.templates.edit', $template) }}" class="btn btn-sm btn-primary">Modifier</a>
                                                    <form action="{{ route('admin.templates.destroy', $template) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce modèle ?')">Supprimer</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-4">
                            {{ $templates->links() }}
                        </div>
                    @else
                        <p>Aucun modèle de contrat disponible.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 