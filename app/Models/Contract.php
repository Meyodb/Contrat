<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'admin_id',
        'contract_template_id',
        'parent_contract_id',
        'title',
        'avenant_number',
        'contract_type',
        'status',
        'admin_notes',
        'admin_signature',
        'employee_signature',
        'final_document_path',
        'submitted_at',
        'admin_signed_at',
        'employee_signed_at',
        'completed_at',
        'generated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'submitted_at' => 'datetime',
        'admin_signed_at' => 'datetime',
        'employee_signed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the contract.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the template for this contract.
     */
    public function template()
    {
        return $this->belongsTo(ContractTemplate::class, 'contract_template_id');
    }

    /**
     * Get the data associated with the contract.
     */
    public function data()
    {
        return $this->hasOne(ContractData::class);
    }
    
    /**
     * Get the parent contract of this avenant.
     */
    public function parentContract()
    {
        return $this->belongsTo(Contract::class, 'parent_contract_id');
    }
    
    /**
     * Get the avenants (child contracts) of this contract.
     */
    public function avenants()
    {
        return $this->hasMany(Contract::class, 'parent_contract_id')
                   ->where('contract_type', 'avenant');
    }
    
    /**
     * Check if this contract is an avenant.
     */
    public function isAvenant()
    {
        return $this->contract_type === 'avenant';
    }
    
    /**
     * Get the latest avenant number for a given parent contract.
     */
    public static function getNextAvenantNumber($parentContractId)
    {
        $maxNumber = static::where('parent_contract_id', $parentContractId)
                        ->where('contract_type', 'avenant')
                        ->max('avenant_number');
        
        return $maxNumber ? (intval($maxNumber) + 1) : 1;
    }
}
