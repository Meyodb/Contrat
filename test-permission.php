<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$kernel->bootstrap();

try {
    // Tester la classe Role
    $role = new Spatie\Permission\Models\Role();
    echo "Classe Role trouvée et instanciée avec succès\n";
    
    // Tester l'existence de la table roles
    $roles = DB::table('roles')->get();
    echo "Table roles accessible, nombre d'entrées: " . count($roles) . "\n";
    
    // Tester l'existence de la table model_has_roles
    $modelHasRoles = DB::table('model_has_roles')->get();
    echo "Table model_has_roles accessible, nombre d'entrées: " . count($modelHasRoles) . "\n";
    
    // Afficher les erreurs détaillées
    echo "Aucune erreur détectée\n";
} catch (\Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
} 