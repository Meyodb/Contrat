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
Auth::routes();

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
    Route::get('/contracts/{contract}/sign', [AdminContractController::class, 'showSignForm'])->name('contracts.sign.form');
    Route::post('/contracts/{contract}/sign', [AdminContractController::class, 'sign'])->name('contracts.sign');
    Route::post('/contracts/{contract}/reject', [AdminContractController::class, 'reject'])->name('contracts.reject');
    Route::delete('/contracts/{contract}/bank-details', [AdminContractController::class, 'deleteBankDetails'])->name('contracts.bank-details.delete');
    
    // Routes pour les templates - désactivées
    // Route::resource('templates', AdminTemplateController::class);
    // Route::get('/templates/{template}/download', [AdminTemplateController::class, 'download'])->name('templates.download');
    
    // Routes pour les utilisateurs
    Route::resource('users', AdminUserController::class)->except(['show']);
    
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

// Routes pour les photos
Route::get('/employee-photos/{filename}', [PhotoController::class, 'showEmployeePhoto'])->name('employee.photo');

// Routes pour les signatures
Route::get('/signatures/{filename}', [SignatureController::class, 'showSignature'])->name('signature');
