<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Affiche le profil de l'administrateur
     */
    public function show()
    {
        $user = Auth::user();
        return view('admin.profile', compact('user'));
    }

    /**
     * Met à jour le profil de l'administrateur
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        
        // Validation des données
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'signature_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'password' => $request->has('change_password') ? 'required|string|min:8|confirmed' : 'nullable',
        ]);

        // Mise à jour du nom et de l'email
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        
        // Traitement de l'image de signature
        if ($request->hasFile('signature_image')) {
            // Supprimer l'ancienne image si elle existe
            if ($user->signature_image) {
                Storage::disk('public')->delete($user->signature_image);
            }
            
            // Stocker la nouvelle image avec le nom fixe "admin_signature.png"
            $file = $request->file('signature_image');
            $filename = 'admin_signature.png';
            $path = Storage::disk('public')->putFileAs('signatures', $file, $filename);
            $user->signature_image = $path;
        }
        
        // Mise à jour du mot de passe si demandé
        if ($request->has('change_password') && $validated['password']) {
            $user->password = Hash::make($validated['password']);
        }
        
        $user->save();
        
        return redirect()->route('admin.profile.show')
            ->with('status', 'Profil mis à jour avec succès.');
    }
} 