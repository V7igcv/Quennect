<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'users';
    
    protected $fillable = [
        'username',
        'password_hash',
        'office_id',
        'role_id',
        'last_login_at'
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    protected $casts = [
        'last_login_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the office this user belongs to
     */
    public function office()
    {
        return $this->belongsTo(Office::class);
    }

    /**
     * Get the role of this user
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the password for authentication
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    /**
     * Get the username for authentication
     */
    public function getAuthIdentifierName()
    {
        return 'username';
    }

    /**
     * Helper methods for role checking
     */
    public function hasRole($roleName): bool
    {
        return $this->role && $this->role->name === $roleName;
    }

    public function isSuperadmin(): bool
    {
        return $this->hasRole('SUPERADMIN');
    }

    public function isFrontdesk(): bool
    {
        return $this->hasRole('OFFICE FRONTDESK');
    }

    /**
     * Check if user can access a specific office
     */
    public function canAccessOffice(Office $office): bool
    {
        if ($this->isSuperadmin()) {
            return true;
        }
        
        return $this->isFrontdesk() && $this->office_id === $office->id;
    }
}
