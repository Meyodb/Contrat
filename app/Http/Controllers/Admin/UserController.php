<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Storage;
use App\Models\ContractData;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * Affiche la liste des utilisateurs
     */
    public function index(Request $request)
    {
        // Synchronisation automatique des photos de profil
        if (!$request->has('no_sync') && !session()->has('photos_synced')) {
            $this->syncProfilePhotos(true);
            // Marquer comme synchronisé pour cette session
            session(['photos_synced' => true]);
        }
        
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
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'is_admin' => 'nullable|boolean',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_admin' => $request->has('is_admin'),
        ];

        // Gérer la photo de profil si elle existe
        if ($request->hasFile('profile_photo')) {
            $path = $request->file('profile_photo')->store('profile-photos', 'public');
            $userData['profile_photo_path'] = str_replace('public/', '', $path);
        }

        $user = User::create($userData);

        // Assigner le rôle à l'utilisateur
        if (method_exists($user, 'assignRole')) {
            if ($request->has('is_admin')) {
                $user->assignRole('admin');
            } else {
                $user->assignRole('employee');
            }
        }

        return redirect()->route('admin.users.index')->with('status', 'Utilisateur créé avec succès!');
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
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'is_admin' => 'nullable|boolean',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'is_admin' => $request->has('is_admin'),
        ];

        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        if ($request->hasFile('profile_photo')) {
            // Supprimer l'ancienne photo si elle existe
            if ($user->profile_photo_path) {
                Storage::delete('public/' . $user->profile_photo_path);
            }
            
            // Stocker la nouvelle photo
            $path = $request->file('profile_photo')->store('profile-photos', 'public');
            $userData['profile_photo_path'] = str_replace('public/', '', $path);
        }

        $user->update($userData);

        // Gérer les rôles si vous utilisez Spatie Permission
        if (method_exists($user, 'syncRoles')) {
            if ($request->has('is_admin')) {
                $user->syncRoles(['admin']);
            } else {
                $user->syncRoles(['employee']);
            }
        }

        return redirect()->route('admin.users.index')->with('status', 'Utilisateur mis à jour avec succès.');
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

        // Suppression en cascade
        try {
            DB::beginTransaction();
            
            // 1. Récupérer tous les contrats de l'utilisateur
            $contracts = $user->contracts;
            
            foreach ($contracts as $contract) {
                // 2. Supprimer les fichiers de signatures d'employé
                if ($contract->employee_signature) {
                    Storage::delete('public/' . $contract->employee_signature);
                }
                
                // 3. Supprimer les données de contrat et les photos liées
                if ($contract->data) {
                    if ($contract->data->photo_path) {
                        Storage::delete('public/' . $contract->data->photo_path);
                    }
                    $contract->data->delete();
                }
                
                // 4. Supprimer les aperçus PDF du contrat
                $previewPath = 'public/contracts/previews/contrat_' . $contract->id . '_*';
                foreach (Storage::files($previewPath) as $file) {
                    Storage::delete($file);
                }
                
                // 5. Supprimer le contrat PDF final
                $contractPath = 'public/contracts/contrat_' . $contract->id . '_*';
                foreach (Storage::files($contractPath) as $file) {
                    Storage::delete($file);
                }
                
                // 6. Supprimer le contrat
                $contract->delete();
            }
            
            // 7. Supprimer la photo de profil
            if ($user->profile_photo_path) {
                Storage::delete('public/' . $user->profile_photo_path);
            }
            
            // 8. Supprimer la signature stockée
            $signaturePath = 'public/signatures/' . $user->id . '_employee.png';
            if (Storage::exists($signaturePath)) {
                Storage::delete($signaturePath);
            }
            
            // 9. Finalement, supprimer l'utilisateur
            $user->delete();
            
            DB::commit();
            
            return redirect()->route('admin.users.index')
                ->with('status', 'Utilisateur et toutes ses données associées supprimés avec succès.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la suppression de l\'utilisateur: ' . $e->getMessage());
            return back()->with('error', 'Une erreur est survenue lors de la suppression de l\'utilisateur. Veuillez réessayer.');
        }
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

    /**
     * Synchronise les photos d'identité des contrats avec les photos de profil utilisateur
     */
    public function syncProfilePhotos($silent = false)
    {
        $contractData = ContractData::whereNotNull('photo_path')->get();
        $count = 0;
        
        foreach ($contractData as $data) {
            if ($data->contract && $data->contract->user && $data->photo_path) {
                $user = $data->contract->user;
                
                // Toujours mettre à jour la photo de profil, même si l'utilisateur en a déjà une
                $user->update([
                    'profile_photo_path' => $data->photo_path
                ]);
                $count++;
                Log::info('Photo de profil mise à jour pour: ' . $user->name);
            }
        }
        
        if ($silent) {
            return $count;
        }
        
        return redirect()->back()->with('status', 'Photos de profil synchronisées. ' . $count . ' utilisateurs mis à jour.');
    }
}
