<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Affiche la liste des utilisateurs
     */
    public function index(Request $request)
    {
        $query = User::with(['contracts.data']);
        
        // Recherche par nom ou email
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        // Filtrage par statut d'archivage
        if ($request->has('archived')) {
            if ($request->archived === '1') {
                $query->where('archived', true);
            } else {
                $query->where('archived', false);
            }
        } else {
            // Par défaut, n'afficher que les utilisateurs non archivés
            $query->where('archived', false);
        }
        
        // Filtrage par rôle
        if ($request->has('role') && !empty($request->role)) {
            if ($request->role === 'admin') {
                $query->where('is_admin', true);
            } elseif ($request->role === 'employee') {
                $query->where('is_admin', false);
            }
        }
        
        // Tri
        if ($request->has('sort') && !empty($request->sort)) {
            switch ($request->sort) {
                case 'oldest':
                    $query->orderBy('created_at', 'asc');
                    break;
                case 'name_asc':
                    $query->orderBy('name', 'asc');
                    break;
                case 'name_desc':
                    $query->orderBy('name', 'desc');
                    break;
                case 'newest':
                default:
                    $query->orderBy('created_at', 'desc');
                    break;
            }
        } else {
            // Tri par défaut
            $query->orderBy('created_at', 'desc');
        }
        
        $users = $query->paginate(10)->withQueryString();
        
        // Récupérer le nombre d'utilisateurs archivés pour l'affichage dans le menu
        $archivedCount = User::where('archived', true)->count();
        
        return view('admin.users.index', compact('users', 'archivedCount'));
    }

    /**
     * Affiche le formulaire de création d'un nouvel utilisateur
     */
    public function create()
    {
        $roles = Role::all();
        return view('admin.users.create', compact('roles'));
    }

    /**
     * Enregistre un nouvel utilisateur
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'is_admin' => 'boolean',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_admin' => $validated['is_admin'] ?? false,
        ]);

        // Attribuer le rôle approprié
        if ($validated['is_admin'] ?? false) {
            $user->assignRole('admin');
        } else {
            $user->assignRole('employee');
        }

        return redirect()->route('admin.users.index')
            ->with('status', 'Utilisateur créé avec succès.');
    }

    /**
     * Affiche les détails d'un utilisateur
     */
    public function show(User $user)
    {
        $user->load('contract');
        return view('admin.users.show', compact('user'));
    }

    /**
     * Affiche le formulaire d'édition d'un utilisateur
     */
    public function edit(User $user)
    {
        $roles = Role::all();
        return view('admin.users.edit', compact('user', 'roles'));
    }

    /**
     * Met à jour un utilisateur
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'is_admin' => 'boolean',
        ]);

        $userData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_admin' => $validated['is_admin'] ?? false,
        ];

        if (!empty($validated['password'])) {
            $userData['password'] = Hash::make($validated['password']);
        }

        $user->update($userData);

        // Mettre à jour les rôles
        $user->syncRoles([]); // Supprimer tous les rôles actuels
        if ($validated['is_admin'] ?? false) {
            $user->assignRole('admin');
        } else {
            $user->assignRole('employee');
        }

        return redirect()->route('admin.users.index')
            ->with('status', 'Utilisateur mis à jour avec succès.');
    }

    /**
     * Supprime un utilisateur
     */
    public function destroy(User $user)
    {
        // Empêcher la suppression de son propre compte
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('status', 'Utilisateur supprimé avec succès.');
    }

    /**
     * Affiche la liste des employés avec contrats finalisés
     */
    public function finalizedContracts()
    {
        // Récupérer les employés ayant des contrats finalisés (signés par l'employé ou complétés)
        $users = User::whereHas('contracts', function($query) {
            $query->whereIn('status', ['employee_signed', 'completed']);
        })
        ->with(['contracts' => function($query) {
            $query->whereIn('status', ['employee_signed', 'completed'])
                  ->with('data');
        }])
        ->where('archived', false)
        ->where('is_admin', false)
        ->orderBy('name')
        ->get();

        return view('admin.users.finalized', compact('users'));
    }

    /**
     * Archive un utilisateur
     */
    public function archive(User $user)
    {
        // Empêcher l'archivage de son propre compte
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Vous ne pouvez pas archiver votre propre compte.');
        }

        $user->update([
            'archived' => true,
            'archived_at' => now(),
        ]);

        return redirect()->route('admin.users.index')
            ->with('status', 'Utilisateur archivé avec succès.');
    }

    /**
     * Désarchive un utilisateur
     */
    public function unarchive(User $user)
    {
        $user->update([
            'archived' => false,
            'archived_at' => null,
        ]);

        return redirect()->route('admin.users.index')
            ->with('status', 'Utilisateur désarchivé avec succès.');
    }
}
