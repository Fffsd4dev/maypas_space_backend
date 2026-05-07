<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EstateManager extends Model
{
     protected $fillable = ['uuid','estate_name', 'slug', 'created_by'];
     protected $table ='estate_managers';

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

     // protected $hidden = [
     //      'id',
     // ];
     public function landlordAgents()
{
    return $this->hasMany(LandlordAgent::class, 'estate_manager_id');
}

}
