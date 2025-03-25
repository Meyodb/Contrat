@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Vérification de signature électronique</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <i class="bi {{ $result['valid'] ? 'bi-shield-check text-success' : 'bi-shield-exclamation text-danger' }}" style="font-size: 4rem;"></i>
                        
                        <h4 class="mt-3 {{ $result['valid'] ? 'text-success' : 'text-danger' }}">
                            {{ $result['valid'] ? 'Signature valide' : 'Signature invalide' }}
                        </h4>
                        
                        <p class="text-muted">{{ $result['message'] }}</p>
                    </div>
                    
                    @if($result['valid'])
                        <div class="alert alert-success">
                            <p class="mb-1">
                                Cette page confirme que la signature électronique est valide et n'a pas été modifiée depuis sa création.
                            </p>
                        </div>
                        
                        <div class="card mt-4">
                            <div class="card-header">
                                <h6 class="mb-0">Informations sur le document</h6>
                            </div>
                            <div class="card-body">
                                <p><strong>Document :</strong> Contrat {{ $result['contract']['title'] }}</p>
                                <p><strong>Statut :</strong> {{ $result['contract']['status'] }}</p>
                                <p><strong>Signataire :</strong> {{ $result['metadata']['user_name'] }}</p>
                                <p><strong>Date de signature :</strong> {{ $result['metadata']['timestamp'] }}</p>
                                <p><strong>Empreinte du document :</strong> <span class="text-monospace small">{{ $result['metadata']['document_hash'] }}</span></p>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <a href="{{ route('signature.certificate.download', $signature_id) }}" class="btn btn-primary">
                                <i class="bi bi-file-earmark-pdf"></i> Télécharger le certificat
                            </a>
                        </div>
                    @else
                        <div class="alert alert-danger">
                            <p class="mb-1">
                                La signature électronique n'a pas pu être vérifiée ou le document a été modifié depuis sa signature.
                            </p>
                        </div>
                    @endif
                    
                    <div class="mt-4 text-center">
                        <p class="text-muted small">
                            <i class="bi bi-info-circle"></i> 
                            Cette vérification de signature a été générée le {{ now()->format('d/m/Y à H:i:s') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 