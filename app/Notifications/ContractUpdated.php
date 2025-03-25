<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class ContractUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    protected $contract;
    protected $changes;

    /**
     * Create a new notification instance.
     */
    public function __construct(Contract $contract, array $changes = [])
    {
        $this->contract = $contract;
        $this->changes = $changes;
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
            ->subject('🔄 Votre contrat a été mis à jour')
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('Nous vous informons que votre contrat **"' . $this->contract->title . '"** a été mis à jour par notre équipe administrative.');
        
        if (!empty($this->changes)) {
            $changesHtml = '<ul>';
            foreach ($this->changes as $field => $value) {
                $changesHtml .= '<li><strong>' . $field . '</strong>: ' . $value . '</li>';
            }
            $changesHtml .= '</ul>';
            
            $mail->line(new HtmlString('Voici les modifications apportées :<br>' . $changesHtml));
        }
        
        return $mail
            ->line('Veuillez consulter votre contrat pour vérifier les informations mises à jour.')
            ->action('Voir mon contrat', route('employee.contracts.show', $this->contract))
            ->line('Si vous avez des questions concernant ces modifications, n\'hésitez pas à contacter notre service des ressources humaines.')
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
            'message' => 'Votre contrat a été mis à jour',
            'changes' => $this->changes,
            'updated_at' => now()->format('d/m/Y H:i'),
            'admin_name' => $this->contract->admin ? $this->contract->admin->name : 'Administrateur'
        ];
    }
} 