<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Temp_Fixes\SignatureHelper;

class SignatureController extends Controller
{
    /**
     * SignatureHelper instance
     */
    protected $signatureHelper;
    
    /**
     * Chemins de signatures par type d'utilisateur
     */
    protected $signaturePaths = [
        'admin' => 'signatures/admin/admin_signature.png',
        'employee' => 'signatures/employees/{id}.png'
    ];
    
    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->signatureHelper = new SignatureHelper();
    }
    
    /**
     * Afficher la page des signatures
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Récupérer tous les utilisateurs
        $users = User::orderBy('lastname')->get();
        
        // Vérifier les signatures existantes
        $signatures = [];
        foreach ($users as $user) {
            // Déterminer le chemin de signature en fonction du rôle
            $signaturePath = str_replace('{id}', $user->id, $this->signaturePaths['employee']);
            
            // Vérifier si la signature existe
            $signatures[$user->id] = [
                'exists' => Storage::exists('public/' . $signaturePath),
                'path' => $signaturePath,
                'timestamp' => Storage::exists('public/' . $signaturePath) 
                    ? filemtime(storage_path('app/public/' . $signaturePath)) 
                    : null
            ];
        }
        
        // Renvoyer la vue avec les données
        return view('signatures.index', [
            'users' => $users,
            'signatures' => $signatures
        ]);
    }
    
    /**
     * Enregistrer une signature
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            // Valider les données
            $validatedData = $request->validate([
                'signature' => 'required|string',
                'user_id' => 'required|integer|exists:users,id',
            ]);
            
            // Déterminer le type de signature (admin ou employee)
            $user = User::findOrFail($validatedData['user_id']);
            $signatureType = $user->hasRole('admin') ? 'admin' : 'employee';
            
            // Utiliser SignatureHelper pour sauvegarder la signature
            $result = $this->signatureHelper->saveSignature(
                $validatedData['signature'],
                $signatureType,
                $validatedData['user_id']
            );
            
            // Vérifier le résultat
            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Échec de la sauvegarde de la signature'
                ], 500);
            }
            
            // Journaliser le succès
            Log::info('Signature enregistrée avec succès', [
                'user_id' => $validatedData['user_id'],
                'path' => $result,
                'by_user' => Auth::id()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Signature enregistrée avec succès',
                'path' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'enregistrement de la signature', [
                'error' => $e->getMessage(),
                'user_id' => $request->input('user_id', 'N/A')
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur s\'est produite lors de l\'enregistrement de la signature: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Convertir les signatures existantes vers le nouveau format
     *
     * @return \Illuminate\Http\Response
     */
    public function convertSignatures()
    {
        try {
            // Vérifier les permissions
            if (!Auth::user()->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'avez pas les permissions nécessaires pour cette action'
                ], 403);
            }
            
            // Utiliser la méthode de migration du SignatureHelper
            $stats = $this->signatureHelper->migrateSignatures();
            
            return response()->json([
                'success' => true,
                'message' => 'Conversion des signatures terminée',
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la conversion des signatures', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur s\'est produite lors de la conversion des signatures: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Corriger les permissions des fichiers de signature
     * 
     * @return \Illuminate\Http\Response
     */
    public function fixSignaturePermissions()
    {
        try {
            // Utiliser la méthode du SignatureHelper
            $stats = $this->signatureHelper->fixSignaturePermissions();
            
            // Log les résultats
            Log::info('Correction des permissions de signatures terminée', [
                'stats' => $stats
            ]);
            
            // Rediriger avec un message de succès
            return back()->with('success', 'Les permissions des signatures ont été corrigées avec succès. ' . 
                $stats['files_fixed'] . ' fichiers et ' . $stats['directories_fixed'] . ' répertoires ont été traités.');
                
        } catch (\Exception $e) {
            Log::error('Erreur lors de la correction des permissions de signatures', [
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Une erreur s\'est produite: ' . $e->getMessage());
        }
    }
    
    /**
     * Afficher une signature d'après son nom de fichier
     *
     * @param string $filename Le nom du fichier de la signature
     * @return \Illuminate\Http\Response
     */
    public function showSignature($filename)
    {
        try {
            Log::info('Demande d\'affichage de signature', ['filename' => $filename, 'route' => request()->route()->getName()]);
            
            // Déterminer le chemin du fichier selon le type de signature et la route utilisée
            $filePath = '';
            $routeName = request()->route()->getName();
            
            // Route spécifique admin
            if ($routeName === 'signature.admin' || $filename === 'admin_signature.png') {
                // Tester d'abord le chemin standard
                $filePath = 'public/' . $this->signaturePaths['admin'];
                
                // Si le fichier n'existe pas, essayer d'autres emplacements possibles
                if (!Storage::exists($filePath)) {
                    Log::warning('Signature admin non trouvée au chemin standard', ['path' => $filePath]);
                    
                    // Essayer dans le dossier signatures (racine)
                    $alternativePath = 'public/signatures/admin_signature.png';
                    if (Storage::exists($alternativePath)) {
                        $filePath = $alternativePath;
                        Log::info('Signature admin trouvée au chemin alternatif', ['path' => $filePath]);
                    }
                }
            } 
            // Route spécifique employé
            else if ($routeName === 'signature.employee') {
                // Chercher dans le dossier des signatures d'employés
                $filePath = 'public/signatures/employees/' . $filename;
            }
            // Route générique
            else {
                // Essayer d'abord comme signature d'employé
                $filePath = 'public/signatures/employees/' . $filename;
                
                // Si le fichier n'existe pas, essayer comme signature admin
                if (!Storage::exists($filePath) && $filename === 'admin_signature.png') {
                    $filePath = 'public/' . $this->signaturePaths['admin'];
                }
            }
            
            // Vérifier si le fichier existe
            if (!Storage::exists($filePath)) {
                Log::warning('Signature non trouvée', [
                    'filename' => $filename,
                    'path' => $filePath,
                    'route' => $routeName
                ]);
                return response()->file(public_path('images/no-signature.png'));
            }
            
            // Récupérer le contenu du fichier
            $fileContents = Storage::get($filePath);
            $mime = Storage::mimeType($filePath);
            
            Log::info('Signature trouvée et renvoyée', [
                'filename' => $filename,
                'path' => $filePath,
                'mime' => $mime,
                'route' => $routeName
            ]);
            
            // Renvoyer le fichier avec le bon type MIME
            return response($fileContents)
                ->header('Content-Type', $mime)
                ->header('Cache-Control', 'public, max-age=3600');
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'affichage de la signature', [
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);
            
            // En cas d'erreur, renvoyer une image par défaut
            return response()->file(public_path('images/no-signature.png'));
        }
    }
} 