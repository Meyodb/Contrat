<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContractTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TemplateController extends Controller
{
    /**
     * Affiche la liste des modèles de contrats
     */
    public function index()
    {
        $templates = ContractTemplate::latest()->paginate(10);
        return view('admin.templates.index', compact('templates'));
    }

    /**
     * Affiche le formulaire de création d'un nouveau modèle
     */
    public function create()
    {
        return view('admin.templates.create');
    }

    /**
     * Enregistre un nouveau modèle
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'template_file' => 'required|file|mimes:docx,doc',
        ]);

        // Stockage du fichier
        $path = $request->file('template_file')->store('templates');

        // Création du modèle
        ContractTemplate::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'file_path' => $path,
        ]);

        return redirect()->route('admin.templates.index')
            ->with('success', 'Modèle de contrat créé avec succès');
    }

    /**
     * Affiche les détails d'un modèle
     */
    public function show(ContractTemplate $template)
    {
        return view('admin.templates.show', compact('template'));
    }

    /**
     * Affiche le formulaire d'édition d'un modèle
     */
    public function edit(ContractTemplate $template)
    {
        return view('admin.templates.edit', compact('template'));
    }

    /**
     * Met à jour un modèle
     */
    public function update(Request $request, ContractTemplate $template)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'template_file' => 'nullable|file|mimes:docx,doc',
        ]);

        // Données à mettre à jour
        $data = [
            'name' => $validated['name'],
            'description' => $validated['description'],
        ];

        // Si un nouveau fichier est fourni, le stocker
        if ($request->hasFile('template_file')) {
            // Supprimer l'ancien fichier
            if ($template->file_path) {
                Storage::delete($template->file_path);
            }
            
            // Stocker le nouveau fichier
            $path = $request->file('template_file')->store('templates');
            $data['file_path'] = $path;
        }

        // Mise à jour du modèle
        $template->update($data);

        return redirect()->route('admin.templates.index')
            ->with('success', 'Modèle de contrat mis à jour avec succès');
    }

    /**
     * Supprime un modèle
     */
    public function destroy(ContractTemplate $template)
    {
        // Vérifier si le modèle est utilisé par des contrats
        if ($template->contracts()->count() > 0) {
            return back()->with('error', 'Ce modèle ne peut pas être supprimé car il est utilisé par des contrats existants.');
        }

        // Supprimer le fichier
        if ($template->file_path) {
            Storage::delete($template->file_path);
        }

        // Supprimer le modèle
        $template->delete();

        return redirect()->route('admin.templates.index')
            ->with('success', 'Modèle de contrat supprimé avec succès');
    }

    /**
     * Télécharge le fichier du modèle
     */
    public function download(ContractTemplate $template)
    {
        if (!$template->file_path || !Storage::exists($template->file_path)) {
            return back()->with('error', 'Le fichier du modèle n\'existe pas.');
        }

        return Storage::download($template->file_path, Str::slug($template->name) . '.docx');
    }
}
