<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Custom CSS -->
    <link href="{{ asset('css/custom.css') }}" rel="stylesheet">

    <!-- Signature Pad CSS -->
    <style>
        .signature-pad-container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
        }
        #signature-pad {
            border: 1px solid #ccc;
            border-radius: 4px;
            width: 100%;
            height: 200px;
            background-color: white;
        }
        .signature-pad-actions {
            margin-top: 10px;
            text-align: right;
        }
    </style>

</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md shadow-sm">
            <div class="container">
                <a class="navbar-brand" href="{{ url('/') }}">
                    {{ config('app.name', 'Laravel') }}
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav me-auto">

                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ms-auto">
                        <!-- Authentication Links -->
                        @guest
                            @if (Route::has('login'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('login') }}">{{ __('Connexion') }}</a>
                                </li>
                            @endif

                            @if (Route::has('register'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('register') }}">{{ __('Inscription') }}</a>
                                </li>
                            @endif
                        @else
                            <!-- Liens de navigation pour les utilisateurs authentifiés -->
                            @auth
                                <!-- Liens pour administrateurs -->
                                @if(Auth::check() && Auth::user()->is_admin)
                                    <li class="nav-item">
                                        <a class="nav-link {{ Request::is('admin/dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                                            <i class="bi bi-speedometer2"></i> Tableau de bord
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ Request::is('admin/users*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                                            <i class="bi bi-people"></i> Utilisateurs
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ Request::is('admin/contracts*') ? 'active' : '' }}" href="{{ route('admin.contracts.index') }}">
                                            <i class="bi bi-file-earmark-text"></i> Contrats
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ Request::is('admin/templates*') ? 'active' : '' }}" href="{{ route('admin.templates.index') }}">
                                            <i class="bi bi-file-earmark-ruled"></i> Modèles
                                        </a>
                                    </li>
                                @else
                                    <li class="nav-item">
                                        <a class="nav-link" href="{{ route('employee.contracts.index') }}">Mes contrats</a>
                                    </li>
                                @endif
                            @endauth
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                    @if(Auth::check() && Auth::user()->is_admin)
                                        <i class="bi bi-person-badge me-1"></i>
                                    @else
                                        <i class="bi bi-person me-1"></i>
                                    @endif
                                    {{ Auth::check() ? Auth::user()->name : 'Utilisateur' }}
                                </a>

                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    @if(Auth::check() && Auth::user()->is_admin)
                                        <a class="dropdown-item" href="{{ route('admin.profile.show') }}">
                                            <i class="bi bi-person"></i> Mon profil
                                        </a>
                                    @endif
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                        <i class="bi bi-box-arrow-right"></i> {{ __('Déconnexion') }}
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main class="py-4">
            <!-- Section des notifications -->
            <div class="container">
                @if(session('success'))
                    <div class="alert alert-success position-relative mb-4">
                        <button type="button" class="btn-close position-absolute" style="top: 10px; right: 10px; font-size: 0.8rem;" data-bs-dismiss="alert" aria-label="Fermer"></button>
                        {{ session('success') }}
                    </div>
                @endif
                
                @if(session('error'))
                    <div class="alert alert-danger position-relative mb-4">
                        <button type="button" class="btn-close position-absolute" style="top: 10px; right: 10px; font-size: 0.8rem;" data-bs-dismiss="alert" aria-label="Fermer"></button>
                        {{ session('error') }}
                    </div>
                @endif
                
                @if(session('status'))
                    <div class="alert alert-info position-relative mb-4">
                        <button type="button" class="btn-close position-absolute" style="top: 10px; right: 10px; font-size: 0.8rem;" data-bs-dismiss="alert" aria-label="Fermer"></button>
                        {{ session('status') }}
                    </div>
                @endif
                
                @if(session('warning'))
                    <div class="alert alert-warning position-relative mb-4">
                        <button type="button" class="btn-close position-absolute" style="top: 10px; right: 10px; font-size: 0.8rem;" data-bs-dismiss="alert" aria-label="Fermer"></button>
                        {{ session('warning') }}
                    </div>
                @endif
            </div>
            <!-- Fin de la section des notifications -->
            
            @yield('content')
        </main>
        
        <footer class="footer mt-auto">
            <div class="container">
                <div class="row">
                    <div class="col-md-6">
                        <h5>{{ config('app.name', 'Laravel') }}</h5>
                        <p>Plateforme de gestion de contrats</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <p>&copy; {{ date('Y') }} Soup&Juice - Tous droits réservés</p>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Signature Pad Library -->
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.5/dist/signature_pad.umd.min.js"></script>
    
    <!-- Custom JavaScript -->
    @if(file_exists(public_path('js/custom.js')))
        <script src="{{ asset('js/custom.js') }}"></script>
    @endif
    
    @yield('scripts')
    @stack('scripts')
</body>
</html>
