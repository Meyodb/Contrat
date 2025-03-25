<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FixMigrationStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Vérifier si la migration existe déjà
        $exists = DB::table('migrations')
            ->where('migration', '2025_03_13_212006_add_photo_path_to_contract_data_table')
            ->exists();
        
        // Si elle n'existe pas, l'ajouter
        if (!$exists) {
            DB::table('migrations')->insert([
                'migration' => '2025_03_13_212006_add_photo_path_to_contract_data_table',
                'batch' => 14,
            ]);
            
            $this->command->info('Migration 2025_03_13_212006_add_photo_path_to_contract_data_table marquée comme terminée.');
        } else {
            $this->command->info('La migration existe déjà dans la table migrations.');
        }
    }
} 