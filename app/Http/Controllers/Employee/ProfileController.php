<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\ContractData;

class ProfileController extends Controller
{
    /**
     * Affiche le profil de l'employé
     */
    public function show()
    {
        $user = Auth::user();
        // Récupérer le contrat principal de l'employé avec les données associées
        $contract = $user->contracts()->where('contract_type', '!=', 'avenant')->with('data')->first();
        
        return view('employee.profile.show', compact('user', 'contract'));
    }
    
    /**
     * Met à jour le profil de l'employé
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
        
        $userData = [
            'name' => $request->name,
            'email' => $request->email,
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
        
        return redirect()->route('employee.profile.show')
            ->with('status', 'Profil mis à jour avec succès.');
    }
} 