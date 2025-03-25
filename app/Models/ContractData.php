<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ContractData extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'contract_id',
        // Informations personnelles
        'full_name',
        'first_name',
        'last_name',
        'gender',
        'birth_date',
        'birth_place',
        'nationality',
        'address',
        'postal_code',
        'city',
        'social_security_number',
        'email',
        'phone',
        'bank_details',
        'photo_path',
        
        // Informations contrat (admin)
        'work_hours',
        'hourly_rate',
        'contract_start_date',
        'contract_signing_date',
        'trial_period_months',
        'overtime_hours_20',
        
        // Champs calculés
        'monthly_hours',
        'weekly_hours',
        'monthly_gross_salary',
        'trial_period_end_date',
        'monthly_overtime',
        'weekly_overtime',
        
        // Champs temporaires pour compatibilité
        'field_value',
        'field_type',
        'section',
        'admin_only',
        'is_admin_field',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'birth_date' => 'date',
        'contract_start_date' => 'date',
        'contract_signing_date' => 'date',
        'trial_period_end_date' => 'date',
        'work_hours' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'monthly_hours' => 'decimal:2',
        'weekly_hours' => 'decimal:2',
        'monthly_gross_salary' => 'decimal:2',
        'overtime_hours_20' => 'decimal:2',
        'monthly_overtime' => 'decimal:2',
        'weekly_overtime' => 'decimal:2',
        'admin_only' => 'boolean',
        'is_admin_field' => 'boolean',
    ];

    /**
     * Get the contract that owns this data.
     */
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }
    
    /**
     * Calculate derived fields before saving
     */
    protected static function booted()
    {
        static::saving(function ($contractData) {
            // Calculer le nombre d'heures mensuelles
            if ($contractData->work_hours) {
                $contractData->monthly_hours = (float)$contractData->work_hours * 4.33;
                $contractData->weekly_hours = (float)$contractData->work_hours;
            }
            
            // Calculer le salaire brut mensuel
            if ($contractData->hourly_rate && $contractData->monthly_hours) {
                $contractData->monthly_gross_salary = (float)$contractData->hourly_rate * (float)$contractData->monthly_hours;
            }
            
            // Calculer la date de fin de période d'essai
            if ($contractData->contract_start_date && $contractData->trial_period_months) {
                $startDate = Carbon::parse($contractData->contract_start_date);
                $contractData->trial_period_end_date = $startDate->addMonths((int)$contractData->trial_period_months);
            }
            
            // Calculer les heures supplémentaires
            if ($contractData->overtime_hours_20 && $contractData->hourly_rate) {
                $overtimeRate = (float)$contractData->hourly_rate * 1.2;
                $contractData->monthly_overtime = (float)$contractData->overtime_hours_20 * $overtimeRate;
                $contractData->weekly_overtime = (float)$contractData->monthly_overtime / 4.33;
            }
        });
    }
}
