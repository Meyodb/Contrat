<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class ContractRejected extends Notification implements ShouldQueue
{
    use Queueable;

    protected $contract;
    protected $reason;

    /**
     * Create a new notification instance.
     */
    public function __construct(Contract $contract, string $reason = null)
    {
        $this->contract = $contract;
        $this->reason = $reason;
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
        $mail = (new MailMessage)
            ->subject('❌ Votre contrat a été rejeté')
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('Nous sommes désolés de vous informer que votre contrat **"' . $this->contract->title . '"** a été rejeté par notre équipe administrative.');
            
        if ($this->reason) {
            $mail->line('**Motif du rejet** : ' . $this->reason);
        }
        
        return $mail
            ->line(new HtmlString('Voici les détails du contrat :<br>
                <ul>
                    <li><strong>Référence</strong>: ' . $this->contract->title . '</li>
                    <li><strong>Date de soumission</strong>: ' . $this->contract->updated_at->format('d/m/Y') . '</li>
                    <li><strong>Statut actuel</strong>: Rejeté</li>
                </ul>'))
            ->line('Veuillez contacter notre service des ressources humaines pour discuter des modifications nécessaires ou pour obtenir plus d\'informations sur ce rejet.')
            ->action('Voir les détails du contrat', route('employee.contracts.show', $this->contract))
            ->salutation('Cordialement, l\'équipe RH');
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
            'message' => 'Votre contrat a été rejeté',
            'reason' => $this->reason,
            'rejected_at' => now()->format('d/m/Y H:i'),
            'status' => 'rejected',
            'admin_name' => $this->contract->admin ? $this->contract->admin->name : 'Administrateur'
        ];
    }
} 