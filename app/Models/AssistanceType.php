<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssistanceType extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'assistance_name',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the service that this assistance type belongs to
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
