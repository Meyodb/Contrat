<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractSignedByEmployee extends Notification implements ShouldQueue
{
    use Queueable;

    protected $contract;

    /**
     * Create a new notification instance.
     */
    public function __construct(Contract $contract)
    {
        $this->contract = $contract;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $contractType = $this->contract->is_avenant ? 'avenant' : 'contrat';
        
        return (new MailMessage)
                    ->subject($this->contract->is_avenant ? 'Un avenant a été signé' : 'Un contrat a été signé')
                    ->greeting('Bonjour ' . $notifiable->name . ',')
                    ->line('L\'employé ' . $this->contract->user->name . ' a signé ' . $contractType . '.')
                    ->line('Le ' . $contractType . ' est maintenant complet et les signatures sont validées.')
                    ->action('Voir le ' . $contractType, route('admin.contracts.show', $this->contract))
                    ->line('Merci d\'utiliser notre application de gestion des contrats !');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'contract_id' => $this->contract->id,
            'title' => $this->contract->title,
            'message' => 'Le contrat a été signé par ' . $this->contract->user->name,
            'signed_at' => now()->format('d/m/Y H:i'),
        ];
    }
} 