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
        'title',
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
        'certificate_generated_at'
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
}
