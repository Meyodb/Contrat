<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

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
        $contractData = $this->contract->data;
        $employeeName = $this->contract->user->name;
        $employeeEmail = $contractData && $contractData->email ? $contractData->email : $this->contract->user->email;
        
        return (new MailMessage)
            ->subject('✅ Contrat signé par ' . $employeeName)
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('Le contrat **"' . $this->contract->title . '"** a été signé par ' . $employeeName . '.')
            ->line(new HtmlString('Voici les détails du contrat :<br>
                <ul>
                    <li><strong>Référence</strong>: ' . $this->contract->title . '</li>
                    <li><strong>Employé</strong>: ' . $employeeName . '</li>
                    <li><strong>Email</strong>: ' . $employeeEmail . '</li>
                    <li><strong>Date de signature</strong>: ' . now()->format('d/m/Y à H:i') . '</li>
                </ul>'))
            ->line('Le contrat est maintenant prêt pour la génération du document final.')
            ->action('Voir et finaliser le contrat', route('admin.contracts.show', $this->contract))
            ->line('Veuillez vérifier les informations et générer le document final pour compléter le processus.')
            ->salutation('Cordialement, le système de gestion des contrats');
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
            'employee_name' => $this->contract->user->name,
            'employee_email' => $this->contract->data && $this->contract->data->email ? $this->contract->data->email : $this->contract->user->email,
            'message' => 'Contrat signé par l\'employé',
            'signed_at' => now()->format('d/m/Y H:i'),
            'status' => 'employee_signed'
        ];
    }
} 