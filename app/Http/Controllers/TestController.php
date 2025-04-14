<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Http\Controllers\Employee\ContractController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class TestController extends Controller
{
    /**
     * Test the PDF generation of a contract
     */
    public function testContractPdf($id = null)
    {
        // Si aucun ID n'est fourni, on prend le premier contrat disponible
        if (!$id) {
            $contract = Contract::first();
        } else {
            $contract = Contract::find($id);
        }

        // Si aucun contrat n'est trouvé, on retourne une erreur
        if (!$contract) {
            return response()->json(['error' => 'Aucun contrat trouvé'], 404);
        }

        // Charger les données du contrat
        $contractData = [
            'contract' => $contract,
            'user' => $contract->user,
            'admin' => $contract->admin,
            'data' => $contract->data,
            'admin_signature' => $contract->admin_signature,
            'employee_signature' => $contract->employee_signature
        ];

        // Générer le HTML avec le template Blade
        $html = view('pdf.cdi-template', $contractData)->render();

        // Créer le PDF
        $pdf = PDF::loadHTML($html);

        // Télécharger le PDF
        return $pdf->download($contract->title . '.pdf');
    }

    /**
     * List all contracts for testing
     */
    public function listContracts()
    {
        $contracts = Contract::with('user')->get();
        
        $formattedContracts = $contracts->map(function ($contract) {
            return [
                'id' => $contract->id,
                'title' => $contract->title,
                'user' => $contract->user ? $contract->user->name : 'N/A',
                'status' => $contract->status,
                'created_at' => $contract->created_at->format('Y-m-d H:i'),
                'test_url' => route('test.contract.pdf', ['id' => $contract->id])
            ];
        });

        return response()->json($formattedContracts);
    }

    /**
     * Créer un employé et un contrat de test avec des données complètes
     */
    public function createTestData()
    {
        try {
            // Vérifier si l'utilisateur de test existe déjà
            $user = \App\Models\User::where('email', 'test.employee@example.com')->first();
            
            if (!$user) {
                // Créer un nouvel utilisateur (employé)
                $user = new \App\Models\User();
                $user->name = 'Test Employé';
                $user->email = 'test.employee@example.com';
                $user->password = bcrypt('password');
                $user->is_admin = false;
                $user->save();
            }
            
            // Trouver un administrateur pour le contrat
            $admin = \App\Models\User::where('is_admin', true)->first();
            
            if (!$admin) {
                // Créer un administrateur si aucun n'existe
                $admin = new \App\Models\User();
                $admin->name = 'Test Admin';
                $admin->email = 'test.admin@example.com';
                $admin->password = bcrypt('password');
                $admin->is_admin = true;
                $admin->save();
            }
            
            // Trouver un modèle de contrat
            $template = \App\Models\ContractTemplate::first();
            
            if (!$template) {
                return $this->errorResponse('template', new \Exception('Aucun modèle de contrat trouvé dans la base de données'));
            }
            
            // Créer un nouveau contrat de test
            $contract = new \App\Models\Contract();
            $contract->title = 'Contrat CDI Test';
            $contract->user_id = $user->id;
            $contract->admin_id = $admin->id;
            $contract->contract_template_id = $template->id;
            $contract->status = 'draft';
            $contract->save();
            
            // Ajouter des données au contrat
            $contractData = new \App\Models\ContractData();
            $contractData->contract_id = $contract->id;
            
            // Définir les propriétés une par une pour détecter où se produit l'erreur
            try { $contractData->first_name = 'Jean'; } 
            catch (\Exception $e) { return $this->errorResponse('first_name', $e); }
            
            try { $contractData->last_name = 'Dupont'; } 
            catch (\Exception $e) { return $this->errorResponse('last_name', $e); }
            
            try { $contractData->full_name = 'Jean Dupont'; } 
            catch (\Exception $e) { return $this->errorResponse('full_name', $e); }
            
            try { $contractData->birth_date = '1985-05-15'; } 
            catch (\Exception $e) { return $this->errorResponse('birth_date', $e); }
            
            try { $contractData->birth_place = 'Paris'; } 
            catch (\Exception $e) { return $this->errorResponse('birth_place', $e); }
            
            try { $contractData->address = '123 Rue de la République'; } 
            catch (\Exception $e) { return $this->errorResponse('address', $e); }
            
            try { $contractData->email = 'jean.dupont@example.com'; } 
            catch (\Exception $e) { return $this->errorResponse('email', $e); }
            
            try { $contractData->phone = '0612345678'; } 
            catch (\Exception $e) { return $this->errorResponse('phone', $e); }
            
            try { $contractData->nationality = 'Française'; } 
            catch (\Exception $e) { return $this->errorResponse('nationality', $e); }
            
            try { $contractData->social_security_number = '1 85 05 69 123 456 78'; } 
            catch (\Exception $e) { return $this->errorResponse('social_security_number', $e); }
            
            try { $contractData->gender = 'M'; } 
            catch (\Exception $e) { return $this->errorResponse('gender', $e); }
            
            try { $contractData->contract_start_date = now()->addDays(15)->format('Y-m-d'); } 
            catch (\Exception $e) { return $this->errorResponse('contract_start_date', $e); }
            
            try { $contractData->monthly_gross_salary = 3500; } 
            catch (\Exception $e) { return $this->errorResponse('monthly_gross_salary', $e); }
            
            try { $contractData->weekly_hours = 35; } 
            catch (\Exception $e) { return $this->errorResponse('weekly_hours', $e); }
            
            try { $contractData->monthly_hours = 151.67; } 
            catch (\Exception $e) { return $this->errorResponse('monthly_hours', $e); }
            
            try { $contractData->trial_period_months = 2; } 
            catch (\Exception $e) { return $this->errorResponse('trial_period_months', $e); }
            
            try { $contractData->trial_period_end_date = now()->addDays(15)->addMonths(2)->format('Y-m-d'); } 
            catch (\Exception $e) { return $this->errorResponse('trial_period_end_date', $e); }
            
            try { $contractData->contract_signing_date = now()->format('Y-m-d'); } 
            catch (\Exception $e) { return $this->errorResponse('contract_signing_date', $e); }
            
            try {
                $contractData->save();
            } catch (\Exception $e) {
                return $this->errorResponse('save', $e);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Données de test créées avec succès',
                'user' => $user,
                'admin' => $admin,
                'template' => $template,
                'contract' => $contract,
                'contract_data' => $contractData,
                'test_pdf_url' => route('test.contract.pdf', ['id' => $contract->id])
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('global', $e);
        }
    }
    
    /**
     * Formater une réponse d'erreur
     */
    private function errorResponse($step, \Exception $e)
    {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la création des données de test',
            'step' => $step,
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
}
