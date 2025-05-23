<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasRoles, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'signature_image',
        'archived',
        'archived_at',
        'profile_photo_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_admin' => 'boolean',
        'archived' => 'boolean',
        'archived_at' => 'datetime',
    ];

    /**
     * Get the contract associated with the user.
     */
    public function contract()
    {
        return $this->hasOne(Contract::class);
    }

    /**
     * Get all contracts associated with the user.
     */
    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    /**
     * Check if the user is an administrator.
     */
    public function isAdmin()
    {
        return $this->is_admin;
    }
}
