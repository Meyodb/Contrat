<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Services\CertifiedSignatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CertifiedSignatureController extends Controller
{
    protected $signatureService;
    
    public function __construct(CertifiedSignatureService $signatureService)
    {
        $this->signatureService = $signatureService;
    }
    
    /**
     * Affiche la page de vérification des signatures
     */
    public function verify($id)
    {
        $verificationResult = $this->signatureService->verifySignature($id);
        
        return view('signature.verify', [
            'result' => $verificationResult,
            'signature_id' => $id
        ]);
    }
    
    /**
     * Signe un contrat avec une signature certifiée pour l'employé
     */
    public function employeeSign(Request $request, Contract $contract)
    {
        // Validation
        $request->validate([
            'employee_signature' => 'required|string',
        ]);
        
        try {
            // Vérifier que l'utilisateur est bien celui associé au contrat
            if (Auth::id() !== $contract->user_id) {
                return back()->with('error', 'Vous n\'êtes pas autorisé à signer ce contrat.');
            }
            
            // Vérifier que le contrat est en attente de signature
            if ($contract->status !== 'admin_signed') {
                return back()->with('error', 'Ce contrat n\'est pas prêt à être signé.');
            }
            
            // Générer la signature certifiée
            $result = $this->signatureService->signContract(
                $contract, 
                Auth::user(), 
                $request->employee_signature
            );
            
            // Mettre à jour le contrat
            $contract->update([
                'employee_signature' => $result['signature_url'],
                'employee_signed_at' => now(),
                'status' => 'employee_signed',
                'signature_id' => $result['signature_id'],
                'document_hash' => $result['document_hash']
            ]);
            
            // Notification à l'admin
            if ($contract->admin) {
                $contract->admin->notify(new \App\Notifications\ContractSignedByEmployee($contract));
            }
            
            return redirect()->route('employee.contracts.show', $contract)
                ->with('success', 'Contrat signé avec succès. Un certificat de signature a été généré.');
                
        } catch (\Exception $e) {
            Log::error('Erreur lors de la signature du contrat: ' . $e->getMessage());
            return back()->with('error', 'Une erreur est survenue lors de la signature: ' . $e->getMessage());
        }
    }
    
    /**
     * Signe un contrat avec une signature certifiée pour l'administrateur
     */
    public function adminSign(Request $request, Contract $contract)
    {
        // Validation
        $request->validate([
            'admin_signature' => 'required|string',
        ]);
        
        try {
            // Vérifier que l'utilisateur est un administrateur
            if (!Auth::user()->is_admin) {
                return back()->with('error', 'Vous n\'êtes pas autorisé à signer ce contrat.');
            }
            
            // Vérifier que le contrat est en attente de signature administrative
            if ($contract->status !== 'submitted' && $contract->status !== 'in_review') {
                return back()->with('error', 'Ce contrat n\'est pas prêt à être signé.');
            }
            
            // Générer la signature certifiée
            $result = $this->signatureService->signContract(
                $contract, 
                Auth::user(), 
                $request->admin_signature
            );
            
            // Mettre à jour le contrat
            $contract->update([
                'admin_id' => Auth::id(),
                'admin_signature' => $result['signature_url'],
                'admin_signed_at' => now(),
                'status' => 'admin_signed',
                'admin_signature_id' => $result['signature_id'],
                'admin_document_hash' => $result['document_hash']
            ]);
            
            // Notification à l'employé
            $contract->user->notify(new \App\Notifications\ContractSignedByAdmin($contract));
            
            return redirect()->route('admin.contracts.index')
                ->with('success', 'Contrat signé avec succès. Un certificat de signature a été généré.');
                
        } catch (\Exception $e) {
            Log::error('Erreur lors de la signature du contrat: ' . $e->getMessage());
            return back()->with('error', 'Une erreur est survenue lors de la signature: ' . $e->getMessage());
        }
    }
    
    /**
     * Télécharge le certificat de signature
     */
    public function downloadCertificate($id)
    {
        try {
            $certificateUrl = $this->signatureService->generateCertificate($id);
            return redirect($certificateUrl);
        } catch (\Exception $e) {
            Log::error('Erreur lors du téléchargement du certificat: ' . $e->getMessage());
            return back()->with('error', 'Impossible de générer le certificat: ' . $e->getMessage());
        }
    }
} 