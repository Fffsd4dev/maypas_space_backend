<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceLog extends Model
{
    protected $fillable = [
        'maintenance_id',
        'technician_id',
        'log_message',
        'status_update',
        'visit_date',
        'next_expected_visit_date',
        'estate_manager_id',
    ];

    public function maintenance()
    {
        return $this->belongsTo(MaintenanceRequest::class, 'maintenance_id');
    }

    public function technician()
    {
        return $this->belongsTo(Technician::class, 'technician_id');
    }

    public function estateManager()
    {
        return $this->belongsTo(EstateManager::class);
    }
}
