<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Office extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'office_name',
        'office_description',
        'office_acronym',
        'logo',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    // Optional: Add an accessor for logo URL
    public function getLogoUrlAttribute()
    {
        if (!$this->logo) {
            return asset('images/default-office-logo.png');
        }

        $logo = ltrim(trim($this->logo), '/');

        // Accept full/absolute URLs as-is.
        if (Str::startsWith($logo, ['http://', 'https://', '//'])) {
            return $this->logo;
        }

        // Stored as storage/... path.
        if (Str::startsWith($logo, 'storage/')) {
            return asset($logo);
        }

        // Stored as logos/... path from public disk.
        if (Str::startsWith($logo, 'logos/')) {
            return asset('storage/' . $logo);
        }

        // Stored as raw filename.
        return asset('storage/logos/' . $logo);
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
