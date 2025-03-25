<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class ContractReminder extends Notification implements ShouldQueue
{
    use Queueable;

    protected $contract;
    protected $daysRemaining;

    /**
     * Create a new notification instance.
     */
    public function __construct(Contract $contract, int $daysRemaining = 3)
    {
        $this->contract = $contract;
        $this->daysRemaining = $daysRemaining;
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
        $urgencyLevel = $this->daysRemaining <= 1 ? 'haute' : ($this->daysRemaining <= 3 ? 'moyenne' : 'basse');
        $subject = $this->daysRemaining <= 1 ? '⚠️ URGENT : Votre contrat attend votre signature' : '⏰ Rappel : Votre contrat attend votre signature';
        
        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting('Bonjour ' . $notifiable->name . ',');
            
        if ($this->daysRemaining <= 1) {
            $mail->line('**Ceci est un rappel urgent.** Votre contrat **"' . $this->contract->title . '"** attend toujours votre signature.');
            $mail->line('Veuillez signer votre contrat dans les plus brefs délais pour finaliser votre embauche.');
        } else {
            $mail->line('Nous vous rappelons que votre contrat **"' . $this->contract->title . '"** est prêt à être signé.');
            $mail->line('Il vous reste **' . $this->daysRemaining . ' jours** pour le signer.');
        }
        
        return $mail
            ->line(new HtmlString('Informations sur le contrat :<br>
                <ul>
                    <li><strong>Référence</strong>: ' . $this->contract->title . '</li>
                    <li><strong>Statut</strong>: En attente de signature</li>
                    <li><strong>Urgence</strong>: ' . ucfirst($urgencyLevel) . '</li>
                </ul>'))
            ->action('Signer mon contrat maintenant', route('employee.contracts.show', $this->contract))
            ->line('Si vous rencontrez des difficultés pour signer votre contrat, n\'hésitez pas à contacter notre service des ressources humaines.')
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
            'message' => 'Rappel : Votre contrat attend votre signature',
            'days_remaining' => $this->daysRemaining,
            'urgency' => $this->daysRemaining <= 1 ? 'high' : ($this->daysRemaining <= 3 ? 'medium' : 'low'),
            'sent_at' => now()->format('d/m/Y H:i')
        ];
    }
}
