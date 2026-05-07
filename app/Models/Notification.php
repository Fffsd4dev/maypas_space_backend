<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'type',
        'data',
        'apartment_id',
        'for',
        'is_read',
        'estate_manager_id',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    // Relationships (optional)
    public function apartment()
    {
        return $this->belongsTo(Apartment::class);
    }

    public function landlordAgent()
    {
        return $this->belongsTo(LandlordAgent::class, 'for');
    }

    public function estateManager()
    {
        return $this->belongsTo(EstateManager::class);
    }
}
