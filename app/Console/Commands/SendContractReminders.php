<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Notifications\ContractReminder;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendContractReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contracts:send-reminders {--days=3 : Nombre de jours avant de considérer un contrat comme urgent}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envoie des rappels aux employés qui n\'ont pas encore signé leur contrat';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $this->info("Recherche des contrats en attente de signature depuis plus de {$days} jours...");
        
        // Récupérer les contrats qui sont en attente de signature (admin_signed) depuis plus de X jours
        $pendingContracts = Contract::where('status', 'admin_signed')
            ->where('admin_signed_at', '<=', Carbon::now()->subDays($days))
            ->get();
            
        $this->info("Nombre de contrats trouvés : " . $pendingContracts->count());
        
        $bar = $this->output->createProgressBar($pendingContracts->count());
        $bar->start();
        
        $sentCount = 0;
        
        foreach ($pendingContracts as $contract) {
            // Calculer le nombre de jours écoulés depuis la signature par l'admin
            $daysSinceAdminSigned = Carbon::parse($contract->admin_signed_at)->diffInDays(Carbon::now());
            
            // Déterminer l'urgence en fonction du nombre de jours
            $daysRemaining = max(1, 7 - $daysSinceAdminSigned); // Considérer qu'il y a 7 jours pour signer
            
            // Envoyer la notification
            $contract->user->notify(new ContractReminder($contract, $daysRemaining));
            
            $sentCount++;
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info("Rappels envoyés : {$sentCount}");
        
        return Command::SUCCESS;
    }
}
