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

    /**
     * Get all users with this role
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

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

    /**
     * Get role by name
     */
    public static function getRoleId(string $name): ?int
    {
        return self::where('name', $name)->value('id');
    }
}