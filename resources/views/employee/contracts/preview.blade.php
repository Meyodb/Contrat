@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h3>Prévisualisation du contrat</h3>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h4>Signatures</h4>
                            <div class="row">
                                <div class="col-md-6 text-center">
                                    <p><strong>Signature de l'employeur</strong></p>
                                    <p>M BRIAND Grégory</p>
                                    <div id="admin-signature">
                                        @php
                                            // Vérifier si la signature admin existe ou si le contrat est dans un état signé par l'admin
                                            $adminSignaturePath = 'signatures/admin/admin_signature.png';
                                            $adminSignatureExists = \Storage::disk('public')->exists($adminSignaturePath) || 
                                                                  ($contract->status === 'admin_signed' || 
                                                                   $contract->status === 'employee_signed' || 
                                                                   $contract->status === 'completed');
                                        @endphp
                                        @if($adminSignatureExists)
                                            <img src="{{ route('signature.admin', ['filename' => 'admin_signature.png']) }}" alt="Signature de l'employeur" class="img-fluid" style="max-height: 100px;">
                                        @else
                                            <div style="width: 200px; height: 100px; border-bottom: 1px solid #000; display: inline-block;">
                                                <p class="text-center">(Signature à venir)</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6 text-center">
                                    <p><strong>Signature de l'employé</strong></p>
                                    <p>{{ $user->first_name ?? '' }} {{ $user->last_name ?? '' }}</p>
                                    <div id="employee-signature">
                                        @php
                                            $userId = $contract->user_id;
                                            $employeeSignaturePath = "signatures/employees/{$userId}.png";
                                        @endphp
                                        @if(Storage::disk('public')->exists($employeeSignaturePath))
                                            <img src="{{ asset('storage/' . $employeeSignaturePath) }}" alt="Signature de l'employé" class="img-fluid" style="max-height: 100px;">
                                        @else
                                            <div style="width: 200px; height: 100px; border-bottom: 1px solid #000; display: inline-block;">
                                                <p class="text-center">(Signature à venir)</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 text-center">
                            <a href="{{ route('employee.contracts.preview', $contract) }}" class="btn btn-primary" target="_blank">
                                <i class="bi bi-file-pdf"></i> Voir le PDF complet
                            </a>
                            <a href="{{ route('employee.contracts.show', $contract) }}" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Retour aux détails
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 