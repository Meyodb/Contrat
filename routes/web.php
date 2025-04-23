<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ContractController as AdminContractController;
use App\Http\Controllers\Admin\TemplateController as AdminTemplateController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Employee\ContractController as EmployeeContractController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\SignatureController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Routes d'authentification
// Auth::routes();
// Routes d'authentification définies manuellement (remplacement de Auth::routes())
Route::get('login', [\App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [\App\Http\Controllers\Auth\LoginController::class, 'login']);
Route::post('logout', [\App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');

// Routes d'enregistrement
Route::get('register', [\App\Http\Controllers\Auth\RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('register', [\App\Http\Controllers\Auth\RegisterController::class, 'register']);

// Routes de réinitialisation de mot de passe
Route::get('password/reset', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('password/email', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('password/reset/{token}', [\App\Http\Controllers\Auth\ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('password/reset', [\App\Http\Controllers\Auth\ResetPasswordController::class, 'reset'])->name('password.update');

// Routes de confirmation d'email
Route::get('email/verify', [\App\Http\Controllers\Auth\VerificationController::class, 'show'])->name('verification.notice');
Route::get('email/verify/{id}/{hash}', [\App\Http\Controllers\Auth\VerificationController::class, 'verify'])->name('verification.verify');
Route::post('email/resend', [\App\Http\Controllers\Auth\VerificationController::class, 'resend'])->name('verification.resend');

// Route /home qui redirige les administrateurs vers leur tableau de bord
Route::get('/home', function() {
    if (Auth::check() && Auth::user()->is_admin) {
        return redirect()->route('admin.dashboard');
    }
    
    return app(HomeController::class)->index();
})->name('home');

// Routes pour les administrateurs
Route::middleware(['auth', \App\Http\Middleware\AdminMiddleware::class])->name('admin.')->prefix('admin')->group(function() {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    
    // Routes pour les contrats
    Route::resource('contracts', AdminContractController::class);
    Route::get('/contracts/{contract}/preview', [AdminContractController::class, 'preview'])->name('contracts.preview');
    Route::get('/contracts/{contract}/download', [AdminContractController::class, 'download'])->name('contracts.download');
    Route::get('/contracts/{contract}/sign', [AdminContractController::class, 'sign'])->name('contracts.sign');
    Route::post('/contracts/{contract}/reject', [AdminContractController::class, 'reject'])->name('contracts.reject');
    Route::delete('/contracts/{contract}/bank-details', [AdminContractController::class, 'deleteBankDetails'])->name('contracts.delete-bank-details');
    Route::get('/fix-photos', [AdminContractController::class, 'fixPhotos'])->name('contracts.fix-photos');
    
    // Routes pour les avenants
    Route::get('/contracts/{contract}/create-avenant', [AdminContractController::class, 'showCreateAvenantForm'])->name('contracts.create-avenant');
    Route::post('/contracts/{contract}/create-avenant', [AdminContractController::class, 'storeAvenant'])->name('contracts.store-avenant');
    Route::post('/contracts/{contract}/avenant/preview', [AdminContractController::class, 'previewAvenant'])->name('contracts.avenant.preview');
    
    // Routes pour les employés avec contrats finalisés
    Route::get('/employees/finalized', [AdminUserController::class, 'finalizedContracts'])->name('employees.finalized');
    
    // Routes pour les utilisateurs
    Route::resource('users', AdminUserController::class);
    Route::put('/users/{user}/archive', [AdminUserController::class, 'archive'])->name('users.archive');
    Route::put('/users/{user}/unarchive', [AdminUserController::class, 'unarchive'])->name('users.unarchive');
    Route::get('/sync-profile-photos', [AdminUserController::class, 'syncProfilePhotos'])->name('users.sync.profile.photos');
    
    // Routes pour le profil administrateur
    Route::get('/profile', [App\Http\Controllers\Admin\ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [App\Http\Controllers\Admin\ProfileController::class, 'update'])->name('profile.update');
});

// Routes pour les employés
Route::middleware(['auth', \App\Http\Middleware\EmployeeMiddleware::class])->prefix('employee')->name('employee.')->group(function () {
    // Routes pour les contrats
    Route::resource('contracts', \App\Http\Controllers\Employee\ContractController::class);
    Route::get('contracts/{contract}/preview', [\App\Http\Controllers\Employee\ContractController::class, 'preview'])->name('contracts.preview');
    Route::get('contracts/{contract}/download', [\App\Http\Controllers\Employee\ContractController::class, 'download'])->name('contracts.download');
    Route::post('contracts/{contract}/sign', [\App\Http\Controllers\Employee\ContractController::class, 'sign'])->name('contracts.sign');
    Route::post('contracts/{contract}/submit', [\App\Http\Controllers\Employee\ContractController::class, 'submit'])->name('contracts.submit');
    
    // Routes pour les avenants
    Route::get('contracts/{contract}/avenants', [\App\Http\Controllers\Employee\ContractController::class, 'contractAvenants'])->name('contracts.avenants');
    Route::get('avenants/{avenant}', [\App\Http\Controllers\Employee\ContractController::class, 'showAvenant'])->name('avenants.show');
    
    // Routes pour le profil
    Route::get('profile', [\App\Http\Controllers\Employee\ProfileController::class, 'show'])->name('profile.show');
    Route::put('profile', [\App\Http\Controllers\Employee\ProfileController::class, 'update'])->name('profile.update');
});

// Route temporaire pour tester la génération de contrat
Route::get('/test-contract-generation/{contract}', function(\App\Models\Contract $contract) {
    $controller = new \App\Http\Controllers\Admin\ContractController();
    return $controller->generateDocument($contract);
})->name('test.contract.generation');

// Routes de test pour le PDF du contrat (sans middleware d'authentification)
Route::get('/test-contract-pdf/{id?}', [\App\Http\Controllers\TestController::class, 'testContractPdf'])
    ->name('test.contract.pdf')
    ->withoutMiddleware(['auth', 'verified']);
    
Route::get('/test-contracts-list', [\App\Http\Controllers\TestController::class, 'listContracts'])
    ->name('test.contracts.list')
    ->withoutMiddleware(['auth', 'verified']);
    
Route::get('/test-pdf-page', function() {
    return view('test.pdf-tester');
})->name('test.pdf.page')->withoutMiddleware(['auth', 'verified']);

// Routes pour les tests de PDF
Route::prefix('tests')->name('test.')->group(function () {
    Route::get('/pdf-tester', [App\Http\Controllers\TestController::class, 'index'])->name('pdf.tester');
    Route::get('/create-test-data', [App\Http\Controllers\TestController::class, 'createTestData'])->name('create.data');
    Route::get('/contracts-list', [App\Http\Controllers\TestController::class, 'contractsList'])->name('contracts.list');
    Route::get('/contract-pdf/{contract?}', [App\Http\Controllers\TestController::class, 'generatePdf'])->name('contract.pdf');
});

// Route pour migrer les signatures vers le nouveau format
Route::get('/migrate-signatures', function() {
    $helper = new \App\Temp_Fixes\SignatureHelper();
    $stats = $helper->migrateSignatures();
    return response()->json([
        'message' => 'Migration des signatures terminée',
        'stats' => $stats
    ]);
})->middleware(['auth', \App\Http\Middleware\AdminMiddleware::class])->name('migrate-signatures');

// Routes pour les photos
Route::get('/profile-photos/{filename}', [PhotoController::class, 'showProfilePhoto'])->name('profile.photo');

// Routes pour les signatures
Route::get('/signatures/{filename}', [SignatureController::class, 'showSignature'])->name('signature');
Route::get('/fix-signature-permissions', [SignatureController::class, 'fixSignaturePermissions'])->middleware(['auth'])->name('fix.signature.permissions');
Route::get('/signatures/admin/{filename}', [SignatureController::class, 'showSignature'])->name('signature.admin');
Route::get('/signatures/employees/{filename}', [SignatureController::class, 'showSignature'])->name('signature.employee');

Route::get('/generate-signature/{user_id}', function($userId) {
    try {
        $helper = new \App\Temp_Fixes\SignatureHelper();
        $userId = (int)$userId;
        
        // Vérifier si l'utilisateur existe
        $user = \App\Models\User::find($userId);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé'
            ], 404);
        }
        
        // Créer une signature par défaut
        $img = imagecreatetruecolor(300, 100);
        $background = imagecolorallocate($img, 255, 255, 255);
        $textcolor = imagecolorallocate($img, 0, 0, 0);
        
        imagefilledrectangle($img, 0, 0, 300, 100, $background);
        imagestring($img, 3, 50, 30, "Signature " . $user->name, $textcolor);
        imageline($img, 50, 60, 250, 60, $textcolor);
        imageline($img, 50, 60, 100, 80, $textcolor);
        imageline($img, 100, 80, 150, 40, $textcolor);
        imageline($img, 150, 40, 200, 70, $textcolor);
        imageline($img, 200, 70, 250, 60, $textcolor);
        
        // Créer les répertoires
        $employeeDir = storage_path('app/public/signatures/employees');
        if (!file_exists($employeeDir)) {
            if (!file_exists(storage_path('app/public/signatures'))) {
                mkdir(storage_path('app/public/signatures'), 0755, true);
            }
            mkdir($employeeDir, 0755, true);
        }
        
        // Sauvegarder l'image
        ob_start();
        imagepng($img);
        $imageData = ob_get_clean();
        imagedestroy($img);
        
        // Enregistrer avec l'API Storage
        \Illuminate\Support\Facades\Storage::put("public/signatures/employees/{$userId}.png", $imageData);
        \Illuminate\Support\Facades\Storage::put("public/signatures/{$userId}_employee.png", $imageData);
        
        // Mise à jour de la base de données si nécessaire
        if ($user) {
            $contract = \App\Models\Contract::where('user_id', $userId)
                ->whereIn('status', ['admin_signed', 'employee_signed'])
                ->first();
                
            if ($contract) {
                $contract->update([
                    'employee_signature' => "public/signatures/employees/{$userId}.png",
                ]);
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Signature générée avec succès',
            'paths' => [
                "public/signatures/employees/{$userId}.png",
                "public/signatures/{$userId}_employee.png"
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la génération de la signature',
            'error' => $e->getMessage()
        ], 500);
    }
})->name('generate.signature');

// Route pour ajuster les permissions de signature
Route::get('/fix-permissions', function() {
    try {
        // Désactiver le rapport d'erreurs pour éviter les erreurs de permissions
        $oldErrorReporting = error_reporting(0);
        
        $result = [];
        
        // Lister les chemins à corriger
        $paths = [
            storage_path('app/public'),
            storage_path('app/public/signatures'),
            storage_path('app/public/signatures/admin'),
            storage_path('app/public/signatures/employees'),
        ];
        
        foreach ($paths as $path) {
            if (file_exists($path)) {
                $result[] = "Dossier trouvé: " . $path;
                @chmod($path, 0777);
                $result[] = "Permission actualisée: " . substr(sprintf('%o', fileperms($path)), -4);
            } else {
                $result[] = "Dossier non trouvé: " . $path;
                try {
                    mkdir($path, 0777, true);
                    $result[] = "Dossier créé: " . $path;
                } catch (\Exception $e) {
                    $result[] = "Erreur création dossier: " . $e->getMessage();
                }
            }
        }
        
        // Chercher tous les fichiers PNG dans les dossiers signatures
        $pngFiles = [
            storage_path('app/public/signatures/admin/admin_signature.png'),
        ];
        
        // Ajouter les signatures des employés
        try {
            $users = \App\Models\User::all();
            foreach ($users as $user) {
                $pngFiles[] = storage_path('app/public/signatures/employees/' . $user->id . '.png');
            }
        } catch (\Exception $e) {
            $result[] = "Erreur lors de la récupération des utilisateurs: " . $e->getMessage();
        }
        
        foreach ($pngFiles as $file) {
            if (file_exists($file)) {
                $result[] = "Fichier trouvé: " . $file;
                @chmod($file, 0777);
                $result[] = "Permission fichier: " . substr(sprintf('%o', fileperms($file)), -4);
            }
        }
        
        // Vérifier le lien symbolique
        if (!file_exists(public_path('storage'))) {
            $result[] = "Lien symbolique manquant, tentative de création...";
            try {
                \Illuminate\Support\Facades\Artisan::call('storage:link');
                $result[] = "Lien symbolique créé avec succès";
            } catch (\Exception $e) {
                $result[] = "Erreur lors de la création du lien symbolique: " . $e->getMessage();
            }
        } else {
            $result[] = "Lien symbolique existant: " . public_path('storage');
        }
        
        // Restaurer le rapport d'erreurs
        error_reporting($oldErrorReporting);
        
        return response()->json([
            'success' => true,
            'message' => 'Tentative de correction des permissions terminée',
            'results' => $result
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la correction des permissions',
            'error' => $e->getMessage()
        ], 500);
    }
})->name('fix.all.permissions');

// Route pour copier la signature dans le dossier public
Route::get('/public-signature/{user_id}', function($userId) {
    try {
        $helper = new \App\Temp_Fixes\SignatureHelper();
        $userId = (int)$userId;
        
        // Vérifier si l'utilisateur existe
        $user = \App\Models\User::find($userId);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé'
            ], 404);
        }
        
        // Chemins source potentiels
        $sourcePaths = [
            storage_path('app/public/signatures/employees/' . $userId . '.png'),
            storage_path('app/public/signatures/' . $userId . '_employee.png')
        ];
        
        $sourceFound = false;
        $sourceContent = null;
        
        foreach ($sourcePaths as $sourcePath) {
            if (file_exists($sourcePath)) {
                $sourceFound = true;
                $sourceContent = file_get_contents($sourcePath);
                break;
            }
        }
        
        // Si aucune signature n'est trouvée, en créer une
        if (!$sourceFound || !$sourceContent) {
            // Créer une signature par défaut
            $img = imagecreatetruecolor(300, 100);
            $background = imagecolorallocate($img, 255, 255, 255);
            $textcolor = imagecolorallocate($img, 0, 0, 0);
            
            imagefilledrectangle($img, 0, 0, 300, 100, $background);
            imagestring($img, 3, 50, 30, "Signature " . $user->name, $textcolor);
            imageline($img, 50, 60, 250, 60, $textcolor);
            imageline($img, 50, 60, 100, 80, $textcolor);
            imageline($img, 100, 80, 150, 40, $textcolor);
            
            // Capturer le contenu de l'image
            ob_start();
            imagepng($img);
            $sourceContent = ob_get_clean();
            imagedestroy($img);
        }
        
        // Définir le chemin de destination dans le dossier public
        $publicPath = public_path('signatures');
        if (!file_exists($publicPath)) {
            mkdir($publicPath, 0777, true);
        }
        
        // Copier le fichier vers le dossier public
        $destPath = $publicPath . '/' . $userId . '.png';
        file_put_contents($destPath, $sourceContent);
        
        // S'assurer que le fichier est accessible
        @chmod($destPath, 0777);
        
        // Créer la route URL pour accéder à la signature
        $signatureUrl = url('signatures/' . $userId . '.png');
        
        // Mettre à jour le contrat si nécessaire
        $contract = \App\Models\Contract::where('user_id', $userId)
            ->whereIn('status', ['admin_signed', 'employee_signed', 'completed'])
            ->orderBy('id', 'desc')
            ->first();
            
        if ($contract) {
            $contract->update([
                'employee_signature' => 'signatures/' . $userId . '.png',
            ]);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Signature copiée dans le dossier public',
            'url' => $signatureUrl,
            'contract_updated' => $contract ? true : false
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false, 
            'message' => 'Erreur lors de la copie de la signature',
            'error' => $e->getMessage()
        ], 500);
    }
})->name('public.signature');

// Test routes
Route::get('/test/signature', function () {
    return view('test.signature-tester');
})->name('test.signature');

Route::post('/test/save-signature', function (Request $request) {
    // Journaliser la requête pour le débogage
    Log::info('Requête de signature reçue', [
        'content_type' => $request->header('Content-Type'),
        'method' => $request->method(),
        'has_signature' => $request->has('signature')
    ]);
    
    try {
        // Récupérer la signature depuis la requête (formulaire standard)
        $signature = $request->input('signature');
        $userId = $request->input('user_id', 10); // Utiliser 10 comme ID par défaut pour le test
        
        // Journaliser la signature reçue
        Log::info('Signature reçue', [
            'user_id' => $userId,
            'signature_length' => strlen($signature ?? ''),
            'signature_start' => substr($signature ?? '', 0, 50) . '...'
        ]);
        
        // Vérifier si la signature est présente
        if (!$signature) {
            Log::warning('Aucune signature reçue');
            return response()->json([
                'success' => false,
                'message' => 'Aucune signature fournie.'
            ]);
        }
        
        // Vérifier si le format est correct (base64 image)
        if (!Str::startsWith($signature, 'data:image')) {
            Log::warning('Format de signature invalide', [
                'start' => substr($signature, 0, 100) . '...'
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Format de signature invalide. La signature doit commencer par data:image.'
            ]);
        }
        
        // Extraire les données base64 de l'image
        list($type, $data) = explode(';', $signature);
        list(, $data) = explode(',', $data);
        $decodedImage = base64_decode($data);
        
        // Définir le chemin de sauvegarde
        $timestamp = now()->format('Ymd_His');
        $filename = "signature_{$userId}_{$timestamp}.png";
        $directory = 'signatures';
        $publicPath = public_path($directory);
        
        // Créer le répertoire s'il n'existe pas
        if (!File::exists($publicPath)) {
            File::makeDirectory($publicPath, 0755, true);
            Log::info('Répertoire de signatures créé', ['path' => $publicPath]);
        }
        
        // Sauvegarder l'image dans le répertoire public
        $path = "{$publicPath}/{$filename}";
        file_put_contents($path, $decodedImage);
        Log::info('Signature sauvegardée', ['path' => $path]);
        
        // Essayer également de sauvegarder avec le Storage facade pour comparaison
        try {
            Storage::disk('public')->put("{$directory}/{$filename}", $decodedImage);
            Log::info('Signature sauvegardée également avec Storage');
        } catch (\Exception $e) {
            Log::warning('Échec de sauvegarde avec Storage', ['error' => $e->getMessage()]);
        }
        
        // Construire l'URL pour l'accès à l'image
        $url = url("{$directory}/{$filename}");
        
        // Retourner une réponse de succès avec l'URL de l'image
        return response()->json([
            'success' => true,
            'message' => 'Signature enregistrée avec succès.',
            'url' => $url,
            'timestamp' => now()->toIso8601String()
        ]);
        
    } catch (\Exception $e) {
        // Journaliser l'erreur
        Log::error('Erreur lors de la sauvegarde de la signature', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        
        // Retourner une réponse d'erreur
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la sauvegarde de la signature: ' . $e->getMessage()
        ], 500);
    }
})->name('test.save.signature');

/*
 * Routes temporaires de maintenance
 */
Route::prefix('maintenance')->middleware(['auth', \App\Http\Middleware\AdminMiddleware::class])->group(function () {
    // Régénérer toutes les signatures par défaut
    Route::get('/regenerate-signatures', function() {
        try {
            $helper = new \App\Temp_Fixes\SignatureHelper();
            
            // Recréer la signature admin
            $adminPath = $helper->createAdminSignature();
            
            // Récupérer tous les utilisateurs
            $users = \App\Models\User::all();
            $count = 0;
            
            foreach ($users as $user) {
                // Supprimer les anciennes signatures
                $oldSignaturePaths = [
                    storage_path('app/public/signatures/employees/' . $user->id . '.png'),
                    public_path('signatures/' . $user->id . '.png'),
                    storage_path('app/public/signatures/' . $user->id . '_employee.png'),
                    storage_path('app/public/signatures/employee-' . $user->id . '.png'),
                ];
                
                foreach ($oldSignaturePaths as $path) {
                    if (file_exists($path)) {
                        unlink($path);
                    }
                }
                
                // Créer une nouvelle signature par défaut
                $signaturePath = $helper->createDefaultSignature($user->id);
                if ($signaturePath) {
                    $count++;
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => "Signatures régénérées: $count utilisateurs + admin",
                'admin_signature' => $adminPath
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    })->name('maintenance.regenerate-signatures');
});
