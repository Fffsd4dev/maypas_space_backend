<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceRequest extends Model
{
    protected $fillable = [
        'tenant_id',
        'apartment_id',
        'landlord_agent_id',
        'title',
        'description',
        'status',
        'priority',
        'expected_visit_date',
        'attachment',
        'estate_manager_id',
    ];

    // Relationships
    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function apartment()
    {
        return $this->belongsTo(Apartment::class);
    }

    public function landlordAgent()
    {
        return $this->belongsTo(User::class, 'landlord_agent_id');
    }

    public function logs()
    {
        return $this->hasMany(MaintenanceLog::class, 'maintenance_id');
    }

    public function estateManager()
    {
        return $this->belongsTo(EstateManager::class);
    }
}
