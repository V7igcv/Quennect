<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

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
    public function hasRole($roleName)
    {
        return $this->role && $this->role->name === $roleName;
    }

    public function isSuperadmin()
    {
        return $this->hasRole('SUPERADMIN');
    }

    public function isFrontdesk()
    {
        return $this->hasRole('FRONTDESK');
    }

    /**
     * Scope to get active users
     */
    // public function scopeActive($query)
    // {
    //     return $query->where('is_active', true);
    // }
}
