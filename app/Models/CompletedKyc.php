<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CompletedKyc extends Model
{
    protected $fillable = [
        'uuid',
        'tenant_id',
        'verified',
        'verified_by',
        'queried',
        'queried_by',
        'reason',
        'estate_manager_id',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                // Generate a UUID and trim it to 10 characters
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function tenant(){
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function verifier(){
        return $this->belongsTo(LandlordAgent::class, 'verified_by');
    }

    public function querier(){
        return $this->belongsTo(LandlordAgent::class, 'queried_by');
    }
}
