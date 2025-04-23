<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\CompanyInfo;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Créer les rôles si nécessaires
        $this->createRoles();

        // User::factory(10)->create();

        // Créer un utilisateur admin si nécessaire
        if (!User::where('email', 'admin@example.com')->exists()) {
            $admin = User::factory()->create([
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'is_admin' => true,
            ]);
            $admin->assignRole('admin');
        }

        // Créer un utilisateur employé si nécessaire
        if (!User::where('email', 'employee@example.com')->exists()) {
            $employee = User::factory()->create([
                'name' => 'Employee User',
                'email' => 'employee@example.com',
                'is_admin' => false,
            ]);
            $employee->assignRole('employee');
        }

        // Créer les informations de l'entreprise
        if (!CompanyInfo::count()) {
            CompanyInfo::create([
                'company_name' => 'S.A.R.L WHAT EVER',
                'address' => '54 avenue De Kléber',
                'postal_code' => '75016',
                'city' => 'PARIS',
                'siret' => '439 077 462 00026',
                'email' => 'contact@whatevercompany.com',
                'phone' => '01 23 45 67 89',
                'legal_form' => 'S.A.R.L',
                'share_capital' => '200 000 €',
                'vat_number' => 'FR12439077462',
                'rcs' => 'Paris B 439 077 462'
            ]);
        }

        $this->call([
            ContractTemplateSeeder::class,
        ]);
    }

    /**
     * Créer les rôles nécessaires pour l'application
     */
    private function createRoles(): void
    {
        // Créer le rôle admin s'il n'existe pas déjà
        if (!Role::where('name', 'admin')->exists()) {
            Role::create(['name' => 'admin']);
        }

        // Créer le rôle employee s'il n'existe pas déjà
        if (!Role::where('name', 'employee')->exists()) {
            Role::create(['name' => 'employee']);
        }
    }
}
