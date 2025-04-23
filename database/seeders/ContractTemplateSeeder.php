<?php

namespace Database\Seeders;

use App\Models\ContractTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ContractTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer un modèle CDI s'il n'existe pas
        if (!ContractTemplate::where('name', 'CDI')->exists()) {
            ContractTemplate::create([
                'name' => 'CDI',
                'description' => 'Contrat à durée indéterminée',
                'file_path' => 'templates/cdi.docx',
                'is_active' => true,
            ]);
            
            $this->command->info('Modèle de contrat CDI créé avec succès.');
        } else {
            $this->command->info('Le modèle de contrat CDI existe déjà.');
        }
    }
} 