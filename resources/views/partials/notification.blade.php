{{-- 
    Partial pour afficher une notification
    Paramètres:
    - type: success, danger, warning, info
    - message: le message à afficher
    - dismissible: true/false (défaut: true)
    - icon: classe d'icône Bootstrap (optionnel)
--}}

<div class="alert alert-{{ $type ?? 'info' }} position-relative mb-3">
    @if($dismissible ?? true)
        <button type="button" class="btn-close position-absolute" style="top: 10px; right: 10px; font-size: 0.8rem;" data-bs-dismiss="alert" aria-label="Fermer"></button>
    @endif
    
    @if(isset($icon))
        <i class="bi bi-{{ $icon }} me-2"></i>
    @endif
    
    @if(isset($title))
        <h5 class="alert-heading">{{ $title }}</h5>
    @endif
    
    {{ $message }}
    
    @if(isset($slot) && !empty(trim($slot)))
        <div class="mt-2">
            {{ $slot }}
        </div>
    @endif
</div> 