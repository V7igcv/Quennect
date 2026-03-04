<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barangay extends Model
{
    use HasFactory;

    protected $fillable = [
        'barangay_name'
    ];

    /**
     * Get all queue transactions from this barangay
     */
    public function queueTransactions()
    {
        return $this->hasMany(QueueTransaction::class);
    }
}
