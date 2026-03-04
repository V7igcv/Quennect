<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    // protected $casts = [
    //     'is_active' => 'boolean',
    // ];

    /**
     * Get all users with this role
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Scope to get only active roles
     */
    // public function scopeActive($query)
    // {
    //     return $query->where('is_active', true);
    // }

    /**
     * Check if this is a specific role
     */
    public function isSuperadmin()
    {
        return $this->name === 'SUPERADMIN';
    }

    public function isFrontdesk()
    {
        return $this->name === 'FRONTDESK';
    }
}