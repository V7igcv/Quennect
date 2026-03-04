<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Office extends Model
{
    use HasFactory;

    protected $fillable = [
        'office_name',
        'office_description',
        'office_acronym',
        'logo',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Optional: Add an accessor for logo URL
    public function getLogoUrlAttribute()
    {
        return $this->logo ? asset('storage/logos/' . $this->logo) : asset('images/default-office-logo.png');
    }

    /**
     * Get all services offered by this office
     */
    public function services()
    {
        return $this->hasMany(Service::class);
    }

    /**
     * Get all counters in this office
     */
    public function counters()
    {
        return $this->hasMany(Counter::class);
    }

    /**
     * Get all users (staff) assigned to this office
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get all queue transactions for this office
     */
    public function queueTransactions()
    {
        return $this->hasMany(QueueTransaction::class);
    }

    /**
     * Scope to get only active offices
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
