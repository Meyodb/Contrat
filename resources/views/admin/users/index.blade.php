@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Gestion des utilisateurs</h1>
            </div>
            
            @if(session('status'))
                <div class="alert alert-success position-relative">
                    <button type="button" class="btn-close position-absolute" style="top: 10px; right: 10px; font-size: 0.8rem;" data-bs-dismiss="alert" aria-label="Fermer"></button>
                    {{ session('status') }}
                </div>
            @endif
                
            @if(session('error'))
                <div class="alert alert-danger position-relative">
                    <button type="button" class="btn-close position-absolute" style="top: 10px; right: 10px; font-size: 0.8rem;" data-bs-dismiss="alert" aria-label="Fermer"></button>
                    {{ session('error') }}
                </div>
            @endif
            
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <a class="nav-link {{ request('archived') !== '1' ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                        Utilisateurs actifs
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request('archived') === '1' ? 'active' : '' }}" href="{{ route('admin.users.index', ['archived' => '1']) }}">
                        Utilisateurs archivés <span class="badge bg-secondary">{{ $archivedCount }}</span>
                    </a>
                </li>
            </ul>
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ request('archived') === '1' ? 'Liste des utilisateurs archivés' : 'Liste des utilisateurs actifs' }}</h5>
                    <div>
                        <a href="{{ route('admin.users.create') }}" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-circle"></i> Ajouter un utilisateur
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <form action="{{ route('admin.users.index') }}" method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                                @if(request('archived') === '1')
                                    <input type="hidden" name="archived" value="1">
                                @endif
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
                                    <a href="{{ route('admin.users.index', request('archived') === '1' ? ['archived' => '1'] : []) }}" class="btn btn-outline-secondary d-flex align-items-center">
                                        <i class="bi bi-x-circle me-1"></i> Réinitialiser
                                    </a>
                                @endif
                                <span class="ms-auto text-muted">{{ $users->total() }} utilisateur(s)</span>
                            </form>
                        </div>
                    </div>
                    
                    @if($users->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Photo</th>
                                        <th>Nom</th>
                                        <th>Email</th>
                                        <th>Rôle</th>
                                        <th>Heures mensuelles</th>
                                        <th>Date d'inscription</th>
                                        @if(request('archived') === '1')
                                            <th>Date d'archivage</th>
                                        @endif
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($users as $user)
                                        <tr>
                                            <td>
                                                <div class="user-photo-container" onclick="showPhotoModal('{{ $user->id }}')">
                                                    @if($user->profile_photo_path)
                                                        <img src="{{ strpos($user->profile_photo_path, 'photos/') === 0 ? asset($user->profile_photo_path) : Storage::url($user->profile_photo_path) }}" 
                                                            alt="Photo de {{ $user->name }}" 
                                                            class="user-profile-photo"
                                                            onerror="this.onerror=null; this.src='{{ asset('img/default-profile.png') }}'; console.error('Image non trouvée');">
                                                    @else
                                                        <div class="profile-photo-placeholder">
                                                            <i class="bi bi-person-circle"></i>
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>{{ $user->name }}</td>
                                            <td>{{ $user->email }}</td>
                                            <td>{{ $user->is_admin ? 'Administrateur' : 'Employé' }}</td>
                                            <td>
                                                @if($user->contracts->isNotEmpty() && $user->contracts->first()->data && $user->contracts->first()->data->monthly_hours)
                                                    {{ $user->contracts->first()->data->monthly_hours }}
                                                @else
                                                    Non défini
                                                @endif
                                            </td>
                                            <td>{{ $user->created_at ? $user->created_at->format('d/m/Y') : 'Non spécifiée' }}</td>
                                            @if(request('archived') === '1')
                                                <td>{{ $user->archived_at ? $user->archived_at->format('d/m/Y') : 'Non spécifiée' }}</td>
                                            @endif
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('admin.users.show', $user) }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Voir les détails">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-secondary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    
                                                    @if(request('archived') === '1')
                                                        <!-- Bouton pour désarchiver -->
                                                        <button type="button" class="btn btn-sm btn-success" 
                                                                onclick="event.preventDefault(); 
                                                                if(confirm('Êtes-vous sûr de vouloir désarchiver cet utilisateur ?')) 
                                                                document.getElementById('unarchive-form-{{ $user->id }}').submit();">
                                                            <i class="bi bi-box-arrow-up"></i>
                                                        </button>
                                                        <form id="unarchive-form-{{ $user->id }}" action="{{ route('admin.users.unarchive', $user) }}" method="POST" class="d-none">
                                                            @csrf
                                                            @method('PUT')
                                                        </form>
                                                    @else
                                                        <!-- Bouton pour archiver -->
                                                        @if($user->id !== auth()->id())
                                                            <button type="button" class="btn btn-sm btn-warning" 
                                                                    onclick="event.preventDefault(); 
                                                                    if(confirm('Êtes-vous sûr de vouloir archiver cet utilisateur ?')) 
                                                                    document.getElementById('archive-form-{{ $user->id }}').submit();">
                                                                <i class="bi bi-archive"></i>
                                                            </button>
                                                            <form id="archive-form-{{ $user->id }}" action="{{ route('admin.users.archive', $user) }}" method="POST" class="d-none">
                                                                @csrf
                                                                @method('PUT')
                                                            </form>
                                                        @endif
                                                    @endif
                                                    
                                                    <!-- Bouton pour supprimer -->
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
                        
                        <!-- Pagination -->
                        <div class="d-flex justify-content-center mt-4">
                            {{ $users->links() }}
                        </div>
                    @else
                        <div class="alert alert-info position-relative">
                            <h5>Aucun utilisateur trouvé</h5>
                            <p>{{ request('archived') === '1' ? 'Il n\'y a pas d\'utilisateurs archivés.' : 'Il n\'y a pas encore d\'utilisateurs dans le système.' }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour afficher la photo de profil en grand -->
<div class="modal fade" id="photoModal" tabindex="-1" aria-labelledby="photoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="photoModalLabel">Photo de profil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modalUserPhoto" src="" alt="Photo de profil" class="img-fluid">
            </div>
        </div>
    </div>
</div>

<style>
    .user-photo-container {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        overflow: hidden;
        display: flex;
        justify-content: center;
        align-items: center;
        cursor: pointer;
        background-color: #f0f0f0;
    }
    
    .user-profile-photo {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .profile-photo-placeholder {
        width: 100%;
        height: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 1.5rem;
        color: #6c757d;
    }
</style>

<script>
    function showPhotoModal(userId) {
        // Récupérer les utilisateurs depuis la collection Laravel
        const users = {!! $users->toJson() !!};
        // Trouver l'utilisateur par ID
        const user = users.data.find(u => u.id == userId);
        
        const modal = new bootstrap.Modal(document.getElementById('photoModal'));
        
        if (user && user.profile_photo_path) {
            let photoUrl;
            
            // Vérifier si le chemin commence par 'photos/'
            if (user.profile_photo_path.startsWith('photos/')) {
                photoUrl = '/' + user.profile_photo_path;
            } else {
                photoUrl = '/storage/' + user.profile_photo_path;
            }
            
            document.getElementById('modalUserPhoto').src = photoUrl;
            document.getElementById('photoModalLabel').textContent = 'Photo de ' + user.name;
            
            // Ajouter une gestion d'erreur de chargement d'image
            const img = document.getElementById('modalUserPhoto');
            img.onerror = function() {
                console.error('Erreur de chargement de l\'image:', photoUrl);
                this.src = '/img/default-profile.png';
                document.getElementById('photoModalLabel').textContent = 'Photo de ' + user.name + ' (par défaut)';
            };
            
            modal.show();
        } else {
            // Si l'utilisateur n'a pas de photo, afficher une photo par défaut
            document.getElementById('modalUserPhoto').src = '/img/default-profile.png';
            document.getElementById('photoModalLabel').textContent = 'Photo de profil non disponible';
            modal.show();
        }
    }
</script>
@endsection 