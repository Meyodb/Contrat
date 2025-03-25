<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class ContractSignedByAdmin extends Notification implements ShouldQueue
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
        $startDate = $contractData && $contractData->contract_start_date ? $contractData->contract_start_date->format('d/m/Y') : 'Non définie';
        
        return (new MailMessage)
            ->subject('✅ Votre contrat a été signé par l\'administrateur')
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('Nous avons le plaisir de vous informer que votre contrat **"' . $this->contract->title . '"** a été signé par notre équipe administrative.')
            ->line(new HtmlString('Voici un récapitulatif de votre contrat :<br>
                <ul>
                    <li><strong>Référence</strong>: ' . $this->contract->title . '</li>
                    <li><strong>Date de début</strong>: ' . $startDate . '</li>
                    <li><strong>Statut</strong>: Signé par les deux parties</li>
                </ul>'))
            ->line('Le contrat est maintenant finalisé et vous pouvez le télécharger depuis votre espace personnel.')
            ->action('Accéder à mon contrat', route('employee.contracts.show', $this->contract))
            ->line('Si vous avez des questions concernant votre contrat, n\'hésitez pas à contacter notre service des ressources humaines.')
            ->line('Nous vous souhaitons une excellente collaboration au sein de notre entreprise.')
            ->salutation('Cordialement, l\'équipe RH');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $contractData = $this->contract->data;
        $startDate = $contractData && $contractData->contract_start_date ? $contractData->contract_start_date->format('d/m/Y') : 'Non définie';
        
        return [
            'contract_id' => $this->contract->id,
            'title' => $this->contract->title,
            'message' => 'Contrat signé par l\'administrateur',
            'start_date' => $startDate,
            'status' => 'completed',
            'signed_at' => now()->format('d/m/Y H:i'),
            'admin_name' => $this->contract->admin ? $this->contract->admin->name : 'Administrateur'
        ];
    }
} 