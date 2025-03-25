<?php

namespace Database\Seeders;

use App\Models\ContractTemplate;
use Illuminate\Database\Seeder;

class ContractTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ContractTemplate::create([
            'name' => 'CDI Whatever',
            'description' => 'Modèle de contrat à durée indéterminée',
            'file_path' => 'templates/CDI Whatever.docx',
        ]);
    }
} 