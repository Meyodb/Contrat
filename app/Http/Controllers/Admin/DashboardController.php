<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Affiche le tableau de bord administrateur
     */
    public function index()
    {
        // Statistiques des contrats par statut
        $contractsByStatus = Contract::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status')
            ->toArray();
            
        // Contrats récents
        $recentContracts = Contract::with(['user', 'template'])
            ->latest()
            ->take(5)
            ->get();
            
        // Nombre total d'employés
        $totalEmployees = User::where('is_admin', false)->count();
        
        // Contrats nécessitant une action
        $pendingContracts = Contract::whereIn('status', ['submitted', 'employee_signed'])
            ->with(['user', 'template'])
            ->latest()
            ->take(10)
            ->get();
            
        // Statistiques mensuelles (nombre de contrats par mois)
        $monthlyStats = Contract::select(DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'), DB::raw('count(*) as total'))
            ->whereYear('created_at', now()->year)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('total', 'month')
            ->toArray();
            
        return view('admin.dashboard', compact(
            'contractsByStatus', 
            'recentContracts', 
            'totalEmployees', 
            'pendingContracts',
            'monthlyStats'
        ));
    }
}
