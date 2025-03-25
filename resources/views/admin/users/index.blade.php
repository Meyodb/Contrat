@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Gestion des utilisateurs</h1>
                <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                    <i class="bi bi-person-plus"></i> Créer un utilisateur
                </a>
            </div>
            
            @if(session('success'))
                <div class="alert alert-success position-relative">
                    <button type="button" class="btn-close position-absolute" style="top: 10px; right: 10px; font-size: 0.8rem;" data-bs-dismiss="alert" aria-label="Fermer"></button>
                    {{ session('success') }}
                </div>
                @endif
                
                @if(session('error'))
                <div class="alert alert-danger position-relative">
                    <button type="button" class="btn-close position-absolute" style="top: 10px; right: 10px; font-size: 0.8rem;" data-bs-dismiss="alert" aria-label="Fermer"></button>
                    {{ session('error') }}
                </div>
                @endif
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Liste des utilisateurs</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <form action="{{ route('admin.users.index') }}" method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                                <div class="input-group" style="max-width: 350px;">
                                    <input type="text" name="search" class="form-control" placeholder="Rechercher..." value="{{ request('search') }}">
                                    <button class="btn btn-outline-secondary" type="submit">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                                <select name="role" class="form-select" style="max-width: 180px;" onchange="this.form.submit()">
                                    <option value="">Tous les rôles</option>
                                    <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Administrateurs</option>
                                    <option value="employee" {{ request('role') == 'employee' ? 'selected' : '' }}>Employés</option>
                                </select>
                                <select name="sort" class="form-select" style="max-width: 180px;" onchange="this.form.submit()">
                                    <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Plus récents</option>
                                    <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Plus anciens</option>
                                    <option value="name_asc" {{ request('sort') == 'name_asc' ? 'selected' : '' }}>Nom (A-Z)</option>
                                    <option value="name_desc" {{ request('sort') == 'name_desc' ? 'selected' : '' }}>Nom (Z-A)</option>
                                </select>
                                @if(request('search') || request('role') || request('sort'))
                                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary d-flex align-items-center">
                                        <i class="bi bi-x-circle me-1"></i> Réinitialiser
                                    </a>
                                @endif
                                <span class="ms-auto text-muted">{{ count($users) }} utilisateur(s)</span>
                            </form>
                        </div>
                    </div>
                    
                    @if(count($users) > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Email</th>
                                        <th>Heures mensuelles</th>
                                        <th>Date d'inscription</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($users as $user)
                                        <tr>
                                            <td>{{ $user->name }}</td>
                                            <td>{{ $user->email }}</td>
                                            <td>
                                                @if($user->contracts->isNotEmpty() && $user->contracts->first()->data && $user->contracts->first()->data->monthly_hours)
                                                    {{ $user->contracts->first()->data->monthly_hours }}
                                                @else
                                                    Non défini
                                                @endif
                                            </td>
                                            <td>{{ $user->created_at ? $user->created_at->format('d/m/Y') : 'Non spécifiée' }}</td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Voir les détails">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-secondary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    @if($user->id !== auth()->id())
                                                        <button type="button" class="btn btn-sm btn-danger" 
                                                                onclick="event.preventDefault(); 
                                                                if(confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.')) 
                                                                document.getElementById('delete-form-{{ $user->id }}').submit();">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                        <form id="delete-form-{{ $user->id }}" action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-none">
                                                            @csrf
                                                            @method('DELETE')
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info position-relative">
                            <button type="button" class="btn-close position-absolute" style="top: 10px; right: 10px; font-size: 0.8rem;" data-bs-dismiss="alert" aria-label="Fermer"></button>
                            <h5>Aucun utilisateur trouvé</h5>
                            <p>Il n'y a pas encore d'utilisateurs dans le système.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 