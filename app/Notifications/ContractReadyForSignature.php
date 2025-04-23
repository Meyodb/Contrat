<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class ContractReadyForSignature extends Notification implements ShouldQueue
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
        $hourlyRate = $contractData && $contractData->hourly_rate ? number_format($contractData->hourly_rate, 2, ',', ' ') . ' €' : 'Non défini';
        $workHours = $contractData && $contractData->work_hours ? $contractData->work_hours . ' heures' : 'Non défini';
        
        return (new MailMessage)
            ->subject('📝 Votre contrat est prêt à être signé')
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('Nous avons le plaisir de vous informer que votre contrat **"' . $this->contract->title . '"** a été revu et signé par notre équipe administrative.')
            ->line(new HtmlString('Voici un récapitulatif des informations principales :<br>
                <ul>
                    <li><strong>Référence</strong>: ' . $this->contract->title . '</li>
                    <li><strong>Date de début</strong>: ' . $startDate . '</li>
                    <li><strong>Taux horaire</strong>: ' . $hourlyRate . '</li>
                    <li><strong>Heures de travail</strong>: ' . $workHours . '</li>
                </ul>'))
            ->line('Le contrat est maintenant prêt pour votre signature électronique. Veuillez vérifier attentivement toutes les informations avant de signer.')
            ->line('Pour signer le contrat, cliquez sur le bouton ci-dessous et suivez les instructions sur la page qui s\'affichera.')
            ->action('Signer mon contrat', route('employee.contracts.show', $this->contract))
            ->line('Si vous avez des questions ou si vous constatez des erreurs dans le contrat, veuillez contacter notre service des ressources humaines avant de signer.')
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
            'message' => 'Votre contrat est prêt à être signé',
            'start_date' => $startDate,
            'status' => 'admin_signed',
            'admin_signed_at' => $this->contract->admin_signed_at ? $this->contract->admin_signed_at->format('d/m/Y H:i') : now()->format('d/m/Y H:i'),
            'admin_name' => $this->contract->admin ? $this->contract->admin->name : 'Administrateur'
        ];
    }
} 