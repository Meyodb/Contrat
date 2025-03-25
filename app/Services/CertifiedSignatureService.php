<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Writer;

class CertifiedSignatureService
{
    /**
     * Génère une signature certifiée pour un contrat
     *
     * @param Contract $contract Le contrat à signer
     * @param User $user L'utilisateur qui signe
     * @param string $signatureImage L'image de la signature au format base64
     * @return array Informations sur la signature certifiée
     */
    public function signContract(Contract $contract, User $user, string $signatureImage)
    {
        try {
            // 1. Générer un identifiant unique pour la signature
            $signatureId = Str::uuid()->toString();
            
            // 2. Extraire la partie image du base64
            $imageData = explode(',', $signatureImage)[1] ?? '';
            if (empty($imageData)) {
                throw new \Exception("Donnée d'image de signature invalide");
            }
            
            // 3. Créer le hash du document (contrat + données)
            $documentHash = $this->generateDocumentHash($contract);
            
            // 4. Horodatage
            $timestamp = now()->format('Y-m-d H:i:s');
            $timeReference = now()->timestamp;
            
            // 5. Enregistrer les métadonnées de signature
            $signatureMetadata = [
                'signature_id' => $signatureId,
                'contract_id' => $contract->id,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'document_hash' => $documentHash,
                'timestamp' => $timestamp,
                'time_reference' => $timeReference,
                'ip_address' => request()->ip()
            ];
            
            // 6. Générer le certificat de signature (stocké en JSON pour le moment)
            Storage::put('private/signatures/metadata/'. $signatureId .'.json', json_encode($signatureMetadata, JSON_PRETTY_PRINT));
            
            // 7. Enregistrer l'image de la signature
            // Utilisateur admin ou employé ?
            $filename = $user->is_admin 
                ? 'admin_signature.png' 
                : $user->id . '_' . $signatureId . '.png';
                
            $signaturePath = 'signatures/' . $filename;
            Storage::disk('public')->put($signaturePath, base64_decode($imageData));
            
            // 8. Générer un QR code de vérification
            $verificationUrl = route('verify.signature', ['id' => $signatureId]);
            $qrCodePath = $this->generateQRCode($verificationUrl, $signatureId);
            
            // 9. Retourner les données de la signature
            return [
                'signature_id' => $signatureId,
                'document_hash' => $documentHash,
                'signature_path' => $signaturePath,
                'timestamp' => $timestamp,
                'signature_url' => asset('storage/' . $signaturePath),
                'verification_url' => $verificationUrl,
                'qr_code_path' => $qrCodePath
            ];
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de la signature certifiée : ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Génère un hash unique pour le document
     */
    private function generateDocumentHash(Contract $contract)
    {
        // Collecter toutes les données importantes du contrat pour créer un hash unique
        $data = [
            'contract_id' => $contract->id,
            'template_id' => $contract->template_id,
            'title' => $contract->title,
            'created_at' => $contract->created_at->format('Y-m-d H:i:s'),
            'user_id' => $contract->user_id,
        ];
        
        // Ajouter les données du contrat si elles existent
        if ($contract->data) {
            $contractData = $contract->data->toArray();
            // Exclure les timestamps et les IDs pour se concentrer sur les données réelles
            unset($contractData['id'], $contractData['created_at'], $contractData['updated_at']);
            $data['contract_data'] = $contractData;
        }
        
        // Créer une représentation JSON des données et générer le hash
        $jsonData = json_encode($data);
        return hash('sha256', $jsonData);
    }
    
    /**
     * Génère un QR code pour la vérification de la signature
     */
    private function generateQRCode(string $url, string $signatureId)
    {
        try {
            // Créer le répertoire pour les QR codes s'il n'existe pas
            $qrCodeDir = storage_path('app/public/signatures/qrcodes');
            if (!file_exists($qrCodeDir)) {
                mkdir($qrCodeDir, 0755, true);
            }
            
            // Créer le renderer pour le QR code
            $renderer = new ImageRenderer(
                new RendererStyle(200),
                new ImagickImageBackEnd()
            );
            
            $writer = new Writer($renderer);
            $qrCodePath = 'signatures/qrcodes/' . $signatureId . '.png';
            $fullPath = storage_path('app/public/' . $qrCodePath);
            
            // Générer et enregistrer le QR code
            $writer->writeFile($url, $fullPath);
            
            return $qrCodePath;
        } catch (\Exception $e) {
            Log::error('Erreur lors de la génération du QR code : ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Vérifie une signature certifiée
     */
    public function verifySignature(string $signatureId)
    {
        try {
            // Charger les métadonnées de la signature
            $metadataPath = 'private/signatures/metadata/'. $signatureId .'.json';
            
            if (!Storage::exists($metadataPath)) {
                return [
                    'valid' => false,
                    'message' => 'Signature non trouvée'
                ];
            }
            
            $metadata = json_decode(Storage::get($metadataPath), true);
            
            // Vérifier si le contrat existe toujours
            $contract = Contract::find($metadata['contract_id']);
            if (!$contract) {
                return [
                    'valid' => false,
                    'message' => 'Contrat associé non trouvé'
                ];
            }
            
            // Régénérer le hash du document et comparer
            $currentHash = $this->generateDocumentHash($contract);
            $originalHash = $metadata['document_hash'];
            
            $hashValid = ($currentHash === $originalHash);
            
            return [
                'valid' => $hashValid,
                'message' => $hashValid ? 'Signature valide' : 'Le document a été modifié après la signature',
                'metadata' => $metadata,
                'contract' => [
                    'id' => $contract->id,
                    'title' => $contract->title,
                    'status' => $contract->status,
                    'user' => $contract->user->name ?? 'Utilisateur inconnu',
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Erreur lors de la vérification de la signature : ' . $e->getMessage());
            return [
                'valid' => false,
                'message' => 'Erreur lors de la vérification : ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Génère un certificat PDF pour une signature
     */
    public function generateCertificate(string $signatureId)
    {
        try {
            // Vérifier la signature
            $verification = $this->verifySignature($signatureId);
            if (!$verification['valid']) {
                throw new \Exception('Impossible de générer un certificat pour une signature invalide');
            }
            
            // Préparer les données pour le certificat
            $data = [
                'signature_id' => $signatureId,
                'verification' => $verification,
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'qr_code' => asset('storage/signatures/qrcodes/' . $signatureId . '.png')
            ];
            
            // Générer le PDF du certificat
            $pdf = \PDF::loadView('pdf.signature-certificate', $data);
            
            // Enregistrer le certificat
            $certificatePath = 'certificates/' . $signatureId . '.pdf';
            Storage::disk('public')->put($certificatePath, $pdf->output());
            
            return asset('storage/' . $certificatePath);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la génération du certificat : ' . $e->getMessage());
            throw $e;
        }
    }
} 