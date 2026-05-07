<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Technician extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'specialty_id',
        'estate_manager_id',
    ];

    public function logs()
    {
        return $this->hasMany(MaintenanceLog::class, 'technician_id');
    }

    public function specialist()
    {
        return $this->belongsTo(Specialty::class, 'specialty_id');
    }

    public function estateManager()
    {
        return $this->belongsTo(EstateManager::class);
    }
}
