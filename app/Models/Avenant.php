<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Avenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'contract_id',
        'initial_contract_date',
        'effective_date',
        'signing_date',
        'current_hours',
        'current_salary',
        'current_hourly_rate',
        'new_hours',
        'new_salary',
        'new_hourly_rate',
        'motif',
        'status',
        'pdf_path',
    ];

    protected $casts = [
        'initial_contract_date' => 'date',
        'effective_date' => 'date',
        'signing_date' => 'date',
        'current_hours' => 'float',
        'current_salary' => 'float',
        'current_hourly_rate' => 'float',
        'new_hours' => 'float',
        'new_salary' => 'float',
        'new_hourly_rate' => 'float',
    ];

    /**
     * Get the contract associated with the avenant.
     */
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Get the employee associated with the avenant through the contract.
     */
    public function employee()
    {
        return $this->hasOneThrough(User::class, Contract::class, 'id', 'id', 'contract_id', 'user_id');
    }
} 