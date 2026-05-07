<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class LandlordAgent extends Authenticatable
{
    use HasApiTokens;
    protected $fillable = [
        'uuid',
        'first_name',
        'last_name',
        'email',
        'phone',
        'id_card',
        'selfie_photo',
        'cac',
        'business_name',
        'business_state',
        'business_lga',
        'about_business',
        'business_services',
        'business_address',
        'logo',
        'verified',

        'email_verified_at',
        'estate_manager_id',
        'user_type_id',
        'password',
    ];

    protected $hidden = [
        'password',
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


    public function user_type()
    {
        return $this->belongsTo(UserType::class, 'user_type_id');
    }

    public function apartments()
    {
        return $this->hasMany(Apartment::class);
    }
    public function estateManager()
{
    return $this->belongsTo(EstateManager::class, 'estate_manager_id');
}

}
