@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="text-center mb-4">
                <h1 style="font-size: 32px; margin-bottom: 10px;">{{ date('Y') + 1 }}</h1>
                <h2 style="font-size: 24px; margin-bottom: 15px;">TABLEAU D'ÉMARGEMENT</h2>
                <p style="font-size: 14px; max-width: 800px; margin: 0 auto;">
                    Les soussignés reconnaissent, ce jour, avoir reçu de la société {{ config('app.name') }} un écrit constatant la décision unilatérale de la société relative aux
                    garanties collectives et obligatoires de « frais de santé », conformément à l'article L.911-1 du Code de la Sécurité Sociale.
                </p>
            </div>
            
            <div class="d-flex justify-content-end mb-3">
                <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Retour au tableau de bord
                </a>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0" style="font-size: 16px;">Liste des employés ayant finalisé leur contrat</h5>
                </div>
                <div class="card-body">
                    @if($users->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center" style="width: 33%; font-size: 14px;">NOM</th>
                                        <th class="text-center" style="width: 33%; font-size: 14px;">PRÉNOM</th>
                                        <th class="text-center" style="width: 34%; font-size: 14px;">SIGNATURE</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($users as $user)
                                        <tr style="height: 70px;">
                                            <td class="align-middle text-center" style="font-size: 14px;">
                                                {{ $user->contracts->first()->data->last_name ?? '' }}
                                            </td>
                                            <td class="align-middle text-center" style="font-size: 14px;">
                                                {{ $user->contracts->first()->data->first_name ?? '' }}
                                            </td>
                                            <td class="align-middle text-center">
                                                @if($user->contracts->first()->employee_signature)
                                                    @php
                                                        $contractId = $user->contracts->first()->id;
                                                        $employeeSignatureFilename = $contractId . '_employee.png';
                                                    @endphp
                                                    <img src="{{ asset('storage/signatures/' . $employeeSignatureFilename) }}" alt="Signature employé" style="max-height: 60px;">
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-3">
                            <a href="#" class="btn btn-primary btn-sm" onclick="window.print();">
                                <i class="bi bi-printer"></i> Imprimer le tableau
                            </a>
                        </div>
                    @else
                        <div class="alert alert-warning">
                            <h5 style="font-size: 16px;">Aucun employé avec contrat finalisé</h5>
                            <p style="font-size: 14px;">Il n'y a pas encore d'employés ayant finalisé leur contrat.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        body {
            padding: 0;
            margin: 0;
            font-size: 12px;
        }
        .container {
            width: 100%;
            max-width: 100%;
        }
        .btn, .alert, nav, footer, .d-flex {
            display: none !important;
        }
        .card {
            border: none;
        }
        .card-header, .card-body {
            padding: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
        }
        .text-center {
            text-align: center;
        }
        h1 {
            font-size: 24px !important;
            margin-bottom: 10px !important;
        }
        h2 {
            font-size: 18px !important;
            margin-bottom: 10px !important;
        }
        p {
            font-size: 12px !important;
            margin-bottom: 20px !important;
        }
    }
</style>
@endsection 