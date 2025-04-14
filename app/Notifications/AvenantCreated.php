<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AvenantCreated extends Notification
{
    use Queueable;

    /**
     * Le contrat associé à cette notification.
     *
     * @var \App\Models\Contract
     */
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
        return (new MailMessage)
                    ->subject('Avenant à votre contrat de travail')
                    ->greeting('Bonjour ' . $notifiable->name . ',')
                    ->line('Un avenant à votre contrat de travail a été créé et nécessite votre signature.')
                    ->line('Il s\'agit de l\'avenant n°' . $this->contract->avenant_number . ' qui modifie vos conditions de travail.')
                    ->action('Voir l\'avenant', route('employee.contracts.show', $this->contract))
                    ->line('Merci de prendre connaissance de cet avenant et de le signer électroniquement dans les plus brefs délais.');
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
            'message' => 'Un avenant à votre contrat de travail (n°' . $this->contract->avenant_number . ') a été créé et nécessite votre signature.',
            'action_url' => route('employee.contracts.show', $this->contract),
        ];
    }
} 