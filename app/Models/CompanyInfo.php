<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyInfo extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'address',
        'postal_code',
        'city',
        'siret',
        'logo_path',
        'phone',
        'email',
        'website',
        'legal_representative',
        'legal_status'
    ];
} 