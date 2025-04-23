<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractTemplate extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'file_path',
    ];

    /**
     * Get the contracts that use this template.
     */
    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }
}
