<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Branch extends Model
{
    protected $fillable = [
        'name',
        'location_id',
        'estate_manager_id'
    ];

     //automatically generate UUID
    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                // Generate a UUID and trim it to 10 characters
                $model->uuid = (string) Str::uuid();
            }
        });

    }

    public function location(){
        return $this->belongsTo(Location::class);
    }
}
